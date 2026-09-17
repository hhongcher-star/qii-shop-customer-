<?php
require_once __DIR__ . '/../../app/customers.php';
qii_start_session();
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
qii_ensure_customer_tables($pdo);

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    qii_verify_frontend_csrf();
    $lastSent = (int)($_SESSION['password_reset_last_sent'] ?? 0);
    if (time() - $lastSent < 60) {
        $error = '请稍等一分钟后再试';
    } else {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $stmt = $pdo->prepare('SELECT id FROM customers WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $id = $stmt->fetchColumn();
        if ($id) {
            $token = qii_create_customer_action_token($pdo, (int)$id, 'password_reset', 3600);
            $base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            qii_send_customer_mail($email, 'Qii.shop 重设密码', "请在一小时内打开：\n$base/reset_password.php?token=$token");
        }
        $_SESSION['password_reset_last_sent'] = time();
        $sent = true;
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>忘记密码</title>
<style>
body{font-family:Arial;background:#fff5fa}.box{max-width:430px;margin:60px auto;background:#fff;padding:28px;border:1px solid #f3cadb;border-radius:10px}input,button{width:100%;box-sizing:border-box;height:45px;margin:8px 0;padding:10px}button{border:0;background:#ed4d94;color:#fff;font-weight:bold}.err{padding:10px;background:#fff0f4;color:#c32b66}
</style>
<body>
<form class="box" method="post">
  <h1>忘记密码</h1>
  <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <?php if ($sent): ?>
    <p>如果邮箱存在，重设链接已经发送。</p>
  <?php else: ?>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(qii_frontend_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input name="email" type="email" placeholder="注册邮箱" autocomplete="email" required>
    <button type="submit">发送重设链接</button>
  <?php endif; ?>
</form>
</body>
</html>
