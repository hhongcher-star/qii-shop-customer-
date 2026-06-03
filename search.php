
<?php
// === DEBUG MODE ===
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);

// === 如果你想把错误写入日志，把下面取消注释 ===
// ini_set("error_log", __DIR__ . "/php_error.log");

session_start();
require_once __DIR__ . '/a9sd8f7sd9f_admin/config.php';

$q = trim($_GET['q'] ?? '');
$products = [];

if ($q !== '') {
    // 支持多关键词模糊查询 (空格分隔)
    $keywords = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);

    $conditions = [];
    $params = [];
    foreach ($keywords as $kw) {
        $conditions[] = "(name LIKE ? OR sku LIKE ?)";
        $params[] = "%{$kw}%";
        $params[] = "%{$kw}%";
    }

    $sql = "SELECT * FROM products";
    if ($conditions) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>🔍 搜索：<?= htmlspecialchars($q) ?> | qii.shoppp</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/shop.css">
<style>
:root {
  --pink: #f6bdd9;
  --pink-dark: #e5679c;
}

/* 页面框架 */
.search-wrapper {
  max-width: 1000px;
  margin: 100px auto 40px;
  padding: 0 20px;
}

.search-wrapper h2 {
  font-family: "Patrick Hand", cursive;
  color: var(--pink-dark);
  margin-bottom: 18px;
  text-align: center;
  font-size: 1.6rem;
}

/* 商品列表 */
.search-results {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.product-card {
  background: #fff;
  border-radius: 18px;
  border: 1px solid #fad2e1;
  padding: 15px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 4px 10px rgba(240,150,180,0.15);
  transition: transform .25s ease;
}
.product-card:hover { transform: translateY(-3px); }

.product-info {
  display: flex;
  gap: 15px;
  align-items: center;
  position: relative;
}
.product-info img {
  width: 80px;
  height: 80px;
  border-radius: 10px;
  border: 1px solid #f6bdd9;
  object-fit: cover;
}

.product-text h4 {
  margin: 0;
  font-size: 1rem;
  color: var(--pink-dark);
}
.product-text p {
  margin: 2px 0;
  font-size: 0.85rem;
  color: #666;
}
.product-text .price {
  margin-top: 5px;
  font-weight: bold;
  color: var(--pink);
}

/* add-to-cart 按钮 */
.add-btn {
  background: var(--pink);
  border: none;
  padding: 6px 14px;
  color: white;
  font-family: "Patrick Hand", cursive;
  border-radius: 18px;
  cursor: pointer;
  transition: all .3s ease;
}
.add-btn:hover { background: var(--pink-dark); transform: scale(1.05); }

.add-btn:disabled {
  background: #e4c0cb;
  color: white;
  cursor: not-allowed;
  opacity: 0.8;
}

/* Sold Out 标签 */
.soldout-tag {
  position: absolute;
  top: -4px;
  left: -4px;
  background: var(--pink-dark);
  color: #fff;
  font-size: 0.8rem;
  font-family: "Patrick Hand", cursive;
  padding: 3px 8px;
  border-radius: 6px;
  transform: rotate(-8deg);
  box-shadow: 0 2px 6px rgba(229,103,156,0.3);
}

/* 手机 */
@media (max-width: 768px) {
  .product-card {
    flex-direction: column;
    text-align: center;
  }
  .product-info {
    flex-direction: column;
    gap: 10px;
  }
}
</style>
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="search-wrapper">
  <h2>🔍 搜索结果：<?= htmlspecialchars($q) ?></h2>
  <div class="search-results">

<?php if ($products): ?>
  <?php foreach ($products as $p): ?>
    <div class="product-card">
      <div class="product-info">
        <?php if ($p['stock'] <= 0): ?>
          <div class="soldout-tag">SOLD OUT</div>
        <?php endif; ?>

<!-- 自动补全开头路径 -->
<img src="a9sd8f7sd9f_admin/<?= htmlspecialchars(ltrim($p['image_url'], '/')) ?>" 
     alt="<?= htmlspecialchars($p['name']) ?>">
        <div class="product-text">
          <h4><?= htmlspecialchars($p['name']) ?></h4>
          <p>库存：<?= (int)$p['stock'] ?></p>
          <div class="price">RM <?= number_format($p['price'],2) ?></div>
        </div>
      </div>

      <?php if ($p['stock'] > 0): ?>
        <button class="add-btn"
          data-id="<?= $p['id'] ?>"
          data-sku="<?= $p['sku'] ?>"
          data-name="<?= htmlspecialchars($p['name']) ?>"
          data-price="<?= $p['price'] ?>"
          data-img="a9sd8f7sd9f_admin/<?= $p['image_url'] ?>"
          data-stock="<?= $p['stock'] ?>"
        >加入购物袋</button>
      <?php else: ?>
        <button class="add-btn" disabled>售罄</button>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

<?php else: ?>
  <p style="text-align:center;color:#aaa;margin:40px 0;">❌ 没有找到相关商品。</p>
<?php endif; ?>

  </div>
</div>

  <?php include __DIR__ . "/includes/footer.php"; ?>

<script>
// 🛒 完全兼容购物袋系统
document.addEventListener("click", async (e) => {
  if (!e.target.classList.contains("add-btn")) return;
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

  // 🔄 更新购物袋 UI（必须存在）
  if (typeof getCartAndUpdate === "function") {
    getCartAndUpdate();
  }

  // 💖 粉色提示
  const tip = document.createElement("div");
  tip.textContent = `💖 已加入 ${btn.dataset.name}`;
  Object.assign(tip.style,{
    position:"fixed",
    bottom:"90px",
    left:"50%",
    transform:"translateX(-50%)",
    background:"#ffc0cb",
    color:"#fff",
    padding:"10px 18px",
    borderRadius:"20px",
    boxShadow:"0 4px 10px rgba(255,105,180,.4)",
    fontSize:"15px",
    zIndex:"9999",
    opacity:"0",
    transition:"opacity .3s, bottom .5s ease-out"
  });
  document.body.appendChild(tip);
  setTimeout(()=>{ tip.style.opacity="1"; tip.style.bottom="110px"; },50);
  setTimeout(()=>{ tip.style.opacity="0"; tip.style.bottom="130px"; },1600);
  setTimeout(()=> tip.remove(),2000);
});
</script>

</body>
</html>

