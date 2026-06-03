<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); 
require_once __DIR__ . '/a9sd8f7sd9f_admin/config.php';

// 当前分类（默认第一个）
$cat = $_GET['cat'] ?? 'phone';


// 分类映射
$categories = [
  'phone'   => '📱 手机配件',
  'hair'    => '🎀 发夹发饰',
  'snack'   => '🍭 零食',
  'creative'=> '💗 文创',
  'case'    => '💖 手机壳',
  'nail'    => '💅 穿戴甲',
  'scent'   => '🌸 香片',
  'doll'    => '🧸 娃娃',
  'stationery' => '✏️ 文具'
];

// 查询该分类下的商品
$stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY sort_order ASC, created_at DESC");
$stmt->execute([$cat]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 如果是 AJAX 请求，只返回商品 HTML
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
  ob_start();
  if ($products) {
    foreach ($products as $p): ?>
      <div class="product-card">
        <?php if ($p['stock'] <= 0): ?>
          <div class="soldout-tag">SOLD OUT</div>
        <?php endif; ?>
        <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
        <div class="product-info">
          <h4><?= htmlspecialchars($p['name']) ?></h4>
          <?php if (!empty($p['sku'])): ?><p>编号：<?= htmlspecialchars($p['sku']) ?></p><?php endif; ?>
          <div class="price">RM <?= number_format($p['price'], 2) ?></div>
        </div>
        <?php if ($p['stock'] > 0): ?>
          <button onclick='openVariantModal({
            id:"<?= $p['id'] ?>",
            name:"<?= htmlspecialchars($p["name"], ENT_QUOTES) ?>",
            price:"<?= $p["price"] ?>",
            stock:"<?= (int)$p["stock"] ?>",
            has_variant:"<?= isset($p["has_variant"]) ? $p["has_variant"] : 0 ?>",
            img:"<?= htmlspecialchars($p["image_url"], ENT_QUOTES) ?>"
          })' 
          class="choose-btn">
            选择规格
          </button>
        <?php else: ?>
          <button class="add-btn" disabled>售罄</button>
        <?php endif; ?>
      </div>
    <?php endforeach;
  } else {
    echo "<p>该分类暂无商品。</p>";
  }
  echo ob_get_clean();
  exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>qii.shoppp | 商店</title>

  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/shop.css" />
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />

  <style>
    html, body {
      margin: 0;
      padding: 0;
      max-width: 100%;
      overflow-x: hidden;
    }

    /* ✅ 加载层 */
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

    /* 🌸 导航栏 */
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
      padding-top: 80px; /* 正确位置 */
    }

    /* 🍬 掉落糖果 */
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

    /* 🌷 主标题 */
    .shop-header {
      width: 100%;
      padding: 30px 0 40px 0; /* 适中高度，不会爆开 */
      background: linear-gradient(180deg, #FFD7E9 0%, #FFEFF5 100%);
      text-align: center;

      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      margin: 0;      /* ❗ 防止被上下 margin 推开 */
      border: 0;      /* ❗ 清掉意外边框 */
    }
    .shop-header img {
      width: 150px;  /* 调整成可爱的大小 */
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

    /* 🌸 主体布局：手机也左右并排 */
.shop-layout {
  display: grid;
  grid-template-columns: 180px 1fr;
  width: 95%;
  max-width: 1200px;
  margin: 30px auto;
  gap: 15px;
   align-items: start;   /* ⭐ 你已经加了，但不够 */
  min-height: auto;    /* ⭐ 这个必须加 */
}

/* 左侧分类栏（无子菜单版本） */
.sidebar {
  background: #fff;
  padding: 12px;
  border-radius: 15px;
  border: 2px solid #f6bdd9;
  box-shadow: 0 4px 12px rgba(240,150,180,0.15);

  position: sticky;
  top: 100px; /* 可按需微调吸顶距离 */
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

/* 右侧商品区 */
.product-area {
  display: flex;
  flex-direction: column;
  gap: 15px;
}
.product-card {
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

/* ✅ 保持手机也左右布局 */
@media (max-width: 768px) {
  .shop-layout {
    grid-template-columns:40% 60%;
    width: 98%;
  }
  .sidebar {
    position: relative; /* 手机端取消 sticky */
    top: 0;
    max-height: none;
    overflow: visible;
    margin-bottom: 15px;
  }
  .sidebar li {
    font-size: 0.9rem;
    padding: 8px;
  }
  .product-card {
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 15px;
  }
  .product-card img {
    width: 120px;
    height: 120px;
    margin-bottom: 10px;
  }
  .product-info {
    margin: 0;
    text-align: center;
  }
  .add-btn {
    width: 100%;
    max-width: 180px;
    margin-top: 8px;
  }
}

    /* 🩷 购物袋弹窗 */
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
    /* SOLD OUT 内置标签样式 */
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
    /* 🖼️ 纯预览，不影响你原本图片上传 / 显示 */
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
   🌸 Qii.shoppp 全站统一粉色主题（整套统一）
   ============================================ */

/* 整个网站统一背景 */
html, body {
  background: linear-gradient(180deg, #FFE6F0 0%, #FFF4FA 100%) !important;
}

/* 顶部 Banner 颜色统一 */
.shop-header {
  background: linear-gradient(180deg, #FFD1E3 0%, #FFE6F2 100%) !important;
}

/* 中间整体区域统一粉色卡片 */
.shop-layout > div {
  background: linear-gradient(180deg, #FFF0F7 0%, #FFE6F0 100%) !important;
  padding: 25px !important;
  border-radius: 25px !important;
  border: 2px solid #F8C9DA !important;
  box-shadow: 0 4px 10px rgba(240,150,180,0.15) !important;
}

/* 分类栏统一同色 */
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

/* 商品卡片统一同色 */
.product-card {
  background: #FFF6FA !important;
  border: 2px solid #F8C9DA !important;
  box-shadow: 0 4px 10px rgba(240,150,180,0.15) !important;
}

/* 商品文字统一 */
.product-info h4 {
  color: #E44B87 !important;
}
.product-info p {
  color: #C2799B !important;
}

/* 价格统一粉色 */
.product-info .price {
  color: #E44B87 !important;
}

/* 选择规格按钮统一粉色 */
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

/* SOLD OUT 统一粉色 */
.soldout-tag {
  background: #E57597 !important;
}

/* 分类标题统一粉色 */
.category-title {
  color: #E44B87 !important;
}

/* ------------------------------------------
   🌸 Variant Modal 规格弹窗统一粉色
   ------------------------------------------ */
#variantModal {
  background: rgba(255, 210, 230, 0.55) !important;
}
#variantModal .modal-box {
  background: linear-gradient(180deg,#FFF0F7 0%, #FFE1EE 100%) !important;
  border: 2px solid #F6BDD9 !important;
  box-shadow: 0 6px 20px rgba(230,103,156,0.35) !important;
}

/* 弹窗标题文字 */
#modalName {
  color: #E44B87 !important;
}
#modalPrice, #modalStock {
  color: #C94B82 !important;
}

/* 规格按钮统一 */
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

/* 加入购物车按钮统一粉色 */
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

/* 关闭按钮 */
#closeVariantModal {
  color: #E5679C !important;
}
#closeVariantModal:hover {
  color: #C94B82 !important;
}

  </style>
</head>

<body>
  <!-- 🩷 加载动画 -->
  <div id="loader">
    <img src="images/25.png" alt="Loading..." />
    <p>Qii 正在陈列可爱好物中，请稍等～ 🎀</p>
  </div>
  
<!-- 🖼️ 图片放大查看弹窗 -->
<div id="imgPreview" class="img-preview-modal" style="display:none;">
  <img id="imgPreviewPic" src="" alt="">
</div>

  <!-- 🍬 掉落糖果 -->
  <div class="falling-sakura">
    <img src="images/candy1.png" class="sakura" />
    <img src="images/candy1.png" class="sakura" />
    <img src="images/candy1.png" class="sakura" />
    <img src="images/candy1.png" class="sakura" />
  </div>

  <!-- 导航栏 -->
  <?php include __DIR__ . "/includes/header.php"; ?>

  <main>
    <header class="shop-header" data-aos="fade-down">
      <img src="images/4.png" alt="购物女孩" />
      <h1>🌸 可爱生活选物</h1>
    </header>

    <section class="shop-layout">
  <!-- 左侧分类栏 -->
  <aside class="sidebar">
    <h3>🩷 分类</h3>
    <ul>
  <li class="cat-link <?= ($cat === 'phone') ? 'active' : '' ?>" data-cat="phone">📱 手机配件</li>
  <li class="cat-link <?= ($cat === 'hair') ? 'active' : '' ?>" data-cat="hair">🎀 发夹发饰</li>
  <li class="cat-link <?= ($cat === 'snack') ? 'active' : '' ?>" data-cat="snack">🍬 零食</li>
  <li class="cat-link <?= ($cat === 'creative') ? 'active' : '' ?>" data-cat="creative">🩷 文创</li>
  <li class="cat-link <?= ($cat === 'case') ? 'active' : '' ?>" data-cat="case">💖 手机壳</li>
  <li class="cat-link <?= ($cat === 'nail') ? 'active' : '' ?>" data-cat="nail">💅 穿戴甲</li>
  <li class="cat-link <?= ($cat === 'scent') ? 'active' : '' ?>" data-cat="scent">🌸 香片</li>
  <li class="cat-link <?= ($cat === 'doll') ? 'active' : '' ?>" data-cat="doll">🧸 娃娃</li>
  <li class="cat-link <?= ($cat === 'stationery') ? 'active' : '' ?>" data-cat="stationery">✏️ 文具</li>
  </ul>

  </aside>

  <!-- 右侧商品展示 -->
  <div>
    <h2 class="category-title"><?= htmlspecialchars($categories[$cat] ?? '发夹发饰') ?></h2>
    <div class="product-area">
      <?php if (empty($products)): ?>
    <p style="text-align:center;color:#999;">暂无商品～ 🎀</p>
  <?php else: ?>
    <?php foreach ($products as $p): ?>
      <div class="product-card" data-aos="fade-up">
        <?php if ($p['stock'] <= 0): ?>
          <div class="soldout-tag">SOLD OUT</div>
        <?php endif; ?>
        <img src="uploads/<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
        <div class="product-info">
          <h4><?= htmlspecialchars($p['name']) ?></h4>
          <?php if (!empty($p['sku'])): ?><p>编号：<?= htmlspecialchars($p['sku']) ?></p><?php endif; ?>
          <div class="price">RM <?= number_format($p['price'], 2) ?></div>
        </div>
        <?php if ($p['stock'] > 0): ?>
          <button onclick='openVariantModal({
            id:"<?= $p['id'] ?>",
            name:"<?= htmlspecialchars($p["name"], ENT_QUOTES) ?>",
            price:"<?= $p["price"] ?>",
            stock:"<?= (int)$p["stock"] ?>",
            has_variant:"<?= isset($p["has_variant"]) ? $p["has_variant"] : 0 ?>",
            img:"<?= htmlspecialchars($p["image_url"], ENT_QUOTES) ?>"
          })' 
          class="choose-btn">
            选择规格
          </button>
        <?php else: ?>
          <button class="add-btn" disabled>售罄</button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
    </div>
  </div>
