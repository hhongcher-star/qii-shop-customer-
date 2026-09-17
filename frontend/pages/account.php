<?php
require_once __DIR__ . '/../../app/customers.php';
qii_start_session();
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
qii_ensure_customer_tables($pdo);
qii_require_customer();

$customer = qii_customer();
$view = (string)($_GET['view'] ?? 'orders');
$allowedViews = ['orders', 'settings'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'orders';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    qii_verify_frontend_csrf();
    $action = (string)($_POST['address_action'] ?? '');
    $customerId = (int)$customer['id'];

    if ($action === 'save_address') {
        qii_save_customer_address(
            $pdo,
            $customerId,
            (string)($_POST['recipient_name'] ?? ''),
            (string)($_POST['phone'] ?? ''),
            (string)($_POST['address'] ?? ''),
            (string)($_POST['postcode'] ?? ''),
            (string)($_POST['state'] ?? '')
        );
        header('Location: account.php?view=settings&saved=1');
        exit;
    }

    $addressId = (int)($_POST['address_id'] ?? 0);
    if ($addressId > 0 && $action === 'delete_address') {
        $stmt = $pdo->prepare('DELETE FROM customer_addresses WHERE id=? AND customer_id=?');
        $stmt->execute([$addressId, $customerId]);
        header('Location: account.php?view=settings&deleted=1');
        exit;
    }

    if ($addressId > 0 && $action === 'default_address') {
        $pdo->prepare('UPDATE customer_addresses SET is_default=0 WHERE customer_id=?')->execute([$customerId]);
        $stmt = $pdo->prepare('UPDATE customer_addresses SET is_default=1, updated_at=NOW() WHERE id=? AND customer_id=?');
        $stmt->execute([$addressId, $customerId]);
        header('Location: account.php?view=settings&default=1');
        exit;
    }
}

