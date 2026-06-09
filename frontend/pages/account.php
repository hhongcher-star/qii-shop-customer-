<?php
require_once __DIR__ . '/../../app/customers.php';
qii_start_session();
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
qii_ensure_customer_tables($pdo);
qii_require_customer();

$customer = qii_customer();
$view = (string)($_GET['view'] ?? 'orders');
$allowedViews = ['orders', 'holds', 'favorites', 'recent'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'orders';
}

$orderWhere = 'o.customer_id=?';
if ($view === 'holds') {
    $orderWhere .= " AND o.order_status IN ('stored_uncombined','stored_combined')";
}

$orders = [];
$favoriteProducts = [];
if (in_array($view, ['orders', 'holds'], true)) {
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) AS item_count
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id=o.id
        WHERE $orderWhere
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([(int)$customer['id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
if ($view === 'favorites') {
    $stmt = $pdo->prepare("
        SELECT p.*
        FROM customer_favorites f
        INNER JOIN products p ON p.id=f.product_id
        WHERE f.customer_id=? AND COALESCE(p.status, 'active')='active'
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([(int)$customer['id']]);
    $favoriteProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$statsStmt = $pdo->prepare("
    SELECT
      COUNT(*) AS all_orders,
      SUM(CASE WHEN order_status IN ('stored_uncombined','stored_combined') THEN 1 ELSE 0 END) AS hold_orders,
      COALESCE(SUM(grand_total), 0) AS total_amount
    FROM orders
    WHERE customer_id=?
");
$statsStmt->execute([(int)$customer['id']]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$pageTitles = [
    'orders' => ['全部订单', '这里记录你的全部订单历史。'],
    'holds' => ['存单管理', '集中查看仍在存放或已经合单的订单。'],
    'favorites' => ['我的收藏', '你收藏的商品会显示在这里。'],
    'recent' => ['最近浏览', '你最近看过的商品会显示在这里。'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitles[$view][0]) ?> | qii.shop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --account-pink: #ed4d94;
      --account-pink-dark: #d93880;
      --account-soft: #fff2f7;
      --account-border: #f6dce7;
      --account-text: #3f3540;
      --account-muted: #8d7b86;
    }
    * { box-sizing: border-box; }
    body { margin:0; background:#ffd9ea; color:var(--account-text); font-family:Arial, sans-serif; }
    .account-page { width:min(1180px, calc(100% - 32px)); margin:22px auto 48px; }
    .account-hero {
      min-height:150px;
      display:flex;
      align-items:center;
      position:relative;
      overflow:hidden;
      padding:28px 42px;
      border:1px solid #f9dfe9;
      border-radius:8px;
      background:linear-gradient(110deg,#fff7fb 0%,#ffedf5 58%,#ffe8f2 100%);
    }
    .account-hero::after {
      content:"";
      position:absolute;
      width:330px;
      height:180px;
      right:20px;
      bottom:-34px;
      background:url("images/27.png") center/contain no-repeat;
      opacity:.94;
    }
    .account-hero-copy { position:relative; z-index:1; max-width:560px; }
    .account-hero h1 { margin:0 0 8px; font-size:30px; letter-spacing:0; }
    .account-hero h1 i { color:var(--account-pink); font-size:19px; }
    .account-hero p { margin:0; color:var(--account-muted); }
    .account-hero strong { color:var(--account-pink); }
    .account-layout { display:grid; grid-template-columns:230px minmax(0,1fr); gap:24px; margin-top:22px; align-items:start; }
    .account-sidebar, .account-panel, .account-stats {
      background:#fff;
      border:1px solid var(--account-border);
      border-radius:8px;
      box-shadow:0 8px 22px rgba(201,75,130,.06);
    }
    .profile-mini { display:flex; align-items:center; gap:12px; padding:18px; border-bottom:1px solid var(--account-border); }
    .profile-avatar {
      width:48px; height:48px; display:grid; place-items:center; flex:0 0 48px;
      border-radius:50%; background:var(--account-soft); color:var(--account-pink); font-size:21px;
    }
    .profile-mini strong, .profile-mini small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:135px; }
    .profile-mini small { color:var(--account-muted); font-size:11px; margin-top:4px; }
    .account-nav { padding:10px 0; }
    .nav-section-title { padding:10px 18px 6px; color:#b49eaa; font-size:11px; font-weight:800; text-transform:uppercase; }
    .account-nav a {
      min-height:44px; display:flex; align-items:center; gap:12px; padding:0 18px;
      color:#6e6069; text-decoration:none; border-left:3px solid transparent; font-size:14px;
    }
    .account-nav a:hover, .account-nav a.active { color:var(--account-pink); background:#fff1f7; border-left-color:var(--account-pink); }
    .account-nav i { width:17px; text-align:center; }
    .logout-link { margin:10px; min-height:42px !important; justify-content:center; border:1px solid var(--account-pink) !important; border-radius:5px; color:var(--account-pink) !important; }
    .account-main { min-width:0; }
    .account-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); margin-bottom:18px; overflow:hidden; }
    .account-stat { display:flex; align-items:center; gap:14px; padding:18px 22px; border-right:1px solid var(--account-border); }
    .account-stat:last-child { border-right:0; }
    .stat-icon { width:43px; height:43px; display:grid; place-items:center; border-radius:7px; background:#fff0f6; color:var(--account-pink); font-size:18px; }
    .account-stat:nth-child(2) .stat-icon { background:#fff7e7; color:#f0a521; }
    .account-stat:nth-child(3) .stat-icon { background:#eef8ff; color:#348ee8; }
    .account-stat strong, .account-stat small { display:block; }
    .account-stat strong { font-size:18px; }
    .account-stat small { margin-top:3px; color:var(--account-muted); }
    .panel-head { min-height:68px; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 20px; border-bottom:1px solid var(--account-border); }
    .panel-head h2 { margin:0 0 4px; font-size:18px; }
    .panel-head p { margin:0; color:var(--account-muted); font-size:13px; }
    .shop-link { color:var(--account-pink); text-decoration:none; font-size:13px; font-weight:800; }
    .orders-list { padding:16px; }
    .order-row { display:grid; grid-template-columns:minmax(150px,1.3fr) 90px 110px 105px; gap:14px; align-items:center; padding:16px; border:1px solid var(--account-border); border-radius:7px; margin-bottom:12px; }
    .order-row:last-child { margin-bottom:0; }
    .order-number { font-weight:900; margin-bottom:5px; }
    .order-row small { color:var(--account-muted); }
    .order-value span { display:block; color:var(--account-muted); font-size:11px; margin-bottom:4px; }
    .receipt-link { display:inline-flex; justify-content:center; align-items:center; min-height:36px; border-radius:5px; background:var(--account-pink); color:#fff; text-decoration:none; font-size:13px; font-weight:900; }
    .hold-badge { display:inline-flex; margin-top:7px; padding:5px 9px; border-radius:999px; background:#fff1d9; color:#bd7a00; font-size:11px; font-weight:900; }
    .favorite-actions { display:grid; grid-template-columns:1fr 38px; gap:8px; }
    .remove-favorite { border:1px solid #f0aac8; border-radius:5px; background:#fff; color:#ed4d94; cursor:pointer; }
    .empty-state { min-height:300px; display:grid; place-items:center; text-align:center; padding:40px 20px; }
    .empty-state i { display:block; margin-bottom:15px; color:#f3a6c7; font-size:54px; }
    .empty-state strong { display:block; margin-bottom:8px; font-size:17px; }
    .empty-state p { margin:0 0 18px; color:var(--account-muted); font-size:13px; }
    .empty-state a { display:inline-flex; align-items:center; gap:7px; min-height:38px; padding:0 18px; border-radius:5px; background:var(--account-pink); color:#fff; text-decoration:none; font-weight:900; font-size:13px; }
    .favorite-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; padding:16px; }
    .favorite-card { position:relative; overflow:hidden; border:1px solid var(--account-border); border-radius:7px; background:#fff; }
    .favorite-card img { width:100%; aspect-ratio:1/1; display:block; object-fit:cover; }
    .favorite-info { padding:12px; }
    .favorite-info strong { display:block; min-height:38px; font-size:14px; }
    .favorite-info span { display:block; margin:8px 0 12px; color:var(--account-pink); font-weight:900; }
    .favorite-info a { display:flex; min-height:36px; align-items:center; justify-content:center; border-radius:5px; background:var(--account-pink); color:#fff; text-decoration:none; font-size:13px; font-weight:900; }
    #loader{
      position: fixed;
      inset: 0;
      display: none;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background:#ffd9ea;
      z-index: 9999;
      opacity: 1;
      transition: opacity .8s ease;
    }

    #loader.fade-out{
      opacity: 0;
    }

    #loader img{
      width: 150px;
      animation: float 2s ease-in-out infinite;
    }

    #loader p{
      margin-top: 18px;
      color:#ed4d94;
      font-size: 1.1rem;
      letter-spacing: .5px;
    }

    @keyframes float{
      0%,100%{ transform: translateY(0); }
      50%{ transform: translateY(-8px); }
    }

    @media (max-width: 820px) {

      html, body{
        width:100%;
        max-width:100%;
        overflow-x:hidden;
      }

      body{
        background:#ffd9ea;
      }

      .account-page{
        width:100%;
        margin:0;
        padding:0 12px 90px;
      }

      .account-hero{
        min-height:160px;
        padding:24px 140px 24px 18px;
        border:0;
        border-radius:0;
        background:#ffd9ea;
      }

      .account-hero::after{
        width:130px;
        height:130px;
        right:10px;
        bottom:0;
        opacity:1;
      }

      .account-hero h1{
        font-size:28px;
        line-height:1.2;
      }

      .account-hero p{
        font-size:14px;
        line-height:1.6;
      }

      .account-layout{
        display:grid;
        grid-template-columns:minmax(0,1fr);
        gap:14px;
        margin-top:0;
      }

      .account-main{
        display:contents;
      }

      .account-stats{
        order:2;
        grid-template-columns:repeat(3,1fr);
        border-radius:16px;
        overflow:hidden;
      }

      .account-stat{
        flex-direction:column;
        text-align:center;
        gap:8px;
        padding:16px 8px;
        border-right:1px solid var(--account-border);
        border-bottom:0;
      }

      .account-stat:last-child{
        border-right:0;
      }

      .account-stat strong{
        font-size:20px;
      }

      .account-stat small{
        font-size:12px;
      }

      .account-sidebar{
        order:1;
        border-radius:16px;
        overflow:hidden;
        min-width:0;
      }

      .account-panel{
        order:3;
      }

      .profile-mini{
        display:flex;
        padding:16px;
      }

      .nav-section-title{
        display:none;
      }

      .account-nav{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        padding:0;
        width:100%;
      }

      .account-nav a{
        min-height:70px;
        flex-direction:column;
        justify-content:center;
        gap:6px;
        border:0;
        border-radius:0;
        font-size:12px;
        padding:8px 4px;
        min-width:0;
        text-align:center;
      }

      .account-nav a i{
        font-size:18px;
      }

      .account-nav a.active{
        background:#fff1f7;
        border-bottom:3px solid var(--account-pink);
      }

      .logout-link{
        display:flex !important;
        grid-column:1/-1;
        margin:10px;
        border-radius:10px !important;
      }

      .account-panel{
        order:3;
        border-radius:16px;
        overflow:hidden;
        min-width:0;
      }

      .panel-head{
        padding:18px;
        align-items:flex-start;
      }

      .empty-state{
        min-height:240px;
        padding:24px 18px;
      }

      .empty-state a{
        width:170px;
        justify-content:center;
      }

      .favorite-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
        padding:12px;
      }

      .order-row{
        grid-template-columns:1fr;
      }

      .orders-list{
        padding:12px;
      }

      .order-row,
      .favorite-card{
        min-width:0;
      }

      .shop-link{
        white-space:nowrap;
      }
    }
  </style>
</head>
<body>
  <div id="loader">
    <img src="images/userimage-removebg-preview.png" alt="Loading">
    <p>Qii 正在整理你的订单中... 🌸</p>
  </div>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="account-page">
  <section class="account-hero">
    <div class="account-hero-copy">
      <h1>我的订单 <i class="fa-solid fa-heart"></i></h1>
      <p><strong><?= htmlspecialchars($customer['name']) ?></strong>，这里会记录你的订单和存单历史。</p>
    </div>
  </section>

  <div class="account-layout">
    <aside class="account-sidebar">
      <div class="profile-mini">
        <span class="profile-avatar"><i class="fa-solid fa-user"></i></span>
        <div>
          <strong><?= htmlspecialchars($customer['name']) ?></strong>
          <small><?= htmlspecialchars($customer['email']) ?></small>
        </div>
      </div>
      <nav class="account-nav">
        <div class="nav-section-title">订单中心</div>
        <a class="<?= $view === 'orders' ? 'active' : '' ?>" href="account.php?view=orders"><i class="fa-solid fa-bag-shopping"></i> 全部订单</a>
        <a class="<?= $view === 'holds' ? 'active' : '' ?>" href="account.php?view=holds"><i class="fa-regular fa-bookmark"></i> 存单管理</a>
        <div class="nav-section-title">我的收藏</div>
        <a class="<?= $view === 'favorites' ? 'active' : '' ?>" href="account.php?view=favorites"><i class="fa-regular fa-heart"></i> 收藏夹</a>
        <a class="<?= $view === 'recent' ? 'active' : '' ?>" href="account.php?view=recent"><i class="fa-regular fa-clock"></i> 最近浏览</a>
        <a class="logout-link" href="change_password.php"><i class="fa-solid fa-key"></i> 修改密码</a>
        <a class="logout-link" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> 退出登录</a>
      </nav>
    </aside>

    <section class="account-main">
      <div class="account-stats">
        <div class="account-stat">
          <span class="stat-icon"><i class="fa-solid fa-bag-shopping"></i></span>
          <div><strong><?= (int)($stats['all_orders'] ?? 0) ?></strong><small>全部订单</small></div>
        </div>
        <div class="account-stat">
          <span class="stat-icon"><i class="fa-solid fa-box-archive"></i></span>
          <div><strong><?= (int)($stats['hold_orders'] ?? 0) ?></strong><small>存单记录</small></div>
        </div>
        <div class="account-stat">
          <span class="stat-icon"><i class="fa-solid fa-receipt"></i></span>
          <div><strong>RM <?= number_format((float)($stats['total_amount'] ?? 0), 2) ?></strong><small>历史订单金额</small></div>
        </div>
      </div>

      <section class="account-panel">
        <header class="panel-head">
          <div>
            <h2><?= htmlspecialchars($pageTitles[$view][0]) ?></h2>
            <p><?= htmlspecialchars($pageTitles[$view][1]) ?></p>
          </div>
          <a class="shop-link" href="shop.php">继续购物 <i class="fa-solid fa-arrow-right"></i></a>
        </header>

        <?php if ($view === 'favorites' && $favoriteProducts): ?>
          <div class="favorite-grid">
            <?php foreach ($favoriteProducts as $product): ?>
              <article class="favorite-card">
                <img src="<?= htmlspecialchars(qii_asset_path($product['image_url'] ?? '')) ?>" alt="<?= htmlspecialchars(qii_text($product['name'])) ?>">
                <div class="favorite-info">
                  <strong><?= htmlspecialchars(qii_text($product['name'])) ?></strong>
                  <span>RM <?= number_format((float)$product['price'], 2) ?></span>
                  <button class="remove-favorite" type="button" data-remove-favorite="<?= (int)$product['id'] ?>"><i class="fa-solid fa-trash"></i> 取消收藏</button>
                  <a href="shop.php?cat=<?= urlencode((string)$product['category']) ?>">查看商品</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php elseif (in_array($view, ['favorites', 'recent'], true)): ?>
          <div class="empty-state">
            <div>
              <i class="<?= $view === 'favorites' ? 'fa-regular fa-heart' : 'fa-regular fa-clock' ?>"></i>
              <strong><?= $view === 'favorites' ? '收藏夹还是空的' : '还没有最近浏览记录' ?></strong>
              <p><?= $view === 'favorites' ? '之后接入商品收藏按钮，收藏内容会显示在这里。' : '之后接入商品浏览记录，最近看过的商品会显示在这里。' ?></p>
              <a href="shop.php"><i class="fa-solid fa-bag-shopping"></i> 去逛逛</a>
            </div>
          </div>
        <?php elseif (!$orders): ?>
          <div class="empty-state">
            <div>
              <i class="fa-solid fa-box-open"></i>
              <strong><?= $view === 'holds' ? '还没有存单记录' : '还没有订单' ?></strong>
              <p>登录状态下结账后，相关记录会显示在这里。</p>
              <a href="shop.php"><i class="fa-solid fa-bag-shopping"></i> 去购物</a>
            </div>
          </div>
        <?php else: ?>
          <div class="orders-list">
            <?php foreach ($orders as $order): ?>
              <article class="order-row">
                <div>
                  <div class="order-number"><?= htmlspecialchars($order['order_number']) ?></div>
                  <small><?= htmlspecialchars(date('Y-m-d H:i', strtotime($order['created_at']))) ?></small>
                  <?php if (in_array($order['order_status'], ['stored_uncombined','stored_combined'], true)): ?>
                    <span class="hold-badge"><?= $order['order_status'] === 'stored_combined' ? '存单：已合单' : '存单：未合单' ?></span>
                  <?php endif; ?>
                </div>
                <div class="order-value"><span>商品</span><?= (int)$order['item_count'] ?> 件</div>
                <div class="order-value"><span>金额</span>RM <?= number_format((float)($order['grand_total'] ?: $order['total']), 2) ?></div>
                <a class="receipt-link" href="receipt.php?order_number=<?= urlencode($order['order_number']) ?><?= !empty($order['receipt_token']) ? '&token=' . urlencode($order['receipt_token']) : '' ?>">查看订单</a>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </section>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener("qii:announcement-closed", () => {
  const loader = document.getElementById("loader");
  if (!loader) return;

  loader.style.display = "flex";
  requestAnimationFrame(() => loader.classList.remove("fade-out"));

  setTimeout(() => {
    loader.classList.add("fade-out");
    setTimeout(() => {
      loader.style.display = "none";
    }, 600);
  }, 1200);
}, { once: true });

async function switchAccountView(url, pushState = true) {
  const panel = document.querySelector(".account-panel");
  if (!panel) return;

  panel.style.opacity = ".45";
  panel.style.pointerEvents = "none";

  try {
    const response = await fetch(url, {
      headers: { "X-Requested-With": "XMLHttpRequest" }
    });
    if (!response.ok) throw new Error("Request failed");

    const html = await response.text();
    const nextDocument = new DOMParser().parseFromString(html, "text/html");
    const nextPanel = nextDocument.querySelector(".account-panel");
    if (!nextPanel) throw new Error("Panel missing");

    panel.innerHTML = nextPanel.innerHTML;
    const nextView = new URL(url, location.href).searchParams.get("view") || "orders";
    document.querySelectorAll('.account-nav a[href*="account.php?view="]').forEach(link => {
      const linkView = new URL(link.href, location.href).searchParams.get("view");
      link.classList.toggle("active", linkView === nextView);
    });

    if (pushState) history.pushState({ accountView: nextView }, "", url);
  } catch (error) {
    location.href = url;
  } finally {
    panel.style.opacity = "";
    panel.style.pointerEvents = "";
  }
}

document.addEventListener("click", event => {
  const link = event.target.closest('.account-nav a[href*="account.php?view="]');
  if (!link) return;
  event.preventDefault();
  switchAccountView(link.href);
});

window.addEventListener("popstate", () => {
  switchAccountView(location.href, false);
});

document.addEventListener("click", async event => {
  const button = event.target.closest("[data-remove-favorite]");
  if (!button) return;
  const token = document.querySelector('meta[name="qii-csrf-token"]')?.content || "";
  const response = await fetch("api/toggle_favorite.php", {
    method: "POST",
    headers: { "Content-Type":"application/x-www-form-urlencoded", "X-QII-CSRF-Token":token },
    body: new URLSearchParams({ product_id: button.dataset.removeFavorite })
  });
  const data = await response.json();
  if (data.success && !data.favorite) button.closest(".favorite-card")?.remove();
});
</script>
</body>
</html>
