<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, status FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && $admin['status'] === 'active' && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([$_SESSION['admin_id']]);

            header('Location: dashboard.php');
            exit;
        }
    }

    admin_log('Failed admin login for username: ' . $username);
    $error = '用户名或密码错误';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>管理端登录 - Qii.shop</title>
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #fff5fa, #fff);
      font-family: Arial, "Microsoft YaHei", "PingFang SC", sans-serif;
      color: #2b2633;
    }

    .login-box {
      width: min(390px, calc(100% - 32px));
      background: #fff;
      border: 1px solid #f4dce8;
      border-radius: 24px;
      padding: 30px;
      box-shadow: 0 18px 46px rgba(244, 63, 143, .14);
    }

    .logo {
      display: block;
      width: 86px;
      height: 86px;
      object-fit: cover;
      border-radius: 24px;
      margin: 0 auto 14px;
      box-shadow: 0 12px 28px rgba(244, 63, 143, .18);
    }

    h1 {
      margin: 0 0 20px;
      text-align: center;
      color: #f43f8f;
      font-size: 26px;
    }

    label {
      display: block;
      margin: 14px 0 6px;
      color: #746b7d;
      font-size: 14px;
      font-weight: 700;
    }

    input {
      width: 100%;
      box-sizing: border-box;
      border: 1px solid #f4dce8;
      border-radius: 14px;
      padding: 12px;
      font-size: 15px;
    }

    button {
      width: 100%;
      margin-top: 20px;
      border: 0;
      border-radius: 16px;
      background: #f43f8f;
      color: #fff;
      padding: 13px;
      font-size: 16px;
      font-weight: 800;
      cursor: pointer;
      box-shadow: 0 12px 26px rgba(244, 63, 143, .22);
    }

    button:hover { background: #ec2f83; }

    .error {
      margin-bottom: 12px;
      padding: 10px 12px;
      border-radius: 14px;
      background: #fee2e2;
      color: #991b1b;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <form class="login-box" method="post">
    <img src="../images/products/qii.jpg" alt="Qii.shop Logo" class="logo">
    <h1>管理端登录</h1>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <label for="username">用户名</label>
    <input id="username" name="username" autocomplete="username" required>
    <label for="password">密码</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <button type="submit">登录</button>
  </form>
</body>
</html>