$orderWhere = 'o.customer_id=?';
$orders = [];
$savedAddresses = [];
if ($view === 'orders') {
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
if ($view === 'settings') {
    $savedAddresses = qii_customer_addresses($pdo, (int)$customer['id'], 20);
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
    'settings' => ['地址设置', '管理你的收件人和地址配套。'],
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
    .logout-link { margin:10px; min-height:42px !important; display:flex; align-items:center; gap:12px; justify-content:center; border:1px solid var(--account-pink) !important; border-radius:5px; color:var(--account-pink) !important; }
    .logout-form { margin:0; }
    .logout-form button { width:calc(100% - 20px); background:#fff; cursor:pointer; font:inherit; }
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
    .empty-state { min-height:300px; display:grid; place-items:center; text-align:center; padding:40px 20px; }
    .empty-state i { display:block; margin-bottom:15px; color:#f3a6c7; font-size:54px; }
    .empty-state strong { display:block; margin-bottom:8px; font-size:17px; }
    .empty-state p { margin:0 0 18px; color:var(--account-muted); font-size:13px; }
    .empty-state a { display:inline-flex; align-items:center; gap:7px; min-height:38px; padding:0 18px; border-radius:5px; background:var(--account-pink); color:#fff; text-decoration:none; font-weight:900; font-size:13px; }
    .settings-wrap { padding:16px; display:grid; gap:16px; }
    .address-form, .address-list { border:1px solid var(--account-border); border-radius:8px; background:#fff; padding:16px; }
    .address-form h3, .address-list h3 { margin:0 0 12px; font-size:16px; }
    .address-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
    .address-grid label { display:grid; gap:6px; color:var(--account-muted); font-size:12px; font-weight:800; }
    .address-grid input, .address-grid textarea {
      width:100%; border:1px solid var(--account-border); border-radius:6px; padding:10px 12px; font:inherit; outline:none;
    }
    .address-grid textarea { min-height:78px; resize:vertical; grid-column:1/-1; }
    .address-save { min-height:42px; margin-top:12px; padding:0 18px; border:0; border-radius:6px; background:var(--account-pink); color:#fff; font-weight:900; cursor:pointer; }
    .address-cards { display:grid; gap:10px; }
    .address-card { border:1px solid var(--account-border); border-radius:7px; padding:12px; display:grid; gap:8px; }
    .address-card strong { font-size:15px; }
    .address-card p { margin:0; color:var(--account-muted); font-size:13px; line-height:1.5; }
    .address-card-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .address-card-actions button { min-height:34px; padding:0 12px; border-radius:5px; border:1px solid #f0aac8; background:#fff; color:var(--account-pink); font-weight:800; cursor:pointer; }
    .address-card .default-badge { display:inline-flex; width:max-content; padding:3px 8px; border-radius:999px; background:#fff1f7; color:var(--account-pink); font-size:11px; font-weight:900; }
    .qii-toast { position:fixed; left:50%; bottom:24px; transform:translateX(-50%) translateY(20px); opacity:0; pointer-events:none; z-index:8000; min-width:220px; max-width:calc(100% - 32px); padding:12px 16px; border-radius:999px; background:#ed4d94; color:#fff; text-align:center; font-weight:900; box-shadow:0 10px 24px rgba(237,77,148,.28); transition:.25s ease; }
    .qii-toast.show { opacity:1; transform:translateX(-50%) translateY(0); }
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
        grid-template-columns:repeat(3,1fr);
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

      .account-nav > .logout-link{
        display:flex !important;
        grid-column:1/-1;
        min-height:44px !important;
        flex-direction:row;
        margin:10px 10px 0;
        border-radius:10px !important;
      }

      .logout-form{
        grid-column:1/-1;
        margin:10px;
      }

      .logout-form .logout-link{
        width:100%;
        min-height:44px !important;
        flex-direction:row;
        margin:0;
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

      .address-grid{
        grid-template-columns:1fr;
      }

      .order-row{
        grid-template-columns:1fr;
      }

      .orders-list{
        padding:12px;
      }

      .order-row{
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
      <p><strong><?= htmlspecialchars($customer['name']) ?></strong>，这里会记录你的订单历史。</p>
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
        <div class="nav-section-title">账户设置</div>
        <a class="<?= $view === 'settings' ? 'active' : '' ?>" href="account.php?view=settings"><i class="fa-solid fa-gear"></i> 设置</a>
        <a class="logout-link" href="change_password.php"><i class="fa-solid fa-key"></i> 修改密码</a>
        <form class="logout-form" method="post" action="logout.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(qii_frontend_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
          <button class="logout-link" type="submit"><i class="fa-solid fa-right-from-bracket"></i> 退出登录</button>
        </form>
      </nav>
    </aside>

    <section class="account-main">
      <div class="account-stats">
        <div class="account-stat">
          <span class="stat-icon"><i class="fa-solid fa-bag-shopping"></i></span>
          <div><strong><?= (int)($stats['all_orders'] ?? 0) ?></strong><small>全部订单</small></div>
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

        <?php if ($view === 'settings'): ?>
          <div class="settings-wrap">
            <form class="address-form" method="post">
              <h3>新增地址配套</h3>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(qii_frontend_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="address_action" value="save_address">
              <div class="address-grid">
                <label>收件人名字
                  <input name="recipient_name" required placeholder="例如：陈小琪">
                </label>
                <label>电话
                  <input name="phone" placeholder="例如：0123456789">
                </label>
                <label>州属
                  <input name="state" placeholder="例如：Selangor">
                </label>
                <label>邮编
                  <input name="postcode" placeholder="例如：43000">
                </label>
                <label>详细地址
                  <textarea name="address" placeholder="例如：No. 12, Jalan Bunga"></textarea>
                </label>
              </div>
              <button class="address-save" type="submit">保存配套</button>
            </form>

            <section class="address-list">
              <h3>已保存配套</h3>
              <?php if (!$savedAddresses): ?>
                <div class="empty-state" style="min-height:160px;padding:24px 12px;">
                  <div><i class="fa-regular fa-address-card"></i><strong>还没有地址配套</strong><p>保存后，结账时可以直接选择。</p></div>
                </div>
              <?php else: ?>
                <div class="address-cards">
                  <?php foreach ($savedAddresses as $address): ?>
                    <article class="address-card">
                      <?php if (!empty($address['is_default'])): ?><span class="default-badge">默认</span><?php endif; ?>
                      <strong><?= htmlspecialchars((string)$address['recipient_name']) ?></strong>
                      <p>
                        <?= htmlspecialchars((string)($address['phone'] ?? '')) ?><br>
                        <?= htmlspecialchars((string)($address['address'] ?? '')) ?><br>
                        <?= htmlspecialchars(trim((string)($address['state'] ?? '') . ' ' . (string)($address['postcode'] ?? ''))) ?>
                      </p>
                      <?php if ((int)($address['id'] ?? 0) > 0): ?>
                        <div class="address-card-actions">
                          <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(qii_frontend_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="address_action" value="default_address">
                            <input type="hidden" name="address_id" value="<?= (int)$address['id'] ?>">
                            <button type="submit">设为默认</button>
                          </form>
                          <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(qii_frontend_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="address_action" value="delete_address">
                            <input type="hidden" name="address_id" value="<?= (int)$address['id'] ?>">
                            <button type="submit">删除</button>
                          </form>
                        </div>
                      <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>
          </div>
        <?php elseif (!$orders): ?>
          <div class="empty-state">
            <div>
              <i class="fa-solid fa-box-open"></i>
              <strong>还没有订单</strong>
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
<div id="qiiToast" class="qii-toast">已加入购物袋</div>

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

function qiiToast(message) {
  const toast = document.getElementById("qiiToast");
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(window.qiiAccountToastTimer);
  window.qiiAccountToastTimer = setTimeout(() => toast.classList.remove("show"), 1800);
}

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

async function submitAccountPanelForm(form) {
  const panel = document.querySelector(".account-panel");
  if (!panel) return;
  panel.style.opacity = ".45";
  panel.style.pointerEvents = "none";

  try {
    const response = await fetch(form.action || location.href, {
      method: "POST",
      headers: { "X-Requested-With": "XMLHttpRequest" },
      body: new FormData(form)
    });
    if (!response.ok) throw new Error("Request failed");

    const html = await response.text();
    const nextDocument = new DOMParser().parseFromString(html, "text/html");
    const nextPanel = nextDocument.querySelector(".account-panel");
    if (!nextPanel) throw new Error("Panel missing");

    panel.innerHTML = nextPanel.innerHTML;
    history.replaceState({ accountView: "settings" }, "", "account.php?view=settings");
    qiiToast("已保存");
  } catch (error) {
    form.submit();
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

</script>
</body>
</html>
