<?php
require_once __DIR__ . '/uploads/config.php';

$product_id = (int)($_GET['product_id'] ?? 0);

// 获取商品信息
$stmt = $pdo->prepare('SELECT id, name, price, stock, image_url FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<div style='padding:12px;text-align:center;color:#C94B82;'>商品不存在</div>";
    exit;
}

// 查 group
$groupsStmt = $pdo->prepare('SELECT id, group_name FROM product_groups WHERE product_id = ? ORDER BY sort_order ASC');
$groupsStmt->execute([$product_id]);
$groups = $groupsStmt->fetchAll(PDO::FETCH_ASSOC);

// ⭐ 查是否有 variant（最重要修复）
$variantCountStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM product_variants 
    WHERE group_id IN (SELECT id FROM product_groups WHERE product_id = ?)
");
$variantCountStmt->execute([$product_id]);
$variantCount = (int)$variantCountStmt->fetchColumn();

// ⭐⭐⭐ 如果没有任何规格（group 无 或 group 有但没有 variant）
if (empty($groups) || $variantCount === 0) {

    echo "<div class='no-variant'
            data-novariant='1'
            data-price='" . number_format((float)$product['price'], 2) . "'
            data-stock='" . (int)$product['stock'] . "'
            data-name='" . htmlspecialchars($product['name']) . "'
            data-img='uploads/" . htmlspecialchars($product['image_url']) . "'
          ></div>";
    exit;
}
?>

<script>
var CURRENT_PRODUCT_ID = <?= $product_id ?>;
</script>

<?php foreach ($groups as $g): ?>

<div class="variant-group-title">
    <?= htmlspecialchars($g['group_name']) ?>
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
     data-vname="<?= htmlspecialchars($v['variant_name']) ?>"
     data-vprice="<?= $v['price'] ?>"
     data-vstock="<?= $v['stock'] ?>"
     data-vimg="uploads/<?= htmlspecialchars($v['image_url']) ?>"
>
    <img src="uploads/<?= htmlspecialchars($v['image_url']) ?>" alt="">
    <div>
        <div class="variant-name"><?= htmlspecialchars($v['variant_name']) ?></div>
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

    parent.document.getElementById('modalImg').src = card.dataset.vimg;
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