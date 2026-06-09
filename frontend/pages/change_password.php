<?php
require_once __DIR__ . '/../../app/customers.php'; qii_start_session();
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php'; qii_ensure_customer_tables($pdo); qii_require_customer();
$message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $current=(string)($_POST['current_password']??''); $new=(string)($_POST['new_password']??'');
  $stmt=$pdo->prepare('SELECT password_hash FROM customers WHERE id=?'); $stmt->execute([qii_customer_id()]);
  if(!password_verify($current,(string)$stmt->fetchColumn())) $error='当前密码不正确';
  elseif(strlen($new)<8) $error='新密码至少 8 位';
  else { $pdo->prepare('UPDATE customers SET password_hash=?,updated_at=NOW() WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),qii_customer_id()]); $pdo->prepare('DELETE FROM customer_remember_tokens WHERE customer_id=?')->execute([qii_customer_id()]); qii_issue_remember_token($pdo,qii_customer_id()); $message='密码已修改'; }
}
?>
<!doctype html><html lang="zh-CN"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>修改密码</title><style>body{font-family:Arial;background:#fff5fa}.box{max-width:430px;margin:60px auto;background:#fff;padding:28px;border:1px solid #f3cadb;border-radius:10px}input,button{width:100%;box-sizing:border-box;height:45px;margin:8px 0;padding:10px}button{border:0;background:#ed4d94;color:#fff;font-weight:bold}.msg{padding:10px;background:#eefaf2}.err{padding:10px;background:#fff0f4;color:#c32b66}a{color:#ed4d94}</style><body><form class="box" method="post"><h1>修改密码</h1><?php if($message):?><div class="msg"><?=$message?></div><?php endif;?><?php if($error):?><div class="err"><?=$error?></div><?php endif;?><input name="current_password" type="password" placeholder="当前密码" required><input name="new_password" type="password" minlength="8" placeholder="新密码（至少 8 位）" required><button>保存新密码</button><a href="account.php">返回我的账号</a></form></body></html>
