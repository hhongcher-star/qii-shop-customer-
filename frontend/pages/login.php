<?php
require_once __DIR__ . '/../../app/customers.php';
qii_start_session();
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
qii_ensure_customer_tables($pdo);

$next = trim((string)($_GET['next'] ?? $_POST['next'] ?? 'account.php'));
if ($next === '' || preg_match('#^https?://#i', $next)) {
    $next = 'account.php';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer && ($customer['status'] ?? '') === 'active' && password_verify($password, (string)$customer['password_hash'])) {
        qii_login_customer($customer, $pdo);
        $pdo->prepare('UPDATE customers SET last_login_at=NOW() WHERE id=?')->execute([(int)$customer['id']]);
        header('Location: ' . $next);
        exit;
    }

    $error = '邮箱或密码不正确';
}

$googleClientId = trim((string)(getenv('QII_GOOGLE_CLIENT_ID') ?: ''));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会员登录 | qii.shop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * { box-sizing:border-box; }
    body { margin:0; font-family:Arial, sans-serif; background:#fffafd; color:#433740; }
    .auth-page { min-height:620px; display:grid; place-items:center; padding:46px 20px; background:linear-gradient(180deg,#fffafd,#fff2f7); }
    .auth-shell { width:min(920px,100%); display:grid; grid-template-columns:1fr 420px; background:#fff; border:1px solid #f6dce7; border-radius:8px; overflow:hidden; box-shadow:0 20px 50px rgba(201,75,130,.12); }
    .auth-art { position:relative; min-height:520px; display:flex; align-items:flex-end; padding:38px; overflow:hidden; background:linear-gradient(145deg,#fff2f7,#ffe4f0); }
    .auth-art::after { content:""; position:absolute; inset:35px 20px 80px; background:url("images/27.png") center/contain no-repeat; }
    .auth-art-copy { position:relative; z-index:1; }
    .auth-art h2 { margin:0 0 8px; font-size:26px; }
    .auth-art p { margin:0; color:#846c78; line-height:1.7; }
    .auth-box { padding:42px 36px; }
    .auth-box h1 { margin:0 0 8px; color:#d93880; font-size:27px; }
    .auth-box > p { margin:0 0 24px; color:#846c78; font-size:14px; }
    label { display:block; margin:14px 0 7px; font-weight:800; font-size:13px; }
    input { width:100%; height:46px; border:1px solid #efccd9; border-radius:6px; padding:0 13px; outline:none; }
    input:focus { border-color:#ed4d94; box-shadow:0 0 0 3px rgba(237,77,148,.1); }
    .auth-error { background:#fff0f4; color:#c42e68; padding:10px 12px; border-radius:6px; margin-bottom:14px; font-size:13px; }
    .auth-btn, .google-btn { width:100%; min-height:46px; display:flex; align-items:center; justify-content:center; gap:10px; border-radius:6px; font-weight:900; cursor:pointer; }
    .auth-btn { border:0; background:#ed4d94; color:#fff; margin-top:20px; }
    .divider { display:flex; align-items:center; gap:12px; margin:20px 0; color:#b29eaa; font-size:12px; }
    .divider::before,.divider::after { content:""; height:1px; flex:1; background:#f0dce5; }
    .google-btn { border:1px solid #dfdfe3; background:#fff; color:#4b4650; }
    .google-btn[disabled] { cursor:not-allowed; color:#9a9097; background:#fafafa; }
    .google-note { margin:8px 0 0; text-align:center; color:#aa8f9c; font-size:11px; }
    .auth-links { display:flex; justify-content:space-between; gap:12px; margin-top:20px; font-size:13px; }
    .auth-links a { color:#d93880; text-decoration:none; font-weight:800; }
    @media (max-width:760px) {
      .auth-page { min-height:0; padding:20px 12px 38px; }
      .auth-shell { grid-template-columns:1fr; }
      .auth-art { display:none; }
      .auth-box { padding:28px 22px; }
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="auth-page">
  <section class="auth-shell">
    <div class="auth-art">
      <div class="auth-art-copy">
        <h2>欢迎回来</h2>
        <p>登录后可以查看全部订单、管理存单，并使用收藏夹和最近浏览。</p>
      </div>
    </div>

    <form class="auth-box" method="post">
      <h1>会员登录</h1>
      <p>登录你的 qii.shop 会员账号。</p>
      <?php if ($error): ?><div class="auth-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
      <label for="email">邮箱</label>
      <input id="email" name="email" type="email" autocomplete="email" required>
      <label for="password">密码</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
      <button class="auth-btn" type="submit"><i class="fa-solid fa-right-to-bracket"></i> 登录</button>

      <div class="divider">或</div>
      <?php if ($googleClientId !== ''): ?>
        <button class="google-btn" type="button" data-google-login><i class="fa-brands fa-google"></i> 使用 Google 登录</button>
        <p class="google-note">Google Client ID 已读取，还需要连接后端 token 验证接口。</p>
      <?php else: ?>
        <button class="google-btn" type="button" disabled><i class="fa-brands fa-google"></i> 使用 Google 登录</button>
        <p class="google-note">配置 QII_GOOGLE_CLIENT_ID 后启用。</p>
      <?php endif; ?>

      <div class="auth-links">
        <a href="register.php">还没有账号？注册</a>
        <a href="index.php">回到首页</a>
        <a href="forgot_password.php">忘记密码</a>
      </div>
    </form>
  </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
