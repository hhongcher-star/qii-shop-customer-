<?php
session_start(); 
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
require_once __DIR__ . '/../../app/categories.php';
require_once __DIR__ . '/../../app/content_settings.php';
require_once __DIR__ . '/../../app/customers.php';
$favoritesEnabled = defined('QII_FAVORITES_ENABLED') && QII_FAVORITES_ENABLED;
if ($favoritesEnabled) {
  qii_ensure_customer_tables($pdo);
}

$favoriteProductIds = [];
if ($favoritesEnabled && qii_customer_id()) {
  $favoriteStmt = $pdo->prepare('SELECT product_id FROM customer_favorites WHERE customer_id=?');
  $favoriteStmt->execute([qii_customer_id()]);
  $favoriteProductIds = array_map('intval', $favoriteStmt->fetchAll(PDO::FETCH_COLUMN));
}

$shopTitle = qii_sanitize_rich_text(qii_content($pdo, 'shop_title', '🌸 可爱生活选物'));
$shopPromoTitle = qii_sanitize_rich_text(qii_content($pdo, 'shop_promo_title', '新品可爱小物上线啦 ✨'));
$shopPromoText = qii_sanitize_rich_text(qii_content($pdo, 'shop_promo_text', '可爱治愈 · 限时优惠'));
$shopPromoButton = qii_sanitize_rich_text(qii_content($pdo, 'shop_promo_button', '立即选购 ›'));
$shopPromoImage = qii_content($pdo, 'shop_promo_image', 'images/qii-gift.png');

function qii_asset_path($path) {
  $path = trim((string)$path);
  if ($path === '') return 'images/logo.png';
  if (preg_match('#^(https?:)?//#', $path)) return $path;
  $path = ltrim($path, '/');
  $assetPath = (strpos($path, 'uploads/') === 0 || strpos($path, 'images/') === 0) ? $path : 'uploads/' . $path;
  $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
  $base = rtrim(dirname($script), '/');
  if (str_ends_with($base, '/frontend/pages')) {
    $base = substr($base, 0, -strlen('/frontend/pages'));
  }
  return ($base === '' ? '' : $base) . '/' . $assetPath;
}

function qii_text($text) {
  $text = (string)$text;
  if ($text === '') return '';

  if (preg_match('/[Ãƒâ€šÃ‚ÂµÃƒÆ’Ã…Â¾ÃƒÆ’Ã¢â‚¬Â¢ÃƒÆ’Ã…Â¡ÃƒÆ’Ã‚ÂÃƒÆ’Ã‚Â¾ÃƒÂ¢Ã¢â‚¬Â¢Ã¢â‚¬ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã¢â‚¬ËœÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â£ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã¢â‚¬â€ÃƒÂ¢Ã¢â‚¬â€œÃ¢â‚¬Å“ÃƒÂ¢Ã¢â‚¬â€œÃ¢â‚¬ËœÃƒÂ¢Ã¢â‚¬ÂÃ‚Â¤ÃƒÂ¢Ã¢â‚¬ÂÃ‚ÂÃƒÂ¢Ã¢â‚¬ÂÃ¢â‚¬ÂÃƒÂ¢Ã¢â‚¬ÂÃ‚Â´ÃƒÂ¢Ã¢â‚¬ÂÃ‚Â¬ÃƒÂ¢Ã¢â‚¬ÂÃ…â€œÃƒÂ¢Ã¢â‚¬ÂÃ‚Â¼]/u', $text)) {
    $fixed = @iconv('UTF-8', 'CP850//IGNORE', $text);
    if (is_string($fixed) && $fixed !== '' && preg_match('/[\x{4E00}-\x{9FFF}]/u', $fixed)) {
      return $fixed;
    }
  }

  return $text;
}

