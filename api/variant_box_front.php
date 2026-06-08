<?php
require_once __DIR__ . '/../a9sd8f7sd9f_admin/config.php';

function qii_asset_path($path) {
    $path = trim((string)$path);
    if ($path === '') return 'images/logo.png';
    if (preg_match('#^(https?:)?//#', $path)) return $path;
    $path = ltrim($path, '/');
    if (strpos($path, 'uploads/') === 0 || strpos($path, 'images/') === 0) return $path;
    return 'uploads/' . $path;
}

function qii_text($text) {
    $text = (string)$text;
    if ($text === '') return '';
    if (preg_match('/[ÂµÃžÃ•ÃšÃÃ¾â•”â•â•‘â•£â•â•—â–“â–‘â”¤â”â””â”´â”¬â”œâ”¼]/u', $text)) {
        $fixed = @iconv('UTF-8', 'CP850//IGNORE', $text);
        if (is_string($fixed) && $fixed !== '' && preg_match('/[\x{4E00}-\x{9FFF}]/u', $fixed)) {
            return $fixed;
        }
    }
    return $text;
}

$product_id = (int)($_GET['product_id'] ?? 0);

// èŽ·å–å•†å“ä¿¡æ¯
$stmt = $pdo->prepare("SELECT id, name, price, stock, image_url FROM products WHERE id = ? AND COALESCE(status, 'active') = 'active' LIMIT 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<div style='padding:12px;text-align:center;color:#C94B82;'>å•†å“ä¸å­˜åœ¨</div>";
    exit;
}

// æŸ¥ group
$groupsStmt = $pdo->prepare('SELECT id, group_name FROM product_groups WHERE product_id = ? ORDER BY sort_order ASC');
$groupsStmt->execute([$product_id]);
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

// â­ æŸ¥æ˜¯å¦æœ‰ variantï¼ˆæœ€é‡è¦ä¿®å¤ï¼‰
$variantCountStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM product_variants 
    WHERE group_id IN (SELECT id FROM product_groups WHERE product_id = ?)
");
$variantCountStmt->execute([$product_id]);
$variantCount = (int)$variantCountStmt->fetchColumn();

// â­â­â­ å¦‚æžœæ²¡æœ‰ä»»ä½•è§„æ ¼ï¼ˆgroup æ—  æˆ– group æœ‰ä½†æ²¡æœ‰ variantï¼‰
if (empty($groups) || $variantCount === 0) {

    echo "<div class='no-variant'
            data-novariant='1'
            data-price='" . number_format((float)$product['price'], 2) . "'
            data-stock='" . (int)$product['stock'] . "'
            data-name='" . htmlspecialchars(qii_text($product['name'])) . "'
            data-img='" . htmlspecialchars(qii_asset_path($product['image_url'])) . "'
          ></div>";
    exit;
}
?>

<script>
var CURRENT_PRODUCT_ID = <?= $product_id ?>;
</script>

<?php foreach ($groups as $g): ?>

<div class="variant-group-title">
    <?= htmlspecialchars(qii_text($g['group_name'])) ?>
</div>

<div class="variant-grid">

<?php
$variantStmt = $pdo->prepare('SELECT * FROM product_variants WHERE group_id = ? ORDER BY sort_order ASC');
$variantStmt->execute([$g['id']]);
$variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($variants as $v):
?>

<div class="variant-card"
     data-vid="<?= $v['id'] ?>"
     data-vname="<?= htmlspecialchars(qii_text($v['variant_name'])) ?>"
     data-vprice="<?= $v['price'] ?>"
     data-vstock="<?= $v['stock'] ?>"
     data-vimg="<?= htmlspecialchars(qii_asset_path($v['image_url'])) ?>"
>
    <img src="<?= htmlspecialchars(qii_asset_path($v['image_url'])) ?>" alt="">
    <div>
        <div class="variant-name"><?= htmlspecialchars(qii_text($v['variant_name'])) ?></div>
        <div class="variant-stock">库存：<?= (int)$v['stock'] ?></div>
    </div>
</div>

<?php endforeach; ?>

</div>

<?php endforeach; ?>

<script>
function updateModal(card){
    if (!card) return;

    parent.document.getElementById('selectedVariantId').value = card.dataset.vid;
    parent.document.getElementById('selectedVariantName').value = card.dataset.vname;

    parent.document.getElementById('modalPrice').textContent =
        parseFloat(card.dataset.vprice).toFixed(2);

    parent.document.getElementById('modalStock').textContent =
        '库存：' + card.dataset.vstock;

    parent.document.getElementById('modalImg').src = parent.qiiAssetPath
        ? parent.qiiAssetPath(card.dataset.vimg)
        : card.dataset.vimg;
}

document.addEventListener('click', function(e){
    let card = e.target.closest('.variant-card');
    if(!card) return;

    document.querySelectorAll('.variant-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');

    updateModal(card);
});

window.addEventListener('DOMContentLoaded', ()=>{
    let first = document.querySelector('.variant-card');
    if(first){
        first.classList.add('active');
        updateModal(first);
    }
});
</script>
