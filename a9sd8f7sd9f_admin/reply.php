<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

if (isset($_POST['delete_submit'])) {
    verify_csrf();
    $delete_id = (int)$_POST['delete_id'];
    if ($delete_id > 0) {
        $stmt = $pdo->prepare('DELETE FROM messages WHERE id=?');
        $ok = $stmt->execute([$delete_id]);
        header('Location: reply.php?msg=' . urlencode($ok ? '留言已删除' : '删除失败，请稍后重试'));
        exit;
    }
}

if (isset($_POST['reply_submit'])) {
    verify_csrf();
    $reply = trim($_POST['reply']);
    $id = (int)$_POST['id'];

    if ($reply !== '') {
        $stmt = $pdo->prepare('SELECT name, email, message FROM messages WHERE id=?');
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $update = $pdo->prepare('UPDATE messages SET reply=?, replied_at=NOW() WHERE id=?');
            $update->execute([$reply, $id]);

            $to = $user['email'];
            $subject = '来自 Qii.shop 的回复';
            $body = "亲爱的 {$user['name']}：\n\n"
                  . "感谢你给 Qii.shop 留言。\n\n"
                  . "你的留言：\n{$user['message']}\n\n"
                  . "我们的回复：\n{$reply}\n\n"
                  . "此信为系统自动发送，请勿直接回复。\n"
                  . "Qii.shop 团队";
            $headers = "From: Qii.shop <no-reply@qii.shop>\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $mailOk = mail($to, $subject, $body, $headers);
            $notice = $mailOk ? '已回复并发送邮件给用户' : '回复已保存，邮件发送失败，请检查 XAMPP mail() 设置';
            header('Location: reply.php?msg=' . urlencode($notice));
            exit;
        }
    }
}

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$limit = max(10, min(50, (int)($_GET['limit'] ?? 10)));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%");
}
if ($status === 'pending') {
    $where[] = "(reply IS NULL OR reply='')";
} elseif ($status === 'replied') {
    $where[] = "(reply IS NOT NULL AND reply<>'')";
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderSql = match ($sort) {
    'oldest' => 'created_at ASC',
    'name_asc' => 'name ASC',
    'pending_first' => "(reply IS NULL OR reply='') DESC, created_at DESC",
    default => 'created_at DESC',
};

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM messages $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $limit));