function qii_product_payload($p) {
  global $favoriteProductIds, $favoritesEnabled;
  return htmlspecialchars(json_encode([
    'id' => (int)$p['id'],
    'name' => qii_text($p['name']),
    'price' => (float)$p['price'],
    'stock' => (int)$p['stock'],
    'sku' => $p['sku'] ?? '',
    'has_variant' => isset($p['has_variant']) ? (int)$p['has_variant'] : 0,
    'favorite' => $favoritesEnabled && in_array((int)$p['id'], $favoriteProductIds, true),
    'img' => qii_asset_path($p['image_url'] ?? ''),
  ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}

// ÃƒÂ¥Ã‚Â½Ã¢â‚¬Å“ÃƒÂ¥Ã¢â‚¬Â°Ã‚ÂÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ§Ã‚Â±Ã‚Â»ÃƒÂ¯Ã‚Â¼Ã‹â€ ÃƒÂ©Ã‚Â»Ã‹Å“ÃƒÂ¨Ã‚Â®Ã‚Â¤ÃƒÂ§Ã‚Â¬Ã‚Â¬ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ¤Ã‚Â¸Ã‚ÂªÃƒÂ¯Ã‚Â¼Ã¢â‚¬Â°
$cat = isset($_GET['cat']) ? trim((string)$_GET['cat']) : '';

if (($_GET['edit_popup'] ?? '') === 'variant') {
  $previewProductStmt = $pdo->query("
    SELECT category
    FROM products
    WHERE COALESCE(status, 'active') = 'active'
      AND stock > 0
      AND has_variant = 1
    ORDER BY id ASC
    LIMIT 1
  ");
  $previewCategory = $previewProductStmt->fetchColumn();
  if (is_string($previewCategory) && $previewCategory !== '') {
    $cat = $previewCategory;
  }
}


// ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ§Ã‚Â±Ã‚Â»ÃƒÂ¦Ã‹Å“Ã‚Â ÃƒÂ¥Ã‚Â°Ã¢â‚¬Å¾
$categories = [
  'phone'   => '&#128241; &#25163;&#26426;&#37197;&#20214;',
  'hair'    => '&#127872; &#21457;&#22841;&#21457;&#39280;',
  'snack'   => '&#127853; &#38646;&#39135;',
  'creative'=> '&#128150; &#25991;&#21019;',
  'case'    => '&#128150; &#25163;&#26426;&#22771;',
  'nail'    => '&#128133; &#31359;&#25140;&#30002;',
  'scent'   => '&#127800; &#39321;&#29255;',
  'doll'    => '&#129528; &#23043;&#23043;',
  'stationery' => '&#9999;&#65039; &#25991;&#20855;'
];

$categoryRows = qii_categories($pdo);
$categories = [];
foreach ($categoryRows as $key => $row) {
  $categories[$key] = trim((string)($row['emoji'] ?? '') . ' ' . (string)($row['name'] ?? $key));
}

function qii_category_label_text(string $html): string {
  return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
}

function qii_category_label_html(string $html): string {
  $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
  $html = preg_replace('/<(div|p)[^>]*>/i', '<br>', $html);
  $html = preg_replace('/<\/?(div|p)[^>]*>/i', '', $html);
  $html = strip_tags($html, '<br>');
  $html = preg_replace('/<br\s*\/?>/i', '<br>', $html);
  $html = preg_replace('/^(<br>)+|(<br>)+$/i', '', $html);
  if (!class_exists('DOMDocument')) {
    return trim(strip_tags($html, '<br>'));
  }

  $document = new DOMDocument('1.0', 'UTF-8');
  libxml_use_internal_errors(true);
  $document->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
  libxml_clear_errors();

  $renderNode = function (DOMNode $node) use (&$renderNode): string {
    if ($node instanceof DOMText) {
      return htmlspecialchars($node->nodeValue, ENT_QUOTES, 'UTF-8');
    }
    if (!$node instanceof DOMElement) return '';
    if (strtolower($node->tagName) === 'br') return '<br>';

    $children = '';
    foreach ($node->childNodes as $child) {
      $children .= $renderNode($child);
    }

    return $children;
  };

  $root = $document->getElementsByTagName('div')->item(0);
  $clean = '';
  if ($root) {
    foreach ($root->childNodes as $child) {
      $clean .= $renderNode($child);
    }
  }
  return trim($clean);
}

if ($cat === '' || !isset($categories[$cat])) {
  $cat = array_key_first($categories) ?: 'phone';
}

// 普通商店分页显示；后台排序模式必须载入当前分类的全部商品。
$shopPage = max(1, (int)($_GET['page'] ?? 1));
$sortEditMode = ($_GET['sort_edit'] ?? '') === '1';
$shopPageSize = $sortEditMode ? 100000 : 24;
$shopOffset = ($shopPage - 1) * $shopPageSize;
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND COALESCE(status, 'active') = 'active' ORDER BY sort_order ASC, created_at DESC LIMIT {$shopPageSize} OFFSET {$shopOffset}");
$stmt->execute([$cat]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ? AND status = 'active'");
$countStmt->execute([$cat]);
$categoryProductCount = (int)$countStmt->fetchColumn();
$totalShopPages = max(1, (int)ceil($categoryProductCount / $shopPageSize));

// ÃƒÂ¥Ã‚Â¦Ã¢â‚¬Å¡ÃƒÂ¦Ã…Â¾Ã…â€œÃƒÂ¦Ã‹Å“Ã‚Â¯ AJAX ÃƒÂ¨Ã‚Â¯Ã‚Â·ÃƒÂ¦Ã‚Â±Ã¢â‚¬Å¡ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¥Ã‚ÂÃ‚ÂªÃƒÂ¨Ã‚Â¿Ã¢â‚¬ÂÃƒÂ¥Ã¢â‚¬ÂºÃ…Â¾ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚Â HTML
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
  ob_start();
  if ($products) {
    foreach ($products as $p) {
      $productCardAos = '';
      include __DIR__ . '/../components/product_card.php';
    }
  } else {
    echo "<p>No products yet.</p>";
  }
  $html = ob_get_clean();
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'html' => $html,
    'page' => $shopPage,
    'total_pages' => $totalShopPages,
    'total_products' => $categoryProductCount,
    'category' => $cat,
  ], JSON_UNESCAPED_UNICODE);
  exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />  <?php require_once __DIR__ . '/../includes/seo.php'; ?>
  <?php qii_seo_meta([
    'title' => 'Shop qii.shoppp | Cute Phone Charms, Accessories & Kawaii Gifts',
    'description' => 'Browse qii.shoppp for cute phone accessories, hair clips, snacks, stationery, dolls, charms and kawaii lifestyle gifts in Malaysia.',
    'path' => '/shop.php',
    'keywords' => 'qii shop, cute phone charms, phone accessories Malaysia, kawaii accessories, hair clips, stationery, cute gifts'
  ]); ?><link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/shop.css" />
  <link rel="stylesheet" href="css/shop-page.css" />
  <link rel="stylesheet" href="css/shop-mobile.css" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
</head>

<body>
  <!-- åŠ è½½åŠ¨ç”» -->
  <div id="loader">
    <img src="images/25.png" alt="Loading..." />
    <p>Qii 正在陈列可爱好物中，请稍等～ 🎀</p>
  </div>

  <!-- å›¾ç‰‡æ”¾å¤§æŸ¥çœ‹å¼¹çª— -->
  <div id="imgPreview" class="img-preview-modal" style="display:none;">
    <img id="imgPreviewPic" src="" alt="">
  </div>

  <!-- æŽ‰è½ç³–æžœåŠ¨ç”» -->
  <div class="falling-sakura">
    <img src="images/candy1.png" class="sakura" />
    <img src="images/candy1.png" class="sakura" />
    <img src="images/candy1.png" class="sakura" />
    <img src="images/candy1.png" class="sakura" />
  </div>

  <!-- å¯¼èˆªæ  -->
  <?php include __DIR__ . "/../includes/header.php"; ?>

  <main>
    <section class="mobile-shop-top" aria-label="手机端商店入口">

      <div class="mobile-promo">
  <h2 data-content-key="shop_promo_title"><?= $shopPromoTitle ?></h2>
  <p data-content-key="shop_promo_text"><?= $shopPromoText ?></p>
  <a href="#shop-products" data-content-key="shop_promo_button"><?= $shopPromoButton ?></a>
  <img src="<?= htmlspecialchars($shopPromoImage) ?>" alt="Qii Gift" data-image-key="shop_promo_image">
</div>
    </section>

    <header class="shop-header" data-aos="fade-down">
      <img src="images/4.png" alt="è´­ç‰©å¥³å­©" />
      <h1 data-content-key="shop_title"><?= $shopTitle ?></h1>
    </header>

    <section class="shop-layout" id="shop-products">
      <!-- å·¦ä¾§åˆ†ç±»æ  -->
      <aside class="sidebar">
        <h3>&#128150; &#20998;&#31867;</h3>
        <ul>
          <?php foreach ($categories as $key => $label): ?>
            <?php $categoryName = (string)($categoryRows[$key]['name'] ?? $key); ?>
            <li class="cat-link <?= ($cat === $key) ? 'active' : '' ?>" data-cat="<?= htmlspecialchars($key) ?>" data-label="<?= htmlspecialchars(qii_category_label_text($categoryName)) ?>" data-html="<?= htmlspecialchars(qii_category_label_html($categoryName)) ?>">
              <a href="shop.php?cat=<?= urlencode($key) ?>">
              <?php if (!empty($categoryRows[$key]['emoji'])): ?>
                <span class="cat-emoji"><?= htmlspecialchars($categoryRows[$key]['emoji']) ?></span>
              <?php endif; ?>
              <span class="cat-name"><?= qii_category_label_html($categoryName) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>

  <!-- ÃƒÂ¥Ã‚ÂÃ‚Â³ÃƒÂ¤Ã‚Â¾Ã‚Â§ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¥Ã‚Â±Ã¢â‚¬Â¢ÃƒÂ§Ã‚Â¤Ã‚Âº -->
  <div>
    <?php $currentCategoryName = (string)($categoryRows[$cat]['name'] ?? $cat); ?>
    <h2 class="category-title"><?= !empty($categoryRows[$cat]['emoji']) ? htmlspecialchars($categoryRows[$cat]['emoji']) . ' ' : '' ?><?= qii_category_label_html($currentCategoryName) ?></h2>
    <div class="product-area">
      <?php if (empty($products)): ?>
    <p style="text-align:center;color:#999;">No products yet.</p>
  <?php else: ?>
    <?php foreach ($products as $p): ?>
      <?php
      $productCardAos = 'fade-up';
      include __DIR__ . '/../components/product_card.php';
      ?>
    <?php endforeach; ?>
  <?php endif; ?>
    </div>
    <?php if ($totalShopPages > 1): ?>
      <nav class="shop-pagination" aria-label="商品分页" data-shop-pagination>
        <?php if ($shopPage > 1): ?><a href="shop.php?cat=<?= urlencode($cat) ?>&page=<?= $shopPage - 1 ?>#shop-products">上一页</a><?php endif; ?>
        <span>第 <?= $shopPage ?> / <?= $totalShopPages ?> 页</span>
        <?php if ($shopPage < $totalShopPages): ?><a href="shop.php?cat=<?= urlencode($cat) ?>&page=<?= $shopPage + 1 ?>#shop-products">下一页</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  </div>
</section>
  </main>

  <?php include __DIR__ . "/../includes/footer.php"; ?>
<!-- ÃƒÂ¢Ã…â€œÃ‚Â¨ JS ÃƒÂ©Ã†â€™Ã‚Â¨ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â  -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="js/shop-page.js"></script>




<?php include __DIR__ . '/../components/variant_modal.php'; ?>

  <!-- ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¸ Qii ÃƒÂ¥Ã‚ÂÃ‚Â¯ÃƒÂ§Ã‹â€ Ã‚Â±ÃƒÂ¦Ã‚ÂÃ‚ÂÃƒÂ§Ã‚Â¤Ã‚ÂºÃƒÂ¦Ã‚Â¡Ã¢â‚¬Â  -->
  <div id="qiiToast" class="qii-toast">已加入购物车</div>

</body>
</html>


