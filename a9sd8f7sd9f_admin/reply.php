<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

/* =============================
   ✉️ 回复留言逻辑 + 邮件通知
============================= */
/* =============================
   🗑 删除留言
============================= */
if (isset($_POST['delete_submit'])) {
  $delete_id = intval($_POST['delete_id']);
  if ($delete_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id=?");
    if ($stmt->execute([$delete_id])) {
      $msg = "🗑 已成功删除留言！";
    } else {
      $msg = "⚠️ 删除失败，请稍后重试";
    }
  }
}

/* =============================
   回复留言
============================= */
if (isset($_POST['reply_submit'])) {
  $reply = trim($_POST['reply']);
  $id = intval($_POST['id']);

  if ($reply !== "") {
    // 1️⃣ 获取原始留言数据（包含邮箱和姓名）
    $stmt = $pdo->prepare("SELECT name, email, message FROM messages WHERE id=?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
      // 2️⃣ 更新数据库
      $update = $pdo->prepare("UPDATE messages SET reply=?, replied_at=NOW() WHERE id=?");
      $update->execute([$reply, $id]);

      // 3️⃣ 发送邮件通知用户
      $to = $user['email'];
      $subject = "来自 Qii.shop 的回复 💌";
      $body = "亲爱的 {$user['name']}：\n\n"
            . "感谢你给 Qii.shop 留言 💕\n"
            . "以下是你之前的留言内容：\n"
            . "-----------------------------------\n"
            . "{$user['message']}\n"
            . "-----------------------------------\n\n"
            . "我们的回复如下：\n"
            . "{$reply}\n\n"
            . "感谢你的支持，希望你的每一天都被温柔包围 🌸\n\n"
            . "此信为系统自动发送，请勿直接回复。\n"
            . "— Qii.shop 团队 💗";

      $headers = "From: Qii.shop <no-reply@qii.shop>\r\n";
      $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

      // 尝试发送邮件
      if (mail($to, $subject, $body, $headers)) {
        $msg = "✅ 已回复并成功发送邮件给用户！";
      } else {
        $msg = "⚠️ 回复已保存，但邮件发送失败（请检查主机 mail() 功能）";
      }
    }
  }
}

/* =============================
   📬 数据加载
============================= */
$messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>用户留言回复 | Qii.shop</title>
<style>
body {
  font-family:'Inter','Noto Sans SC',sans-serif;
  background:#18191C;
  color:#f5f5f5;
  margin:0;
}
main {
  margin-left:230px;
  padding:25px;
}
h1 {
  font-size:22px;
  margin-top:0;
  color:#000;
}
table {
  width:100%;
  border-collapse:collapse;
  background:#1f1f23;
  border-radius:6px;
  overflow:hidden;
  margin-top:15px;
}
th,td {
  border:1px solid #333;
  padding:10px;
  text-align:left;
  color:#eee;
  vertical-align:top;
}
th {
  background:#292a2d;
  color:#fff;
  font-weight:600;
}
textarea {
  width:100%;
  background:#2a2b2f;
  color:#fff;
  border:1px solid #555;
  border-radius:4px;
  padding:8px;
  font-size:14px;
  resize:vertical;
  min-height:60px;
}
button {
  background:#00AEEF;
  border:none;
  color:white;
  padding:6px 12px;
  border-radius:6px;
  cursor:pointer;
  font-size:14px;
  margin-top:5px;
}
button:hover {
  background:#0090cc;
}
.msg {
  background:#2E7D32;
  padding:10px;
  border-radius:6px;
  margin-bottom:15px;
  color:#fff;
  font-weight:500;
}
.reply-box {
  background:#232429;
  padding:10px;
  border-radius:6px;
  margin-top:8px;
}
.status {
  font-weight:500;
  color:#ffb6c1;
}
</style>
</head>
<body>

<?php include 'includes/admin_header.php'; ?>

<main>
  <h1>📨 用户留言回复中心</h1>
  <?php if(isset($msg)): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <table>
    <tr>
      <th>姓名</th>
      <th>Email</th>
      <th>留言内容</th>
      <th>回复</th>
      <th>时间</th>
      <th>🗑 删除</th>
    </tr>

    <?php foreach($messages as $m): ?>
    <tr>
      <td><?= htmlspecialchars($m['name']) ?></td>
      <td><?= htmlspecialchars($m['email']) ?></td>
      <td><?= nl2br(htmlspecialchars($m['message'])) ?></td>

      <td>
        <?php if (!empty($m['reply'])): ?>
          <div class="reply-box">
            <div><strong>已回复：</strong><?= nl2br(htmlspecialchars($m['reply'])) ?></div>
            <div style="color:#aaa; font-size:13px;">🕒 <?= $m['replied_at'] ?></div>
          </div>
        <?php else: ?>
          <form method="post">
            <input type="hidden" name="id" value="<?= $m['id'] ?>">
            <textarea name="reply" placeholder="输入你的回复..." required></textarea>
            <button type="submit" name="reply_submit">💬 回复</button>
          </form>
        <?php endif; ?>
      </td>

      <td>
        <?= $m['created_at'] ?><br>
        <span class="status"><?= empty($m['reply']) ? "待回复" : "已回复" ?></span>
      </td>

      <td>
        <form method="post" onsubmit="return confirm('⚠️ 确定要删除这条留言吗？此操作不可恢复');">
          <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
          <button type="submit" name="delete_submit" style="background:#d9534f;">🗑 删除</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</main>

</body>
</html>
