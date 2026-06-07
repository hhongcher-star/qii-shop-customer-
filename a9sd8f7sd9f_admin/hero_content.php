<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/content_settings.php';

$defaults = [
    'hero_title' => 'Welcome to qii.shoppp',
    'hero_subtitle' => '发现每一份可爱的生活小物',
    'hero_description' => '让每一天，都有一点粉色的温柔与惊喜。',
    'hero_button' => '立即购物',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $settings = [];
    foreach ($defaults as $key => $default) {
        $value = trim((string)($_POST[$key] ?? ''));
        $settings[$key] = $value !== '' ? $value : $default;
    }
    qii_save_content($pdo, $settings);
    header('Location: hero_content.php?saved=1');
    exit;
}

$values = [];
foreach ($defaults as $key => $default) {
    $values[$key] = qii_content($pdo, $key, $default);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>首页 Hero 内容 | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/product_admin.css?v=20260607">
  <style>
    .hero-editor { max-width: 900px; }
    .hero-editor-card { padding: 28px; border: 1px solid #f6cddd; border-radius: 22px; background: #fff; box-shadow: 0 14px 36px rgba(205,77,137,.09); }
    .hero-editor-card h2 { margin: 0 0 22px; color: #29203d; }
    .hero-fields { display: grid; gap: 18px; }
    .hero-field { display: grid; gap: 8px; color: #62576c; font-weight: 800; }
    .hero-field input, .hero-field textarea { width: 100%; box-sizing: border-box; border: 1px solid #f3c6d8; border-radius: 14px; padding: 14px 16px; background: #fffafb; color: #29203d; font: inherit; outline: none; }
    .hero-field textarea { min-height: 100px; resize: vertical; }
    .hero-save-row { display: flex; justify-content: flex-end; margin-top: 22px; }
    .hero-save-row button { border: 0; cursor: pointer; }
    @media (max-width: 760px) {
      .hero-editor-card { padding: 20px 16px; border-radius: 18px; }
      .hero-save-row .primary-action { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>
<main class="main hero-editor">
  <header class="product-topbar"><div><h1>首页 Hero 内容</h1><p>保存后，前台首页会立即显示新文字</p></div></header>
  <?php if (isset($_GET['saved'])): ?><div class="editor-alert success">Hero 内容已保存</div><?php endif; ?>
  <form method="post" class="hero-editor-card">
    <?= csrf_field() ?>
    <h2><i class="fa-solid fa-pen-to-square"></i> Hero 文案</h2>
    <div class="hero-fields">
      <label class="hero-field">主标题<input name="hero_title" value="<?= htmlspecialchars($values['hero_title']) ?>" required></label>
      <label class="hero-field">副标题<input name="hero_subtitle" value="<?= htmlspecialchars($values['hero_subtitle']) ?>" required></label>
      <label class="hero-field">说明文字<textarea name="hero_description" required><?= htmlspecialchars($values['hero_description']) ?></textarea></label>
      <label class="hero-field">按钮文字<input name="hero_button" value="<?= htmlspecialchars($values['hero_button']) ?>" required></label>
    </div>
    <div class="hero-save-row"><button class="primary-action" type="submit"><i class="fa-solid fa-floppy-disk"></i> 保存 Hero 内容</button></div>
  </form>
</main>
</body>
</html>