</section>
  </main>

  <?php include __DIR__ . "/includes/footer.php"; ?>

  <!-- ✨ JS 部分 -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({ duration: 800, once: true });

    // 记录已达上限的商品 SKU（跨分类保持禁用）
    let outOfStockSKU = new Set();

    // Loader 动画
    window.addEventListener("load", () => {
      const loader = document.getElementById("loader");
      setTimeout(() => {
        loader.classList.add("fade-out");
        setTimeout(() => (loader.style.display = "none"), 600);
      }, 1800);
    });

    // ✅ 点击分类时动态加载商品（不刷新整个页面）
    document.addEventListener("DOMContentLoaded", () => {
      const categoryList = document.querySelectorAll(".cat-link");
      const productArea = document.querySelector(".product-area");
      const title = document.querySelector(".category-title");

      // 🚀 页面打开时自动加载默认分类（手机配件）
      const defaultCat = "phone";
      fetch(`shop.php?cat=${defaultCat}&ajax=1`)
        .then(res => res.text())
        .then(html => {
          if (productArea) productArea.innerHTML = html;
          if (title) title.textContent = "📱 手机配件";
          // 设置高亮
          categoryList.forEach(li => li.classList.remove("active"));
          const activeLi = document.querySelector(`[data-cat="${defaultCat}"]`);
          if (activeLi) activeLi.classList.add("active");

          // 🌸 重新应用已达上限的禁用状态
          outOfStockSKU.forEach(sku => {
            const btn = productArea.querySelector(`.add-btn[data-sku="${sku}"]`);
            if (btn) {
              btn.disabled = true;
              btn.textContent = "💖 已达上限";
              btn.style.background = "#e4c0cb";
              btn.style.color = "#fff";
              btn.style.cursor = "not-allowed";
              btn.style.opacity = "0.8";
              btn.style.transition = "all .3s ease";
              btn.style.transform = "scale(0.95)";
            }
          });
        });

      // ✅ 分类点击切换
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

              // 🌸 重新应用已达上限的禁用状态
              outOfStockSKU.forEach(sku => {
                const btn = productArea.querySelector(`.add-btn[data-sku="${sku}"]`);
                if (btn) {
                  btn.disabled = true;
                  btn.textContent = "💖 已达上限";
                  btn.style.background = "#e4c0cb";
                  btn.style.color = "#fff";
                  btn.style.cursor = "not-allowed";
                  btn.style.opacity = "0.8";
                  btn.style.transition = "all .3s ease";
                  btn.style.transform = "scale(0.95)";
                }
              });
            })
            .catch(err => {
              productArea.innerHTML = `<p style="color:#c94b82;text-align:center;">加载失败，请稍后重试～</p>`;
            });
        });
      });
    });

    // 🛒 Add to Cart 逻辑
