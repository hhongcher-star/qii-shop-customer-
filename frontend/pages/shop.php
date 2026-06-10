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
  if (strpos($path, 'uploads/') === 0 || strpos($path, 'images/') === 0) return $path;
  return 'uploads/' . $path;
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
  $categories[$key] = htmlspecialchars($row['emoji']) . ' ' . htmlspecialchars($row['name']);
}

if ($cat === '' || !isset($categories[$cat])) {
  $cat = array_key_first($categories) ?: 'phone';
}

// ÃƒÂ¦Ã…Â¸Ã‚Â¥ÃƒÂ¨Ã‚Â¯Ã‚Â¢ÃƒÂ¨Ã‚Â¯Ã‚Â¥ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ§Ã‚Â±Ã‚Â»ÃƒÂ¤Ã‚Â¸Ã¢â‚¬Â¹ÃƒÂ§Ã…Â¡Ã¢â‚¬Å¾ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚Â
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND COALESCE(status, 'active') = 'active' ORDER BY sort_order ASC, created_at DESC");
$stmt->execute([$cat]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ÃƒÂ¥Ã‚Â¦Ã¢â‚¬Å¡ÃƒÂ¦Ã…Â¾Ã…â€œÃƒÂ¦Ã‹Å“Ã‚Â¯ AJAX ÃƒÂ¨Ã‚Â¯Ã‚Â·ÃƒÂ¦Ã‚Â±Ã¢â‚¬Å¡ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¥Ã‚ÂÃ‚ÂªÃƒÂ¨Ã‚Â¿Ã¢â‚¬ÂÃƒÂ¥Ã¢â‚¬ÂºÃ…Â¾ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚Â HTML
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
  ob_start();
  if ($products) {
    foreach ($products as $p): ?>
      <div class="product-card">
        <?php if (!empty($p['brand'])): ?>
          <div class="brand-badge"><?= htmlspecialchars(qii_text($p['brand'])) ?></div>
        <?php endif; ?>
        <?php if ($p['stock'] <= 0): ?>
          <div class="soldout-tag">SOLD OUT</div>
        <?php endif; ?>
        <img src="<?= htmlspecialchars(qii_asset_path($p['image_url'] ?? '')) ?>" alt="<?= htmlspecialchars(qii_text($p['name'])) ?>">
        <div class="product-info">
          <h4><?= htmlspecialchars(qii_text($p['name'])) ?></h4>
          <?php if (!empty($p['sku'])): ?><p>SKU: <?= htmlspecialchars($p['sku']) ?></p><?php endif; ?>
          <div class="price">RM <?= number_format($p['price'], 2) ?></div>
        </div>
        <?php if ($p['stock'] > 0): ?>
          <button onclick='openVariantModal(<?= qii_product_payload($p) ?>)' class="choose-btn" aria-label="Add to cart"></button>
        <?php else: ?>
          <button class="add-btn" disabled>Sold out</button>
        <?php endif; ?>
      </div>
    <?php endforeach;
  } else {
    echo "<p>No products yet.</p>";
  }
  echo ob_get_clean();
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
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />

  <style>
    html, body {
      margin: 0;
      padding: 0;
      max-width: 100%;
      overflow-x: hidden;
    }

    /* ÃƒÂ¢Ã…â€œÃ¢â‚¬Â¦ ÃƒÂ¥Ã…Â Ã‚Â ÃƒÂ¨Ã‚Â½Ã‚Â½ÃƒÂ¥Ã‚Â±Ã¢â‚¬Å¡ */
    #loader {
      position: fixed; inset: 0;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      background: linear-gradient(180deg,#FFF6FA 0%, #FFE9F0 100%);
      z-index: 2000; opacity: 1; transition: opacity .3s ease;
    }
    #loader.fade-out{opacity:0;}
    #loader img{width:160px;animation:float 2s ease-in-out infinite;}
    #loader p{
      margin-top:18px;font-family:"Patrick Hand",cursive;
      color:#E5679C;font-size:1.2rem;letter-spacing:.5px;
      animation:fadeInText 1s ease-in-out infinite alternate;
    }
    @keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);}}
    @keyframes fadeInText{0%{opacity:.7;transform:scale(.98);}100%{opacity:1;transform:scale(1.02);} }

    /* ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¸ ÃƒÂ¥Ã‚Â¯Ã‚Â¼ÃƒÂ¨Ã‹â€ Ã‚ÂªÃƒÂ¦Ã‚Â Ã‚Â */
    .navbar {
      position: fixed; top: 0; left: 0; width: 100%;
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(10px);
      box-shadow: 0 2px 12px rgba(230,103,156,0.1);
      z-index: 1000;
    }
    .nav-container {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 6%; max-width: 1200px; margin: 0 auto;
    }
    .nav-logo img { height: 50px; border-radius: 50%; transition: transform 0.2s ease; }
    .nav-logo img:hover { transform: scale(1.05); }
    .nav-links a {
      text-decoration: none; color: #C94B82;
      margin: 0 14px; font-family: "Patrick Hand", cursive; font-size: 1.1rem;
      transition: color 0.3s;
    }
    .nav-links a:hover, .nav-links a.active { color: #E5679C; }

    body {
      margin: 0;
      font-family: "Patrick Hand", cursive;
      background-color: #FFF8FB;
     
    }

    main {
      padding-top: 80px; /* ÃƒÂ¦Ã‚Â­Ã‚Â£ÃƒÂ§Ã‚Â¡Ã‚Â®ÃƒÂ¤Ã‚Â½Ã‚ÂÃƒÂ§Ã‚Â½Ã‚Â® */
    }

    /* ÃƒÂ°Ã…Â¸Ã‚ÂÃ‚Â¬ ÃƒÂ¦Ã…Â½Ã¢â‚¬Â°ÃƒÂ¨Ã‚ÂÃ‚Â½ÃƒÂ§Ã‚Â³Ã¢â‚¬â€œÃƒÂ¦Ã…Â¾Ã…â€œ */
    .falling-sakura {
      position: fixed; top: 0; left: 0;
      width: 100%; height: 100%; pointer-events: none;
      z-index: 1500;
    }
    .sakura {
      position: absolute; top: -100px; width: 80px; opacity: 0.7;
      animation: sakuraFall 10s linear infinite;
    }
    @keyframes sakuraFall {
      0% { transform: translateY(0) rotate(0deg); opacity: 0; }
      10% { opacity: 1; }
      90% { transform: translateY(100vh) rotate(360deg); opacity: 1; }
      100% { transform: translateY(105vh) rotate(390deg); opacity: 0; }
    }

    /* ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â· ÃƒÂ¤Ã‚Â¸Ã‚Â»ÃƒÂ¦Ã‚Â Ã¢â‚¬Â¡ÃƒÂ©Ã‚Â¢Ã‹Å“ */
    .shop-header {
      width: 100%;
      padding: 30px 0 40px 0; /* ÃƒÂ©Ã¢â€šÂ¬Ã¢â‚¬Å¡ÃƒÂ¤Ã‚Â¸Ã‚Â­ÃƒÂ©Ã‚Â«Ã‹Å“ÃƒÂ¥Ã‚ÂºÃ‚Â¦ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¤Ã‚Â¸Ã‚ÂÃƒÂ¤Ã‚Â¼Ã…Â¡ÃƒÂ§Ã‹â€ Ã¢â‚¬Â ÃƒÂ¥Ã‚Â¼Ã¢â€šÂ¬ */
      background: linear-gradient(180deg, #FFD7E9 0%, #FFEFF5 100%);
      text-align: center;

      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      margin: 0;      /* ÃƒÂ¢Ã‚ÂÃ¢â‚¬â€ ÃƒÂ©Ã‹Å“Ã‚Â²ÃƒÂ¦Ã‚Â­Ã‚Â¢ÃƒÂ¨Ã‚Â¢Ã‚Â«ÃƒÂ¤Ã‚Â¸Ã…Â ÃƒÂ¤Ã‚Â¸Ã¢â‚¬Â¹ margin ÃƒÂ¦Ã…Â½Ã‚Â¨ÃƒÂ¥Ã‚Â¼Ã¢â€šÂ¬ */
      border: 0;      /* ÃƒÂ¢Ã‚ÂÃ¢â‚¬â€ ÃƒÂ¦Ã‚Â¸Ã¢â‚¬Â¦ÃƒÂ¦Ã…Â½Ã¢â‚¬Â°ÃƒÂ¦Ã¢â‚¬Å¾Ã‚ÂÃƒÂ¥Ã‚Â¤Ã¢â‚¬â€œÃƒÂ¨Ã‚Â¾Ã‚Â¹ÃƒÂ¦Ã‚Â¡Ã¢â‚¬Â  */
    }
    .shop-header img {
      width: 150px;  /* ÃƒÂ¨Ã‚Â°Ã†â€™ÃƒÂ¦Ã¢â‚¬Â¢Ã‚Â´ÃƒÂ¦Ã‹â€ Ã‚ÂÃƒÂ¥Ã‚ÂÃ‚Â¯ÃƒÂ§Ã‹â€ Ã‚Â±ÃƒÂ§Ã…Â¡Ã¢â‚¬Å¾ÃƒÂ¥Ã‚Â¤Ã‚Â§ÃƒÂ¥Ã‚Â°Ã‚Â */
      height: auto;
      margin-bottom: 10px;
      animation: floatUp 3s ease-in-out infinite;
    }
    .shop-header h1 {
      font-size: 32px;
      color: #E44B87;
      font-weight: bold;
      margin: 0;
    }
    @keyframes floatUp {0%,100%{transform:translateY(0);}50%{transform:translateY(-6px);} }

    /* ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¸ ÃƒÂ¤Ã‚Â¸Ã‚Â»ÃƒÂ¤Ã‚Â½Ã¢â‚¬Å“ÃƒÂ¥Ã‚Â¸Ã†â€™ÃƒÂ¥Ã‚Â±Ã¢â€šÂ¬ÃƒÂ¯Ã‚Â¼Ã…Â¡ÃƒÂ¦Ã¢â‚¬Â°Ã¢â‚¬Â¹ÃƒÂ¦Ã…â€œÃ‚ÂºÃƒÂ¤Ã‚Â¹Ã…Â¸ÃƒÂ¥Ã‚Â·Ã‚Â¦ÃƒÂ¥Ã‚ÂÃ‚Â³ÃƒÂ¥Ã‚Â¹Ã‚Â¶ÃƒÂ¦Ã…Â½Ã¢â‚¬â„¢ */
.shop-layout {
  display: grid;
  grid-template-columns: 180px 1fr;
  width: 95%;
  max-width: 1200px;
  margin: 30px auto;
  gap: 15px;
   align-items: start;   /* ÃƒÂ¢Ã‚Â­Ã‚Â ÃƒÂ¤Ã‚Â½Ã‚Â ÃƒÂ¥Ã‚Â·Ã‚Â²ÃƒÂ§Ã‚Â»Ã‚ÂÃƒÂ¥Ã…Â Ã‚Â ÃƒÂ¤Ã‚ÂºÃ¢â‚¬Â ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¤Ã‚Â½Ã¢â‚¬Â ÃƒÂ¤Ã‚Â¸Ã‚ÂÃƒÂ¥Ã‚Â¤Ã…Â¸ */
  min-height: auto;    /* ÃƒÂ¢Ã‚Â­Ã‚Â ÃƒÂ¨Ã‚Â¿Ã¢â€žÂ¢ÃƒÂ¤Ã‚Â¸Ã‚ÂªÃƒÂ¥Ã‚Â¿Ã¢â‚¬Â¦ÃƒÂ©Ã‚Â¡Ã‚Â»ÃƒÂ¥Ã…Â Ã‚Â  */
}

/* ÃƒÂ¥Ã‚Â·Ã‚Â¦ÃƒÂ¤Ã‚Â¾Ã‚Â§ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ§Ã‚Â±Ã‚Â»ÃƒÂ¦Ã‚Â Ã‚ÂÃƒÂ¯Ã‚Â¼Ã‹â€ ÃƒÂ¦Ã¢â‚¬â€Ã‚Â ÃƒÂ¥Ã‚Â­Ã‚ÂÃƒÂ¨Ã‚ÂÃ…â€œÃƒÂ¥Ã‚ÂÃ¢â‚¬Â¢ÃƒÂ§Ã¢â‚¬Â°Ã‹â€ ÃƒÂ¦Ã…â€œÃ‚Â¬ÃƒÂ¯Ã‚Â¼Ã¢â‚¬Â° */
.sidebar {
  background: #fff;
  padding: 12px;
  border-radius: 15px;
  border: 2px solid #f6bdd9;
  box-shadow: 0 4px 12px rgba(240,150,180,0.15);

  position: sticky;
  top: 100px; /* ÃƒÂ¥Ã‚ÂÃ‚Â¯ÃƒÂ¦Ã…â€™Ã¢â‚¬Â°ÃƒÂ©Ã…â€œÃ¢â€šÂ¬ÃƒÂ¥Ã‚Â¾Ã‚Â®ÃƒÂ¨Ã‚Â°Ã†â€™ÃƒÂ¥Ã‚ÂÃ‚Â¸ÃƒÂ©Ã‚Â¡Ã‚Â¶ÃƒÂ¨Ã‚Â·Ã‚ÂÃƒÂ§Ã‚Â¦Ã‚Â» */
  max-height: calc(100vh - 140px);
  overflow-y: auto;
}

.sidebar h3 {
  color: #e5679c;
  text-align: center;
  font-size: 1.1rem;
  margin-bottom: 10px;
}
.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.sidebar li {
  background: #fff;
  border-radius: 15px;
  padding: 10px;
  text-align: center;
  font-size: 0.95rem;
  color: #c94b82;
  cursor: pointer;
  border: 1px solid #f6bdd9;
  transition: all 0.25s ease;
}
.sidebar li:hover { background: #ffe9f0; transform: scale(1.03); color: #e5679c; }
.sidebar li.active { background: #f6bdd9; color: white; transform: scale(1.05); }

/* ÃƒÂ¥Ã‚ÂÃ‚Â³ÃƒÂ¤Ã‚Â¾Ã‚Â§ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¥Ã…â€™Ã‚Âº */
.product-area {
  display: flex;
  flex-direction: column;
  gap: 15px;
}
.product-card {
  position: relative;
  background: #fff;
  border-radius: 18px;
  border: 1px solid #fad2e1;
  box-shadow: 0 4px 10px rgba(240,150,180,0.15);
  padding: 15px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  transition: transform 0.25s ease;
}
.product-card:hover { transform: translateY(-3px); }
.favorite-btn {
  position:absolute;
  top:10px;
  right:10px;
  z-index:8;
  width:38px;
  height:38px;
  display:grid;
  place-items:center;
  padding:0;
  border:1px solid #f5bfd5;
  border-radius:50%;
  background:rgba(255,255,255,.94);
  color:#d8a1b8;
  font-size:17px;
  cursor:pointer;
  box-shadow:0 5px 14px rgba(218,74,139,.12);
}
.favorite-btn:hover,
.favorite-btn.active {
  color:#ed2f87;
  background:#fff0f7;
  border-color:#ed78ab;
}
.brand-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  z-index: 3;
  max-width: calc(100% - 20px);
  padding: 5px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, .92);
  border: 1px solid #ffc6dd;
  color: #e44b87;
  font-family: Arial, "Microsoft YaHei", sans-serif;
  font-size: 11px;
  font-weight: 800;
  line-height: 1.2;
  box-shadow: 0 6px 16px rgba(230, 75, 135, .16);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.product-card img {
  width: 80px;
  height: 80px;
  border-radius: 10px;
  border: 1px solid #f6bdd9;
  object-fit: cover;
}
.product-info {
  flex: 1;
  margin-left: 15px;
  text-align: left;
}
.product-info h4 {
  color: #e5679c;
  margin: 0 0 5px;
  font-size: 1rem;
}
.product-info p {
  margin: 2px 0;
  font-size: 0.85rem;
  color: #666;
}
.product-info .price {
  color: #c94b82;
  font-weight: bold;
  font-size: 0.95rem;
}
.add-btn {
  background: #f6bdd9;
  border: none;
  border-radius: 20px;
  padding: 6px 12px;
  color: white;
  font-family: "Patrick Hand", cursive;
  cursor: pointer;
  transition: all .3s ease;
}
.add-btn:hover { background: #e5679c; transform: scale(1.05); }

/* ÃƒÂ¦Ã¢â‚¬Â°Ã¢â‚¬Â¹ÃƒÂ¦Ã…â€œÃ‚ÂºÃƒÂ§Ã‚Â«Ã‚Â¯ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ§Ã‚Â±Ã‚Â»ÃƒÂ¦Ã¢â‚¬ÂÃ‚Â¹ÃƒÂ¤Ã‚Â¸Ã‚ÂºÃƒÂ¦Ã‚Â¨Ã‚ÂªÃƒÂ¥Ã‚ÂÃ¢â‚¬ËœÃƒÂ¦Ã‚Â»Ã…Â¡ÃƒÂ¥Ã…Â Ã‚Â¨ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¥Ã…â€™Ã‚ÂºÃƒÂ¤Ã‚Â¿Ã‚ÂÃƒÂ¦Ã…â€™Ã‚ÂÃƒÂ¥Ã‚ÂÃ‚Â¯ÃƒÂ¨Ã‚Â¯Ã‚Â»ÃƒÂ¥Ã‚Â®Ã‚Â½ÃƒÂ¥Ã‚ÂºÃ‚Â¦ */
@media (max-width: 768px) {
  .shop-layout {
    grid-template-columns: 1fr;
    width: 94%;
    gap: 12px;
  }
  .sidebar {
    position: sticky;
    top: 0;
    max-height: none;
    overflow-x: auto;
    overflow-y: hidden;
    margin-bottom: 15px;
    z-index: 30;
    padding: 10px;
  }
  .sidebar h3 {
    display: none;
  }
  .sidebar ul {
    flex-direction: row;
    gap: 8px;
    min-width: max-content;
  }
  .sidebar li {
    font-size: 0.9rem;
    padding: 8px 12px;
    white-space: nowrap;
  }
  .product-card {
    display: grid;
    grid-template-columns: 96px 1fr;
    align-items: center;
    gap: 12px;
    padding: 12px;
  }
  .product-card img {
    width: 96px;
    height: 96px;
    margin-bottom: 0;
  }
  .product-info {
    margin: 0;
    text-align: left;
  }
  .choose-btn,
  .add-btn {
    grid-column: 1 / -1;
    width: 100%;
    margin-top: 4px;
  }
}

    /* ÃƒÂ°Ã…Â¸Ã‚Â©Ã‚Â· ÃƒÂ¨Ã‚Â´Ã‚Â­ÃƒÂ§Ã¢â‚¬Â°Ã‚Â©ÃƒÂ¨Ã‚Â¢Ã¢â‚¬Â¹ÃƒÂ¥Ã‚Â¼Ã‚Â¹ÃƒÂ§Ã‚ÂªÃ¢â‚¬â€ */
    .cart-modal {
      display:none; position:fixed; inset:0;
      background:rgba(255,230,240,0.4); backdrop-filter:blur(10px);
      z-index:3000; justify-content:center; align-items:center;
      animation:fadeIn .4s ease;
    }
    .cart-box {
      position:relative; width:420px; max-width:90%;
      background:linear-gradient(180deg,#FFF6FA 0%,#FFE9F0 100%);
      border-radius:25px; padding:35px 25px 25px;
      text-align:center; color:#C94B82;
      box-shadow:0 6px 20px rgba(230,103,156,0.2);
      overflow:hidden; animation:popUp .5s ease;
    }
    .cart-girl {
      position:absolute; top:45%; left:50%;
      transform:translate(-50%,-190px);
      width:120px; pointer-events:none; z-index:3100;
      animation:floatGirl 2.5s ease-in-out infinite;
    }
    .close-btn {
      position:absolute; right:16px; top:12px;
      font-size:22px; color:#E5679C; cursor:pointer; transition:.3s;
    }
    .close-btn:hover{color:#C94B82;}
    @keyframes floatGirl{0%,100%{transform:translate(-50%,-190px);}50%{transform:translate(-50%,-198px);}}
    @keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
    @keyframes popUp{from{transform:scale(.95);opacity:0;}to{transform:scale(1);opacity:1;}}
  </style>
  <style>
    /* SOLD OUT ÃƒÂ¥Ã¢â‚¬Â Ã¢â‚¬Â¦ÃƒÂ§Ã‚Â½Ã‚Â®ÃƒÂ¦Ã‚Â Ã¢â‚¬Â¡ÃƒÂ§Ã‚Â­Ã‚Â¾ÃƒÂ¦Ã‚Â Ã‚Â·ÃƒÂ¥Ã‚Â¼Ã‚Â */
    .soldout-tag {
      position: absolute;
      top: 6px;
      left: 6px;
      background: #e5679c;
      color: #fff;
      font-size: 0.8rem;
      font-family: "Patrick Hand", cursive;
      padding: 3px 8px;
      border-radius: 6px;
      transform: rotate(-8deg);
      opacity: 0.9;
      z-index: 20;
      box-shadow: 0 2px 6px rgba(229,103,156,0.3);
    }
    /* ÃƒÂ°Ã…Â¸Ã¢â‚¬â€œÃ‚Â¼ÃƒÂ¯Ã‚Â¸Ã‚Â ÃƒÂ§Ã‚ÂºÃ‚Â¯ÃƒÂ©Ã‚Â¢Ã¢â‚¬Å¾ÃƒÂ¨Ã‚Â§Ã‹â€ ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¤Ã‚Â¸Ã‚ÂÃƒÂ¥Ã‚Â½Ã‚Â±ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¤Ã‚Â½Ã‚Â ÃƒÂ¥Ã…Â½Ã…Â¸ÃƒÂ¦Ã…â€œÃ‚Â¬ÃƒÂ¥Ã¢â‚¬ÂºÃ‚Â¾ÃƒÂ§Ã¢â‚¬Â°Ã¢â‚¬Â¡ÃƒÂ¤Ã‚Â¸Ã…Â ÃƒÂ¤Ã‚Â¼Ã‚Â  / ÃƒÂ¦Ã‹Å“Ã‚Â¾ÃƒÂ§Ã‚Â¤Ã‚Âº */
.img-preview-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.65);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 5000;
}

.img-preview-modal img {
  max-width: 90%;
  max-height: 90vh;
  border-radius: 10px;
  animation: zoomIn .25s ease;
}

@keyframes zoomIn {
  from { transform: scale(0.85); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}

/* ============================================
   ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¸ Qii.shoppp ÃƒÂ¥Ã¢â‚¬Â¦Ã‚Â¨ÃƒÂ§Ã‚Â«Ã¢â€žÂ¢ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ§Ã‚Â²Ã¢â‚¬Â°ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â²ÃƒÂ¤Ã‚Â¸Ã‚Â»ÃƒÂ©Ã‚Â¢Ã‹Å“ÃƒÂ¯Ã‚Â¼Ã‹â€ ÃƒÂ¦Ã¢â‚¬Â¢Ã‚Â´ÃƒÂ¥Ã‚Â¥Ã¢â‚¬â€ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ¯Ã‚Â¼Ã¢â‚¬Â°
   ============================================ */

/* ÃƒÂ¦Ã¢â‚¬Â¢Ã‚Â´ÃƒÂ¤Ã‚Â¸Ã‚ÂªÃƒÂ§Ã‚Â½Ã¢â‚¬ËœÃƒÂ§Ã‚Â«Ã¢â€žÂ¢ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ¨Ã†â€™Ã…â€™ÃƒÂ¦Ã¢â€žÂ¢Ã‚Â¯ */
html, body {
  background: linear-gradient(180deg, #FFE6F0 0%, #FFF4FA 100%) !important;
}

/* ÃƒÂ©Ã‚Â¡Ã‚Â¶ÃƒÂ©Ã†â€™Ã‚Â¨ Banner ÃƒÂ©Ã‚Â¢Ã…â€œÃƒÂ¨Ã¢â‚¬Â°Ã‚Â²ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ */
.shop-header {
  background: linear-gradient(180deg, #FFD1E3 0%, #FFE6F2 100%) !important;
}

/* ÃƒÂ¤Ã‚Â¸Ã‚Â­ÃƒÂ©Ã¢â‚¬â€Ã‚Â´ÃƒÂ¦Ã¢â‚¬Â¢Ã‚Â´ÃƒÂ¤Ã‚Â½Ã¢â‚¬Å“ÃƒÂ¥Ã…â€™Ã‚ÂºÃƒÂ¥Ã…Â¸Ã…Â¸ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ§Ã‚Â²Ã¢â‚¬Â°ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â²ÃƒÂ¥Ã‚ÂÃ‚Â¡ÃƒÂ§Ã¢â‚¬Â°Ã¢â‚¬Â¡ */
.shop-layout > div {
  background: linear-gradient(180deg, #FFF0F7 0%, #FFE6F0 100%) !important;
  padding: 25px !important;
  border-radius: 25px !important;
  border: 2px solid #F8C9DA !important;
  box-shadow: 0 4px 10px rgba(240,150,180,0.15) !important;
}

/* ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ§Ã‚Â±Ã‚Â»ÃƒÂ¦Ã‚Â Ã‚ÂÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ¥Ã‚ÂÃ…â€™ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â² */
.sidebar {
  background: linear-gradient(180deg, #FFF0F7 0%, #FFE6F0 100%) !important;
  border: 2px solid #F8C9DA !important;
  box-shadow: 0 4px 10px rgba(240,150,180,0.15) !important;
}

.sidebar li {
  background: #FFE6F0 !important;
  border: 1px solid #F6BDD9 !important;
  color: #C94B82 !important;
}
.sidebar li.active {
  background: #F9B8CF !important;
  color: white !important;
}
.sidebar li:hover {
  background: #FCD1E2 !important;
}

/* ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¥Ã‚ÂÃ‚Â¡ÃƒÂ§Ã¢â‚¬Â°Ã¢â‚¬Â¡ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ¥Ã‚ÂÃ…â€™ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â² */
.product-card {
  background: #FFF6FA !important;
  border: 2px solid #F8C9DA !important;
  box-shadow: 0 4px 10px rgba(240,150,180,0.15) !important;
}

/* ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¦Ã¢â‚¬â€œÃ¢â‚¬Â¡ÃƒÂ¥Ã‚Â­Ã¢â‚¬â€ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ */
.product-info h4 {
  color: #E44B87 !important;
}
.product-info p {
  color: #C2799B !important;
}

/* ÃƒÂ¤Ã‚Â»Ã‚Â·ÃƒÂ¦Ã‚Â Ã‚Â¼ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ§Ã‚Â²Ã¢â‚¬Â°ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â² */
.product-info .price {
  color: #E44B87 !important;
}

/* ÃƒÂ©Ã¢â€šÂ¬Ã¢â‚¬Â°ÃƒÂ¦Ã¢â‚¬Â¹Ã‚Â©ÃƒÂ¨Ã‚Â§Ã¢â‚¬Å¾ÃƒÂ¦Ã‚Â Ã‚Â¼ÃƒÂ¦Ã…â€™Ã¢â‚¬Â°ÃƒÂ©Ã¢â‚¬â„¢Ã‚Â®ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ§Ã‚Â²Ã¢â‚¬Â°ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â² */
.choose-btn {
  background: linear-gradient(180deg, #FFBBD4, #FF9EC5) !important;
  border: none !important;
  padding: 8px 16px !important;
  border-radius: 20px !important;
  color: #fff !important;
  font-family: "Patrick Hand", cursive !important;
  box-shadow: 0 4px 10px rgba(230,103,156,.25) !important;
}
.choose-btn:hover {
  background: linear-gradient(180deg, #FF9EC5, #FF8AB8) !important;
  transform: scale(1.07) !important;
}

/* SOLD OUT ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ§Ã‚Â²Ã¢â‚¬Â°ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â² */
.soldout-tag {
  background: #E57597 !important;
}

/* ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ§Ã‚Â±Ã‚Â»ÃƒÂ¦Ã‚Â Ã¢â‚¬Â¡ÃƒÂ©Ã‚Â¢Ã‹Å“ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ§Ã‚Â²Ã¢â‚¬Â°ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â² */
.category-title {
  color: #E44B87 !important;
}

/* ------------------------------------------
   ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¸ Variant Modal ÃƒÂ¨Ã‚Â§Ã¢â‚¬Å¾ÃƒÂ¦Ã‚Â Ã‚Â¼ÃƒÂ¥Ã‚Â¼Ã‚Â¹ÃƒÂ§Ã‚ÂªÃ¢â‚¬â€ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ§Ã‚Â²Ã¢â‚¬Â°ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â²
   ------------------------------------------ */
#variantModal {
  background: rgba(255, 210, 230, 0.55) !important;
}
#variantModal .modal-box {
  background: linear-gradient(180deg,#FFF0F7 0%, #FFE1EE 100%) !important;
  border: 2px solid #F6BDD9 !important;
  box-shadow: 0 6px 20px rgba(230,103,156,0.35) !important;
}

/* ÃƒÂ¥Ã‚Â¼Ã‚Â¹ÃƒÂ§Ã‚ÂªÃ¢â‚¬â€ÃƒÂ¦Ã‚Â Ã¢â‚¬Â¡ÃƒÂ©Ã‚Â¢Ã‹Å“ÃƒÂ¦Ã¢â‚¬â€œÃ¢â‚¬Â¡ÃƒÂ¥Ã‚Â­Ã¢â‚¬â€ */
#modalName {
  color: #E44B87 !important;
}
#modalPrice, #modalStock {
  color: #C94B82 !important;
}

/* ÃƒÂ¨Ã‚Â§Ã¢â‚¬Å¾ÃƒÂ¦Ã‚Â Ã‚Â¼ÃƒÂ¦Ã…â€™Ã¢â‚¬Â°ÃƒÂ©Ã¢â‚¬â„¢Ã‚Â®ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ */
#variantBox button {
  background: #FFE0ED !important;
  border: 1px solid #F3ABC7 !important;
  color: #C94B82 !important;
}
#variantBox button:hover {
  background: #FFC5DD !important;
}
#variantBox button.active {
  background: #FF9BC8 !important;
  color: #fff !important;
}

/* ÃƒÂ¥Ã…Â Ã‚Â ÃƒÂ¥Ã¢â‚¬Â¦Ã‚Â¥ÃƒÂ¨Ã‚Â´Ã‚Â­ÃƒÂ§Ã¢â‚¬Â°Ã‚Â©ÃƒÂ¨Ã‚Â½Ã‚Â¦ÃƒÂ¦Ã…â€™Ã¢â‚¬Â°ÃƒÂ©Ã¢â‚¬â„¢Ã‚Â®ÃƒÂ§Ã‚Â»Ã…Â¸ÃƒÂ¤Ã‚Â¸Ã¢â€šÂ¬ÃƒÂ§Ã‚Â²Ã¢â‚¬Â°ÃƒÂ¨Ã¢â‚¬Â°Ã‚Â² */
#addVariantBtn {
  background: linear-gradient(180deg,#FFB5D3,#FF95C2) !important;
  color: white !important;
  border: none !important;
  border-radius: 20px !important;
  box-shadow: 0 4px 10px rgba(255,105,155,.3) !important;
}
#addVariantBtn:hover {
  background: linear-gradient(180deg,#FF95C2,#FF7FB2) !important;
  transform: scale(1.05) !important;
}

/* ÃƒÂ¥Ã¢â‚¬Â¦Ã‚Â³ÃƒÂ©Ã¢â‚¬â€Ã‚Â­ÃƒÂ¦Ã…â€™Ã¢â‚¬Â°ÃƒÂ©Ã¢â‚¬â„¢Ã‚Â® */
#closeVariantModal {
  color: #E5679C !important;
}
#closeVariantModal:hover {
  color: #C94B82 !important;
}

.mobile-shop-top,
.mobile-bottom-nav {
  display: none;
}

@media (max-width: 768px) {
  body {
    background: #fff4f8;
    padding-bottom: 20px;
  }

  .site-header,
  .shop-header,
  #loader {
    display: none !important;
  }

  main {
    padding-top: 0;
  }

  .mobile-shop-top {
    display: block;
    padding: 12px 14px 0;
  }

  .mobile-search {
    display: flex;
    align-items: center;
    gap: 10px;
    height: 46px;
    padding: 0 16px;
    background: rgba(255,255,255,0.92);
    border: 1px solid #f8d7e4;
    border-radius: 999px;
    box-shadow: 0 8px 20px rgba(229, 103, 156, 0.08);
  }

  .mobile-search span {
    color: #e44b87;
    font-size: 20px;
    line-height: 1;
  }

  .mobile-search input {
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    color: #5d4b55;
    font-size: 14px;
  }

  .mobile-promo {
    position: relative;
    overflow: hidden;
    min-height: 142px;
    margin-top: 14px;
    padding: 24px 18px;
    border-radius: 22px;
    border: 1px solid #ffd5e4;
    background:
      radial-gradient(circle at 72% 28%, rgba(255,255,255,.82) 0 16%, transparent 17%),
      linear-gradient(105deg, #ffcde0 0%, #ffdce9 45%, #fff1f7 100%);
    box-shadow: 0 10px 24px rgba(229, 103, 156, 0.12);
  }

  .mobile-promo h2 {
    position: relative;
    z-index: 2;
    margin: 0 0 8px;
    max-width: 58%;
    color: #ed3d89;
    font-size: 22px;
    line-height: 1.18;
    letter-spacing: 0;
  }

  .mobile-promo p {
    position: relative;
    z-index: 2;
    margin: 0 0 16px;
    color: #68485a;
    font-size: 13px;
  }

  .mobile-promo a {
    position: relative;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 0 18px;
    border-radius: 999px;
    background: #f5368d;
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    box-shadow: 0 8px 18px rgba(245, 54, 141, .24);
  }

  .mobile-promo img {
    position: absolute;
    right: -2px;
    bottom: -12px;
    width: 48%;
    max-height: 150px;
    object-fit: contain;
    filter: drop-shadow(0 10px 16px rgba(200, 80, 130, .18));
  }



  .shop-layout {
    display: block;
    width: auto;
    margin: 14px 12px 0;
  }

  .shop-layout > div {
    padding: 14px !important;
    border-radius: 22px !important;
    background: rgba(255,255,255,.72) !important;
    border: 1px solid #f8d7e4 !important;
    box-shadow: 0 10px 24px rgba(229, 103, 156, 0.08) !important;
  }

  .sidebar {
    position: relative;
    top: auto;
    margin: 0 0 16px;
    padding: 8px 8px 10px !important;
    border-radius: 20px !important;
    overflow-x: auto;
    overflow-y: hidden;
    background: rgba(255,255,255,.86) !important;
    box-shadow: 0 8px 20px rgba(229,103,156,.08) !important;
    scrollbar-width: thin;
  }

  .sidebar h3 {
    display: none;
  }

  .sidebar ul {
    display: flex;
    flex-direction: row;
    gap: 9px;
    min-width: max-content;
    margin: 0;
    padding: 0;
  }

  .sidebar li {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    min-width: 72px;
    min-height: 60px;
    padding: 8px 10px;
    border-radius: 16px !important;
    white-space: nowrap;
    color: #6a5861 !important;
    font-size: 11px;
    line-height: 1.15;
    background: #fff7fb !important;
  }

  .sidebar li.active {
    background: #ffc4df !important;
    color: #e43f88 !important;
    box-shadow: inset 0 0 0 2px #ff8fbd;
  }

  .cat-emoji {
    display: block;
    font-size: 19px;
    line-height: 1;
  }

  .cat-name {
    display: block;
    font-size: 11px;
    line-height: 1.15;
  }

  .category-title {
    margin: 0 0 14px;
    color: #e43f88 !important;
    font-size: 20px;
    font-weight: 800;
    text-align: left;
  }

  .category-title::before {
    content: "";
  }

  .product-area {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }

  .product-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    min-width: 0;
    min-height: 260px;
    padding: 0 !important;
    overflow: hidden;
    border-radius: 18px !important;
    background: #fff !important;
    border: 1px solid #f8d7e4 !important;
    box-shadow: 0 8px 18px rgba(229,103,156,.1) !important;
  }

  .product-card:hover {
    transform: none;
  }

  .product-card img {
    width: 100%;
    height: auto;
    aspect-ratio: 1.04 / 1;
    margin: 0;
    border: 0;
    border-radius: 0;
    object-fit: cover;
  }

  .product-info {
    margin: 0;
    padding: 10px 10px 54px;
    text-align: left;
  }

  .product-info h4 {
    display: -webkit-box;
    min-height: 32px;
    margin: 0 0 4px;
    overflow: hidden;
    color: #42343d !important;
    font-family: Arial, "Microsoft YaHei", sans-serif;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.32;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    word-break: break-word;
  }

  .product-info p {
    margin: 0 0 5px;
    color: #8d7c85 !important;
    font-family: Arial, "Microsoft YaHei", sans-serif;
    font-size: 10px;
    line-height: 1.2;
  }

  .product-info .price {
    color: #f5368d !important;
    font-family: Arial, "Microsoft YaHei", sans-serif;
    font-size: 15px;
    font-weight: 800;
  }

  .choose-btn,
  .add-btn {
    position: absolute;
    right: 12px;
    bottom: 12px;
    width: 38px;
    min-width: 38px;
    height: 38px;
    min-height: 38px;
    margin: 0;
    padding: 0 !important;
    border-radius: 50% !important;
    font-size: 0;
    background: #ffb6cf !important;
    box-shadow: 0 8px 18px rgba(245,54,141,.28) !important;
  }

  .choose-btn::before,
  .add-btn::before {
    content: "\1F6D2";
    font-size: 18px;
    line-height: 1;
  }

  .add-btn:disabled {
    width: auto;
    min-width: 54px;
    padding: 0 12px !important;
    color: #fff !important;
    font-size: 12px;
  }

  .add-btn:disabled::before {
    content: "";
  }

  .soldout-tag {
    top: 8px;
    left: 8px;
    z-index: 3;
    border-radius: 999px;
    transform: none;
  }

  .site-footer .footer-info {
    display: none;
  }

  .floating-buttons {
    right: 12px !important;
    bottom: 92px !important;
    z-index: 1900;
  }

  .floating-cart,
  .floating-speaker {
    width: 54px;
    height: 54px;
    font-size: 24px;
    transform: none !important;
    background: #fff;
  }

  .mobile-bottom-nav {
    position: fixed;
    left: 12px;
    right: 12px;
    bottom: 10px;
    z-index: 2100;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    min-height: 66px;
    padding: 8px 6px;
    border: 1px solid #f8d7e4;
    border-radius: 24px;
    background: rgba(255,255,255,.94);
    box-shadow: 0 -8px 24px rgba(229,103,156,.14);
    backdrop-filter: blur(12px);
  }

  .mobile-bottom-nav a {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    color: #5c4d55;
    text-decoration: none;
    font-size: 12px;
  }

  .mobile-bottom-nav .nav-icon {
    font-size: 22px;
    line-height: 1;
  }

  .mobile-bottom-nav a.active {
    color: #f5368d;
    font-weight: 700;
  }
}

  
@media (max-width: 768px) {
  body {
    background: linear-gradient(180deg, #fff5fa 0%, #ffeef6 100%) !important;
  }
  .mobile-shop-top {
    padding: 8px 16px 0;
  }
  .mobile-search {
    height: 44px;
    background: rgba(255,255,255,.94);
    border-color: #f7d9e5;
    box-shadow: 0 6px 16px rgba(224,91,148,.08);
  }
  .mobile-search span {
    font-size: 22px;
  }
  .mobile-search input::placeholder {
    color: #9b8b93;
  }
  .mobile-promo {
    min-height: 152px;
    margin-top: 12px;
    border-radius: 20px;
    padding: 24px 18px;
    background:
      linear-gradient(90deg, rgba(255,201,224,.96) 0%, rgba(255,218,234,.9) 46%, rgba(255,242,248,.72) 100%),
      url('images/11.png') right center / contain no-repeat;
  }
  .mobile-promo::after {
    content: "";
    position: absolute;
    right: 14px;
    top: 16px;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: rgba(255,255,255,.56);
  }
  .mobile-promo h2 {
    max-width: 62%;
    font-size: 23px;
    color: #f13987;
    text-shadow: 0 1px 0 rgba(255,255,255,.75);
  }
  .mobile-promo p {
    color: #5f4653;
    font-size: 13px;
  }
  .mobile-promo a {
    min-height: 32px;
    padding: 0 16px;
    background: #f5368d;
    font-weight: 700;
  }
  .mobile-promo img {
    right: 6px;
    bottom: 6px;
    width: 42%;
    max-height: 126px;
    z-index: 1;
  }
  .shop-layout {
    margin: 12px 14px 0;
  }
  .sidebar {
    margin-bottom: 14px;
    border-radius: 20px !important;
    background: rgba(255,255,255,.9) !important;
  }
  .sidebar ul {
    gap: 10px;
  }
  .sidebar li {
    min-width: 70px;
    min-height: 66px;
    flex-direction: column;
    gap: 5px;
    font-size: 12px;
    box-shadow: 0 4px 12px rgba(224,91,148,.06);
  }
  .sidebar li.active {
    background: #ffc9df !important;
    box-shadow: inset 0 0 0 1px #ff9fc5;
  }
  .shop-layout > div {
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
  }
  .category-title {
    margin: 16px 4px 12px;
    font-size: 20px;
    color: #f13987 !important;
  }
  .product-area {
    gap: 14px;
  }
  .product-card {
    border-radius: 16px !important;
    border: 1px solid #f9d7e5 !important;
    box-shadow: 0 8px 18px rgba(223,93,150,.09) !important;
  }
  .product-card img {
    aspect-ratio: 1 / .82;
  }
  .product-info {
    padding: 10px 10px 0;
  }
  .product-info h4 {
    min-height: 34px;
    font-size: 13px;
    font-weight: 700;
  }
  .product-info p {
    font-size: 11px;
  }
  .product-info .price {
    font-size: 17px;
  }
  .choose-btn,
  .add-btn {
    width: 40px;
    min-width: 40px;
    height: 40px;
    min-height: 40px;
    margin: 0 10px 10px;
    border-radius: 50% !important;
    background: #ffb6cf !important;
  }
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
    <section class="mobile-shop-top" aria-label="æ‰‹æœºç«¯å•†åº—å…¥å£">

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
            <li class="cat-link <?= ($cat === $key) ? 'active' : '' ?>" data-cat="<?= htmlspecialchars($key) ?>">
              <?php if (!empty($categoryRows[$key]['emoji'])): ?>
                <span class="cat-emoji"><?= htmlspecialchars($categoryRows[$key]['emoji']) ?></span>
              <?php endif; ?>
              <span class="cat-name"><?= htmlspecialchars($categoryRows[$key]['name'] ?? $key) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>

  <!-- ÃƒÂ¥Ã‚ÂÃ‚Â³ÃƒÂ¤Ã‚Â¾Ã‚Â§ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¥Ã‚Â±Ã¢â‚¬Â¢ÃƒÂ§Ã‚Â¤Ã‚Âº -->
  <div>
    <h2 class="category-title"><?= $categories[$cat] ?? $categories['phone'] ?></h2>
    <div class="product-area">
      <?php if (empty($products)): ?>
    <p style="text-align:center;color:#999;">No products yet.</p>
  <?php else: ?>
    <?php foreach ($products as $p): ?>
      <div class="product-card" data-aos="fade-up">
        <?php if (!empty($p['brand'])): ?>
          <div class="brand-badge"><?= htmlspecialchars(qii_text($p['brand'])) ?></div>
        <?php endif; ?>
        <?php if ($p['stock'] <= 0): ?>
          <div class="soldout-tag">SOLD OUT</div>
        <?php endif; ?>
        <img src="<?= htmlspecialchars(qii_asset_path($p['image_url'] ?? '')) ?>" alt="<?= htmlspecialchars(qii_text($p['name'])) ?>">
        <div class="product-info">
          <h4><?= htmlspecialchars(qii_text($p['name'])) ?></h4>
          <?php if (!empty($p['sku'])): ?><p>SKU: <?= htmlspecialchars($p['sku']) ?></p><?php endif; ?>
          <div class="price">RM <?= number_format($p['price'], 2) ?></div>
        </div>
        <?php if ($p['stock'] > 0): ?>
          <button onclick='openVariantModal(<?= qii_product_payload($p) ?>)' class="choose-btn" aria-label="Add to cart"></button>
        <?php else: ?>
          <button class="add-btn" disabled>Sold out</button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
    </div>
  </div>
</section>
  </main>

  <?php include __DIR__ . "/../includes/footer.php"; ?>
<!-- ÃƒÂ¢Ã…â€œÃ‚Â¨ JS ÃƒÂ©Ã†â€™Ã‚Â¨ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â  -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({ duration: 800, once: true });

    // Loader ÃƒÂ¥Ã…Â Ã‚Â¨ÃƒÂ§Ã¢â‚¬ÂÃ‚Â»
    window.addEventListener("load", () => {
      const loader = document.getElementById("loader");
      setTimeout(() => {
        loader.classList.add("fade-out");
        setTimeout(() => (loader.style.display = "none"), 600);
      }, 1800);
    });

    // ÃƒÂ¢Ã…â€œÃ¢â‚¬Â¦ ÃƒÂ§Ã¢â‚¬Å¡Ã‚Â¹ÃƒÂ¥Ã¢â‚¬Â¡Ã‚Â»ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ§Ã‚Â±Ã‚Â»ÃƒÂ¦Ã¢â‚¬â€Ã‚Â¶ÃƒÂ¥Ã…Â Ã‚Â¨ÃƒÂ¦Ã¢â€šÂ¬Ã‚ÂÃƒÂ¥Ã…Â Ã‚Â ÃƒÂ¨Ã‚Â½Ã‚Â½ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¯Ã‚Â¼Ã‹â€ ÃƒÂ¤Ã‚Â¸Ã‚ÂÃƒÂ¥Ã‹â€ Ã‚Â·ÃƒÂ¦Ã¢â‚¬â€œÃ‚Â°ÃƒÂ¦Ã¢â‚¬Â¢Ã‚Â´ÃƒÂ¤Ã‚Â¸Ã‚ÂªÃƒÂ©Ã‚Â¡Ã‚ÂµÃƒÂ©Ã‚ÂÃ‚Â¢ÃƒÂ¯Ã‚Â¼Ã¢â‚¬Â°
    document.addEventListener("DOMContentLoaded", () => {
      const categoryList = document.querySelectorAll(".cat-link");
      const productArea = document.querySelector(".product-area");
      const title = document.querySelector(".category-title");

      const defaultCat = <?= json_encode($cat, JSON_UNESCAPED_UNICODE) ?>;
      fetch(`shop.php?cat=${defaultCat}&ajax=1`)
        .then(res => res.text())
        .then(html => {
          if (productArea) productArea.innerHTML = html;
          const activeLi = document.querySelector(`[data-cat="${defaultCat}"]`);
          if (title && activeLi) title.textContent = activeLi.textContent.trim();
          categoryList.forEach(li => li.classList.remove("active"));
          if (activeLi) activeLi.classList.add("active");
        });

      // ÃƒÂ¢Ã…â€œÃ¢â‚¬Â¦ ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ§Ã‚Â±Ã‚Â»ÃƒÂ§Ã¢â‚¬Å¡Ã‚Â¹ÃƒÂ¥Ã¢â‚¬Â¡Ã‚Â»ÃƒÂ¥Ã‹â€ Ã¢â‚¬Â¡ÃƒÂ¦Ã‚ÂÃ‚Â¢
      categoryList.forEach((item) => {
        item.addEventListener("click", (e) => {
          e.preventDefault();

          categoryList.forEach(li => li.classList.remove("active"));
          item.classList.add("active");

          if (title) title.textContent = item.textContent.trim();

          const cat = item.dataset.cat;
          fetch(`shop.php?cat=${cat}&ajax=1`)
            .then(res => res.text())
            .then(html => {
              productArea.innerHTML = html;
            })
            .catch(err => {
              productArea.innerHTML = `<p style="color:#c94b82;text-align:center;">Load failed. Please try again.</p>`;
            });
        });
      });
    });

// ÃƒÂ¨Ã‚Â®Ã‚Â©ÃƒÂ¦Ã‚Â¯Ã‚ÂÃƒÂ¤Ã‚Â¸Ã‚ÂªÃƒÂ§Ã‚Â³Ã¢â‚¬â€œÃƒÂ¦Ã…Â¾Ã…â€œÃƒÂ¥Ã‹â€ Ã¢â‚¬Â ÃƒÂ¦Ã¢â‚¬Â¢Ã‚Â£ÃƒÂ©Ã…Â¡Ã‚ÂÃƒÂ¦Ã…â€œÃ‚ÂºÃƒÂ¤Ã‚Â½Ã‚ÂÃƒÂ§Ã‚Â½Ã‚Â®
document.querySelectorAll(".sakura").forEach((el, i) => {
  el.style.left = Math.random() * 100 + "vw";          
  el.style.animationDuration = 8 + Math.random()*6 + "s";
  el.style.animationDelay = Math.random()*3 + "s";
  el.style.opacity = 0.4 + Math.random() * 0.6;
});
// ÃƒÂ°Ã…Â¸Ã¢â‚¬â€œÃ‚Â¼ÃƒÂ¯Ã‚Â¸Ã‚Â ÃƒÂ§Ã¢â‚¬Å¡Ã‚Â¹ÃƒÂ¥Ã¢â‚¬Â¡Ã‚Â»ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¥Ã¢â‚¬ÂºÃ‚Â¾ÃƒÂ§Ã¢â‚¬Â°Ã¢â‚¬Â¡ ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ ÃƒÂ¦Ã¢â‚¬ÂÃ‚Â¾ÃƒÂ¥Ã‚Â¤Ã‚Â§ÃƒÂ©Ã‚Â¢Ã¢â‚¬Å¾ÃƒÂ¨Ã‚Â§Ã‹â€ 
document.addEventListener("click", function(e) {
  const favoriteButton = e.target.closest("[data-favorite-product]");
  if (favoriteButton) {
    e.preventDefault();
    e.stopPropagation();
    const token = document.querySelector('meta[name="qii-csrf-token"]')?.content || "";
    const body = new URLSearchParams({ product_id: favoriteButton.dataset.favoriteProduct });
    fetch("api/toggle_favorite.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-QII-CSRF-Token": token
      },
      body
    }).then(async response => {
      const data = await response.json();
      if (response.status === 401 || data.login_required) {
        location.href = "login.php?next=" + encodeURIComponent(location.pathname + location.search);
        return;
      }
      if (!data.success) throw new Error(data.message || "Favorite failed");
      favoriteButton.classList.toggle("active", data.favorite);
      const icon = favoriteButton.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-solid", data.favorite);
        icon.classList.toggle("fa-regular", !data.favorite);
      } else {
        favoriteButton.textContent = data.favorite ? "已收藏" : "收藏";
      }
    }).catch(() => {
      alert("收藏失败，请稍后再试。");
    });
    return;
  }

  // ÃƒÂ¥Ã‚ÂÃ‚ÂªÃƒÂ¦Ã¢â‚¬ÂÃ‚Â¾ÃƒÂ¥Ã‚Â¤Ã‚Â§ product-card ÃƒÂ§Ã…Â¡Ã¢â‚¬Å¾ÃƒÂ¥Ã¢â‚¬ÂºÃ‚Â¾ÃƒÂ§Ã¢â‚¬Â°Ã¢â‚¬Â¡ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¤Ã‚Â¸Ã‚ÂÃƒÂ¦Ã¢â‚¬ÂÃ‚Â¾ÃƒÂ¥Ã‚Â¤Ã‚Â§ header/footer/logo
  if (e.target.matches(".product-card img")) {
    const modal = document.getElementById("imgPreview");
    const modalImg = document.getElementById("imgPreviewPic");
    if (!modal || !modalImg) return;

    modalImg.src = e.target.src;  // ÃƒÂ§Ã¢â‚¬ÂÃ‚Â¨ÃƒÂ¤Ã‚Â½Ã‚Â ÃƒÂ§Ã…Â¡Ã¢â‚¬Å¾ÃƒÂ¥Ã…Â½Ã…Â¸ÃƒÂ¥Ã¢â‚¬ÂºÃ‚Â¾ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¤Ã‚Â¸Ã‚ÂÃƒÂ¦Ã¢â‚¬ÂÃ‚Â¹ÃƒÂ¨Ã‚Â·Ã‚Â¯ÃƒÂ¥Ã‚Â¾Ã¢â‚¬Å¾
    modal.style.display = "flex";
  }
});

// ÃƒÂ§Ã¢â‚¬Å¡Ã‚Â¹ÃƒÂ¥Ã¢â‚¬Â¡Ã‚Â»ÃƒÂ¨Ã†â€™Ã…â€™ÃƒÂ¦Ã¢â€žÂ¢Ã‚Â¯ÃƒÂ¥Ã¢â‚¬Â¦Ã‚Â³ÃƒÂ©Ã¢â‚¬â€Ã‚Â­ÃƒÂ©Ã‚Â¢Ã¢â‚¬Å¾ÃƒÂ¨Ã‚Â§Ã‹â€ 
document.getElementById("imgPreview").addEventListener("click", function() {
  this.style.display = "none";
});

</script>



<?php include __DIR__ . '/../components/variant_modal.php'; ?>

  <!-- ÃƒÂ°Ã…Â¸Ã…â€™Ã‚Â¸ Qii ÃƒÂ¥Ã‚ÂÃ‚Â¯ÃƒÂ§Ã‹â€ Ã‚Â±ÃƒÂ¦Ã‚ÂÃ‚ÂÃƒÂ§Ã‚Â¤Ã‚ÂºÃƒÂ¦Ã‚Â¡Ã¢â‚¬Â  -->
  <div id="qiiToast" class="qii-toast">ÃƒÂ¥Ã‚Â·Ã‚Â²ÃƒÂ¥Ã…Â Ã‚Â ÃƒÂ¥Ã¢â‚¬Â¦Ã‚Â¥ÃƒÂ¨Ã‚Â´Ã‚Â­ÃƒÂ§Ã¢â‚¬Â°Ã‚Â©ÃƒÂ¨Ã‚Â½Ã‚Â¦</div>

  <style>
    .qii-toast {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%) translateY(30px);
      background: linear-gradient(180deg,#FFB5D3,#FF95C2);
      color: white;
      padding: 14px 24px;
      border-radius: 18px;
      font-size: 15px;
      font-family: "Patrick Hand", cursive;
      box-shadow: 0 6px 14px rgba(255,120,160,0.35);
      opacity: 0;
      transition: all .35s ease;
      z-index: 6000;
      pointer-events: none;
    }
    .qii-toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }
  </style>

  <script>
    function qiiToast(msg) {
      const t = document.getElementById("qiiToast");
      if (!t) return;
      t.textContent = msg;
      t.classList.add("show");
      setTimeout(() => t.classList.remove("show"), 2000);
    }
  </script>

</body>
</html>
