<?php
session_start(); 
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
require_once __DIR__ . '/../../app/categories.php';
require_once __DIR__ . '/../../app/content_settings.php';
require_once __DIR__ . '/../../app/customers.php';

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
  return htmlspecialchars(json_encode([
    'id' => (int)$p['id'],
    'name' => qii_text($p['name']),
    'price' => (float)$p['price'],
    'stock' => (int)$p['stock'],
    'sku' => $p['sku'] ?? '',
    'has_variant' => isset($p['has_variant']) ? (int)$p['has_variant'] : 0,
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
  ]); ?>
  <link rel="stylesheet" href="css/style.css?v=20260730-2" />
  <link rel="stylesheet" href="css/shop.css?v=20260730-2" />
  <link rel="stylesheet" href="css/shop-page.css?v=20260730-2" />
  <link rel="stylesheet" href="css/shop-mobile.css?v=20260730-2" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
  <style id="shop-critical-css">
    html, body { margin: 0; max-width: 100%; overflow-x: hidden; background: #fff8fb; }
    body { font-family: "Patrick Hand", "Comic Sans MS", Arial, "Microsoft YaHei", sans-serif; color: #513348; }
    main { padding-top: 0; }
    #loader { position: fixed; inset: 0; z-index: 2000; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(180deg,#fff6fa 0%,#ffe9f0 100%); transition: opacity .3s ease; }
    #loader.fade-out { opacity: 0; pointer-events: none; }
    #loader img { width: 160px; height: auto; }
    .mobile-shop-top, .mobile-bottom-nav { display: none; }
    .shop-header { width: 100%; padding: 34px 16px 38px; background: linear-gradient(180deg,#ffd1e3 0%,#ffe6f2 100%); text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .shop-header img { width: 150px; height: auto; margin-bottom: 10px; }
    .shop-header h1 { margin: 0; color: #e44b87; font-size: 32px; font-weight: 800; line-height: 1.15; }
    .shop-layout { display: grid; grid-template-columns: 180px minmax(0,1fr); gap: 15px; width: 95%; max-width: 1200px; margin: 30px auto; align-items: start; }
    .shop-layout > div { min-width: 0; padding: 25px; border: 2px solid #f8c9da; border-radius: 25px; background: linear-gradient(180deg,#fff0f7 0%,#ffe6f0 100%); box-shadow: 0 4px 10px rgba(240,150,180,.15); }
    .sidebar { position: sticky; top: 100px; max-height: calc(100vh - 140px); overflow-y: auto; padding: 12px; border: 2px solid #f8c9da; border-radius: 15px; background: linear-gradient(180deg,#fff0f7 0%,#ffe6f0 100%); box-shadow: 0 4px 10px rgba(240,150,180,.15); }
    .sidebar h3 { margin: 0 0 10px; color: #e5679c; font-size: 18px; text-align: center; }
    .sidebar ul { list-style: none; display: flex; flex-direction: column; gap: 10px; margin: 0; padding: 0; }
    .sidebar li { margin: 0; padding: 10px; border: 1px solid #f6bdd9; border-radius: 15px; background: #ffe6f0; color: #c94b82; text-align: center; font-size: 15px; line-height: 1.15; }
    .sidebar li.active { background: #f9b8cf; color: #fff; }
    .sidebar li a { display: flex; flex-direction: inherit; align-items: center; justify-content: center; gap: 4px; color: inherit; text-decoration: none; }
    .cat-name { white-space: pre-line; line-height: 1.04; }
    .category-title { margin: 0 0 16px; color: #e44b87; font-size: 26px; font-weight: 800; line-height: 1.1; white-space: pre-line; }
    .product-area { display: flex; flex-direction: column; gap: 15px; }
    .product-card { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 14px; min-width: 0; padding: 15px 18px; border: 2px solid #f8c9da; border-radius: 18px; background: #fff6fa; box-shadow: 0 4px 10px rgba(240,150,180,.15); }
    .product-card > img { width: 80px; height: 80px; flex: 0 0 80px; object-fit: cover; border: 1px solid #f6bdd9; border-radius: 10px; }
    .product-info { flex: 1; min-width: 0; text-align: left; }
    .product-info h4 { margin: 0 0 6px; color: #e44b87; font-size: 16px; line-height: 1.25; overflow-wrap: anywhere; }
    .product-info .price { color: #e44b87; font-size: 15px; font-weight: 800; }
    .choose-btn, .add-btn { position: relative; z-index: 12; min-height: 38px; border: 0; border-radius: 20px; background: linear-gradient(180deg,#ffbbd4,#ff9ec5); color: #fff; cursor: pointer; }
    .choose-btn { width: 42px; height: 42px; min-width: 42px; padding: 0; border-radius: 50%; box-shadow: 0 4px 10px rgba(230,103,156,.25); }
    .choose-btn::before { content: "\1F6D2"; font-size: 18px; line-height: 1; }
    .add-btn { padding: 8px 16px; }
    .soldout-tag { position: absolute; top: 8px; left: 8px; z-index: 3; padding: 6px 10px; border-radius: 999px; background: rgba(104,72,87,.86); color: #fff; font-size: 10px; font-weight: 700; }
    .shop-pagination { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 12px; margin: 30px auto 12px; padding: 18px 12px; }
    .shop-pagination a, .shop-pagination span { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 22px; border-radius: 999px; text-decoration: none; font-family: Arial, "Microsoft YaHei", sans-serif; font-size: 15px; font-weight: 800; }
    .shop-pagination a { background: linear-gradient(135deg,#ff72ad,#f5368d); color: #fff; }
    .shop-pagination span { border: 1px solid #ffc1dc; background: #fff0f7; color: #d92e7c; }
    .falling-sakura { position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; pointer-events: none; z-index: 1500; }
    .falling-sakura .sakura { position: absolute; top: -100px; width: 80px; height: auto; max-width: none; opacity: .7; animation: sakuraFall 10s linear infinite; }
    @keyframes sakuraFall {
      0% { transform: translateY(0) rotate(0deg); opacity: 0; }
      10% { opacity: 1; }
      90% { transform: translateY(100vh) rotate(360deg); opacity: 1; }
      100% { transform: translateY(105vh) rotate(390deg); opacity: 0; }
    }
    @media (max-width: 768px) {
      body { padding-bottom: 20px; background: linear-gradient(180deg,#fff5fa 0%,#ffeef6 100%); }
      #loader, .shop-header { display: none !important; }
      .mobile-shop-top { display: block; padding: 8px 16px 0; }
      .mobile-promo { position: relative; min-height: 152px; margin-top: 12px; overflow: hidden; padding: 24px 18px; border: 1px solid #ffd5e4; border-radius: 20px; background: linear-gradient(90deg,rgba(255,201,224,.96),rgba(255,218,234,.9) 46%,rgba(255,242,248,.72)); box-shadow: 0 10px 24px rgba(229,103,156,.12); }
      .mobile-promo h2 { position: relative; z-index: 2; max-width: 62%; margin: 0 0 8px; color: #f13987; font-size: 23px; line-height: 1.18; }
      .mobile-promo p { position: relative; z-index: 2; margin: 0 0 16px; color: #5f4653; font-size: 13px; }
      .mobile-promo a { position: relative; z-index: 2; display: inline-flex; align-items: center; min-height: 32px; padding: 0 16px; border-radius: 999px; background: #f5368d; color: #fff; text-decoration: none; font-size: 13px; font-weight: 700; }
      .mobile-promo img { position: absolute; right: 6px; bottom: 6px; z-index: 1; width: 42%; max-height: 126px; object-fit: contain; }
      .shop-layout { display: block; width: auto; margin: 12px 14px 0; }
      .shop-layout > div { padding: 0; border: 0; background: transparent; box-shadow: none; }
      .sidebar { position: relative; top: auto; max-height: none; margin: 0 0 14px; padding: 8px 8px 10px; overflow-x: auto; overflow-y: hidden; border-radius: 20px; background: rgba(255,255,255,.9); }
      .sidebar h3 { display: none; }
      .sidebar ul { flex-direction: row; gap: 10px; min-width: max-content; }
      .sidebar li { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; min-width: 70px; min-height: 66px; padding: 8px 10px; border-radius: 16px; white-space: nowrap; font-size: 12px; }
      .cat-emoji { display: block; font-size: 19px; line-height: 1; }
      .cat-name { display: block; font-size: 11px; }
      .category-title { margin: 16px 4px 12px; font-size: 20px; }
      .product-area { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 14px; }
      .product-card { display: flex; flex-direction: column; align-items: stretch; min-height: 250px; padding: 0; overflow: hidden; border-radius: 16px; background: #fff; }
      .product-card > img { width: 100%; height: auto; flex: none; aspect-ratio: 1/.82; border: 0; border-radius: 0; }
      .product-info { padding: 10px 10px 48px; }
      .product-info h4 { display: -webkit-box; min-height: 34px; margin: 0 0 4px; overflow: hidden; color: #42343d; font-family: Arial, "Microsoft YaHei", sans-serif; font-size: 13px; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
      .product-info .price { font-family: Arial, "Microsoft YaHei", sans-serif; font-size: 17px; }
      .choose-btn, .add-btn { position: absolute; right: 12px; bottom: 12px; width: 40px; min-width: 40px; height: 40px; min-height: 40px; padding: 0; border-radius: 50%; font-size: 0; }
      .add-btn:disabled { width: auto; min-width: 54px; padding: 0 12px; font-size: 12px; }
    }
  </style>
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
      $productCardAos = '';
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
  <script src="js/shop-page.js?v=20260729-1"></script>




<?php include __DIR__ . '/../components/variant_modal.php'; ?>

  <!-- ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¸ Qii ÃƒÂ¥Ã‚ÂÃ‚Â¯ÃƒÂ§Ã‹â€ Ã‚Â±ÃƒÂ¦Ã‚ÂÃ‚ÂÃƒÂ§Ã‚Â¤Ã‚ÂºÃƒÂ¦Ã‚Â¡Ã¢â‚¬Â  -->
  <div id="qiiToast" class="qii-toast">已加入购物车</div>

</body>
</html>