document.addEventListener("click", async (e) => {
  if (e.target.classList.contains("add-btn")) {
    const btn = e.target;
    const fd = new FormData();
    fd.append("sku", btn.dataset.sku);
    fd.append("name", btn.dataset.name);
    fd.append("price", btn.dataset.price);
    fd.append("img", btn.dataset.img);

    const res = await fetch("add_to_cart.php?mode=add", {
      method: "POST",
      body: fd
    });
    const data = await res.json();

   if (data.success) {
  // ✅ 更新购物袋数量
  const countEl = document.querySelector(".cart-count");
  if (countEl) countEl.textContent = data.count;

  // 💕 同步更新购物袋内容（立即刷新 footer 弹窗）
  if (typeof getCartAndUpdate === "function") {
    getCartAndUpdate();
  }

      // 🌸 粉色提示动画
      const tip = document.createElement("div");
      tip.textContent = `💖 已加入 ${btn.dataset.name}`;
      Object.assign(tip.style, {
        position: "fixed",
        bottom: "90px",
        left: "50%",
        transform: "translateX(-50%)",
        background: "#ffc0cb",
        color: "#fff",
        padding: "10px 18px",
        borderRadius: "20px",
        boxShadow: "0 4px 10px rgba(255,105,180,.4)",
        fontSize: "15px",
        zIndex: "9999",
        opacity: "0",
        transition: "opacity .3s, bottom .5s ease-out"
      });
      document.body.appendChild(tip);
      setTimeout(() => {
        tip.style.opacity = "1";
        tip.style.bottom = "110px";
      }, 50);
      setTimeout(() => {
        tip.style.opacity = "0";
        tip.style.bottom = "130px";
      }, 1600);
      setTimeout(() => tip.remove(), 2000);
    } else {
      // 如果后端返回库存不足，立即将按钮置为已售罄并标记卡片
      if (data && data.message && data.message.includes("库存不足")) {
        btn.disabled = true;
        btn.textContent = "💖 已售罄";
        btn.style.background = "#e4c0cb";
        btn.style.color = "#fff";
        btn.style.cursor = "not-allowed";
        btn.style.opacity = "0.8";
        btn.style.transform = "scale(0.95)";
        const card = btn.closest('.product-card');
        if (card && !card.querySelector('.soldout-tag')) {
          const tag = document.createElement('div');
          tag.className = 'soldout-tag';
          tag.textContent = 'SOLD OUT';
          card.appendChild(tag);
        }
      }
      // 记录此 SKU 已达上限，后续渲染保持禁用
      outOfStockSKU.add(btn.dataset.sku);
      // 🩷 不再弹窗，用 qii 风格售罄样式
      btn.disabled = true;
      btn.textContent = "💖 已达上限";
      btn.style.background = "#e4c0cb";
      btn.style.color = "#fff";
      btn.style.cursor = "not-allowed";
      btn.style.opacity = "0.8";
      btn.style.transition = "all .3s ease";
      btn.style.transform = "scale(0.95)";
      // 不再使用图片，若需要角标请由库存不足路径添加
    }
  }
});

