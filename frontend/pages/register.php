<?php
require_once __DIR__ . '/../../app/customers.php';
qii_start_session();
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
qii_ensure_customer_tables($pdo);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = '请填写姓名、正确邮箱，密码至少 6 位';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO customers (name, email, phone, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$name, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]);
            $customer = ['id' => (int)$pdo->lastInsertId(), 'name' => $name, 'email' => $email];
            $verifyToken = qii_create_customer_action_token($pdo, (int)$customer['id'], 'verify_email', 86400);
            $base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            qii_send_customer_mail($email, 'Qii.shop 验证邮箱', "请在 24 小时内打开：\n$base/verify_email.php?token=$verifyToken");
            qii_login_customer($customer, $pdo);
            header('Location: account.php');
            exit;
        } catch (Throwable $e) {
            $error = '这个邮箱已经注册过';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>注册会员 | qii.shop</title>
  <style>
    body { margin:0; font-family: Arial, sans-serif; background:#fff7fb; color:#4a3b43; }
    .auth-wrap { min-height:100vh; display:grid; place-items:center; padding:24px; }
    .auth-box { width:min(440px,100%); background:#fff; border:1px solid #f6bdd9; border-radius:18px; padding:28px; box-shadow:0 18px 45px rgba(214,82,142,.14); }
    h1 { margin:0 0 8px; color:#d94b8a; font-size:28px; }
    p { margin:0 0 22px; color:#846c78; }
    label { display:block; margin:14px 0 6px; font-weight:800; }
    input { width:100%; box-sizing:border-box; height:46px; border:1px solid #f3bfd3; border-radius:12px; padding:0 14px; outline:none; }
    .auth-error { background:#fff0f4; color:#c42e68; padding:10px 12px; border-radius:12px; margin-bottom:14px; }
    button { width:100%; height:46px; border:0; border-radius:12px; background:#e5679c; color:#fff; font-weight:900; margin-top:18px; cursor:pointer; }
    .auth-links { display:flex; justify-content:space-between; gap:12px; margin-top:16px; font-size:14px; }
    .auth-links a { color:#d94b8a; text-decoration:none; font-weight:800; }
  </style>
</head>
<body>
  <main class="auth-wrap">
    <form class="auth-box" method="post">
      <h1>注册会员</h1>
      <p>注册后订单会自动归档到你的账号。</p>
      <?php if ($error): ?><div class="auth-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <label for="name">姓名</label>
      <input id="name" name="name" required>
      <label for="email">邮箱</label>
      <input id="email" name="email" type="email" required>
      <label for="phone">手机号码</label>
      <input id="phone" name="phone">
      <label for="password">密码</label>
      <input id="password" name="password" type="password" minlength="6" required>
      <button type="submit">注册</button>
      <div class="auth-links">
        <a href="login.php">已有账号？登录</a>
        <a href="index.php">回到首页</a>
      </div>
    </form>
  </main>
</body>
</html>