$stmt = $pdo->prepare("SELECT * FROM messages $whereSql ORDER BY $orderSql LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'all' => (int)$pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE reply IS NULL OR reply=''")->fetchColumn(),
    'replied' => (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE reply IS NOT NULL AND reply<>''")->fetchColumn(),
    'today' => (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
];
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>用户消息 | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/reply_admin.css?v=20260604">
</head>
<body>
<?php include 'includes/admin_header.php'; ?>

<main class="main reply-page">
  <header class="reply-topbar">
    <div class="title-wrap">
      <h1><i class="fa-solid fa-comments"></i> 用户消息</h1>
      <p>查看用户留言，快速回复客户问题。</p>
    </div>
    
  </header>

  <?php if ($msg): ?><div class="reply-msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <form class="reply-filter glass-panel" method="get">
    <label class="search-field">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="搜索用户名 / 邮箱 / 留言内容...">
    </label>
    <select name="status">
      <option value="">全部状态</option>
      <option value="pending" <?= $status==='pending'?'selected':'' ?>>待回复</option>
      <option value="replied" <?= $status==='replied'?'selected':'' ?>>已回复</option>
    </select>
    <button type="submit"><i class="fa-solid fa-filter"></i> 筛选</button>
  </form>

  <section class="reply-stats">
    <article><span><i class="fa-solid fa-comment-dots"></i></span><p>全部消息</p><strong><?= $stats['all'] ?></strong><small>总留言数</small></article>
    <article class="orange"><span><i class="fa-solid fa-clock"></i></span><p>待回复</p><strong><?= $stats['pending'] ?></strong><small>需要回复</small></article>
    <article class="purple"><span><i class="fa-solid fa-reply"></i></span><p>已回复</p><strong><?= $stats['replied'] ?></strong><small>已处理</small></article>
    <article class="green"><span><i class="fa-solid fa-check"></i></span><p>今日消息</p><strong><?= $stats['today'] ?></strong><small>今日新增</small></article>
  </section>

  <section class="message-list-card glass-panel">
    <div class="list-head">
      <strong>共 <?= $totalRows ?> 条消息</strong>
      <form method="get" class="sort-form">
        <?php foreach ($_GET as $key => $value): if (in_array($key, ['sort', 'page'], true)) continue; ?>
          <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
        <?php endforeach; ?>
        <select name="sort" onchange="this.form.submit()">
          <option value="newest" <?= $sort==='newest'?'selected':'' ?>>最新留言</option>
          <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>最早留言</option>
          <option value="pending_first" <?= $sort==='pending_first'?'selected':'' ?>>待回复优先</option>
          <option value="name_asc" <?= $sort==='name_asc'?'selected':'' ?>>用户名排序</option>
        </select>
      </form>
    </div>

    <div class="message-list">
      <?php if (!$messages): ?><div class="empty">暂无用户消息。</div><?php endif; ?>
      <?php foreach ($messages as $m): ?>
        <?php
          $hasReply = !empty($m['reply']);
          $initial = mb_substr(trim($m['name'] ?? 'U'), 0, 1, 'UTF-8');
        ?>
        <article class="message-card <?= $hasReply ? 'replied' : 'pending' ?>">
          <div class="avatar"><?= htmlspecialchars($initial) ?></div>
          <div class="message-main">
            <div class="message-user">
              <strong><?= htmlspecialchars($m['name'] ?? '') ?></strong>
              <span class="state-pill <?= $hasReply ? 'replied' : 'pending' ?>"><?= $hasReply ? '已回复' : '待回复' ?></span>
            </div>
            <a href="mailto:<?= htmlspecialchars($m['email'] ?? '') ?>"><?= htmlspecialchars($m['email'] ?? '') ?></a>
            <p><?= nl2br(htmlspecialchars($m['message'] ?? '')) ?></p>
            <span class="count-pill">留言内容: 1</span>
            <?php if ($hasReply): ?>
              <div class="reply-box"><strong>回复内容</strong><p><?= nl2br(htmlspecialchars($m['reply'])) ?></p><small><?= htmlspecialchars($m['replied_at'] ?? '') ?></small></div>
            <?php else: ?>
              <form method="post" class="reply-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <textarea name="reply" placeholder="输入你的回复..." required></textarea>
                <button type="submit" name="reply_submit"><i class="fa-solid fa-paper-plane"></i> 回复</button>
              </form>
            <?php endif; ?>
          </div>
          <aside class="message-side">
            <time><?= htmlspecialchars($m['created_at'] ?? '') ?></time>
            <strong><?= $hasReply ? '已回复' : '1 条留言' ?></strong>
            <?php if (!$hasReply): ?>
              <button type="button" class="reply-toggle" data-reply-toggle><i class="fa-solid fa-paper-plane"></i> 回复</button>
            <?php else: ?>
              <button type="button" class="reply-toggle muted" data-reply-toggle>查看回复</button>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('确定要删除这条留言吗？此操作不可恢复。');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$m['id'] ?>">
              <button type="submit" name="delete_submit" class="delete-btn"><i class="fa-solid fa-trash"></i></button>
            </form>
          </aside>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <footer class="reply-footer">
    <span>共 <?= $totalRows ?> 条记录</span>
    <nav>
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="<?= $i === $page ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
      <?php endfor; ?>
    </nav>
    <form method="get" class="limit-form">
      <?php foreach ($_GET as $key => $value): if ($key === 'limit') continue; ?>
        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
      <?php endforeach; ?>
      <span>每页显示</span>
      <select name="limit" onchange="this.form.submit()">
        <option value="10" <?= $limit===10?'selected':'' ?>>10 条</option>
        <option value="20" <?= $limit===20?'selected':'' ?>>20 条</option>
        <option value="50" <?= $limit===50?'selected':'' ?>>50 条</option>
      </select>
    </form>
  </footer>
</main>
<script src="js/product_admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-reply-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      var card = button.closest('.message-card');
      if (card) card.classList.toggle('reply-open');
    });
  });
});
</script>
</body>
</html>