// 让每个糖果分散随机位置
document.querySelectorAll(".sakura").forEach((el, i) => {
  el.style.left = Math.random() * 100 + "vw";          
  el.style.animationDuration = 8 + Math.random()*6 + "s";
  el.style.animationDelay = Math.random()*3 + "s";
  el.style.opacity = 0.4 + Math.random() * 0.6;
});
// 🖼️ 点击商品图片 → 放大预览
document.addEventListener("click", function(e) {
  // 只放大 product-card 的图片，不放大 header/footer/logo
  if (e.target.matches(".product-card img")) {
    const modal = document.getElementById("imgPreview");
    const modalImg = document.getElementById("imgPreviewPic");
    if (!modal || !modalImg) return;

    modalImg.src = e.target.src;  // 用你的原图，不改路径
    modal.style.display = "flex";
  }
});

// 点击背景关闭预览
document.getElementById("imgPreview").addEventListener("click", function() {
  this.style.display = "none";
});

function openVariantModal(p) {
    const modal = document.getElementById("variantModal");

    // 基本资料
    document.getElementById("modalName").textContent = p.name;
    document.getElementById("modalPrice").textContent = parseFloat(p.price).toFixed(2);
    document.getElementById("modalImg").src = "a9sd8f7sd9f_admin/" + p.img;
    document.getElementById("selectedProductId").value = p.id;

    // 重置
    document.getElementById("selectedVariantId").value = "";
    document.getElementById("selectedVariantName").value = "";
    document.getElementById("modalStock").textContent = "库存：-";

    /* ===========================================================
       ⭐⭐⭐ 情况 A：无规格商品（has_variant = 0）
       =========================================================== */
    if (p.has_variant == 0 || p.has_variant == "0") {

        // 显示“无需选择规格”
        document.getElementById("variantBox").innerHTML = `
            <div style="padding:12px; font-size:14px; color:#C94B82;">
                无需选择规格
            </div>
        `;

        // 设置为 0（无规格）
        document.getElementById("selectedVariantId").value = 0;
        document.getElementById("selectedVariantName").value = "";

        // 库存
        let stock = p.stock ? p.stock : 0;
        document.getElementById("modalStock").textContent = "库存：" + stock;

        // 隐藏分页
        document.getElementById("variantPagination").style.display = "none";

        modal.style.display = "flex";
        return;
    }

    /* ===========================================================
       ⭐⭐⭐ 情况 B：有规格 → 加载 variant_box_front.php
       =========================================================== */
    fetch("variant_box_front.php?product_id=" + p.id)
        .then(res => res.text())
        .then(html => {
            document.getElementById("variantBox").innerHTML = html;

            // ⭐ 检查是否是无规格商品
            const noVarEl = document.querySelector("#variantBox .no-variant");
            if (noVarEl) {
                document.getElementById("selectedVariantId").value = 0;
                document.getElementById("selectedVariantName").value = "";

                document.getElementById("modalImg").src = noVarEl.dataset.img;
                document.getElementById("modalPrice").textContent = noVarEl.dataset.price;
                document.getElementById("modalStock").textContent = "库存：" + noVarEl.dataset.stock;

                document.getElementById("variantPagination").style.display = "none";
                return;  // 不继续执行规格逻辑
            }

            // ⭐ 有规格才继续执行下面内容
            setTimeout(() => {
                autoSelectFirstCard();
                setupVariantPagination();
            }, 50);
        });

    modal.style.display = "flex";
}

function closeVariantModal() {
    document.getElementById("variantModal").style.display = "none";
}
</script>



<?php include 'variant_modal.php'; ?>

  <!-- 🌸 Qii 可爱提示框 -->
  <div id="qiiToast" class="qii-toast">已加入购物车</div>

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
      z-index: 999999;
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
