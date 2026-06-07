<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/content_settings.php';

$pages = [
    'index' => [
        'label' => '首页 Index',
        'url' => '../index.php',
        'fields' => [
            'hero_title' => ['主标题', 'Welcome to qii.shoppp', 'input'],
            'hero_subtitle' => ['副标题', '发现每一份可爱的生活小物', 'input'],
            'hero_description' => ['说明文字', '让每一天，都有一点粉色的温柔与惊喜。', 'textarea'],
            'hero_button' => ['按钮文字', '立即购物', 'input'],
        ],
    ],
    'shop' => [
        'label' => '商店 Shop',
        'url' => '../shop.php',
        'fields' => [
            'shop_title' => ['商店主标题', '🌸 可爱生活选物', 'input'],
            'shop_promo_title' => ['手机横幅标题', '新品可爱小物上线啦 ✨', 'input'],
            'shop_promo_text' => ['手机横幅说明', '可爱治愈 · 限时优惠', 'input'],
            'shop_promo_button' => ['手机横幅按钮', '立即选购 ›', 'input'],
        ],
    ],
    'contact' => [
        'label' => '联系 Contact',
        'url' => '../contact.php',
        'fields' => [
            'contact_title' => ['页面标题', '联系我们 📬', 'input'],
            'contact_description' => ['介绍文字', "如果你对我们的商品有任何疑问，\n或者只是想聊聊生活里的小确幸，\n欢迎随时来和我们说说话 🌷\n\n有时候，一句“嗨～”也能让一天变得更可爱。", 'textarea'],
            'contact_button' => ['发送按钮文字', '发送', 'input'],
            'contact_social_text' => ['社交平台说明', '或在社交平台找到我们 🌸', 'input'],
        ],
    ],
];

$page = $_GET['page'] ?? $_POST['page'] ?? 'index';
if (!isset($pages[$page])) {
    $page = 'index';
}
$pageConfig = $pages[$page];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $settings = [];
    foreach ($pageConfig['fields'] as $key => [$label, $default]) {
        $value = trim((string)($_POST[$key] ?? ''));
        $settings[$key] = $value !== '' ? $value : $default;
    }
    qii_save_content($pdo, $settings);
    header('Location: hero_content.php?page=' . rawurlencode($page) . '&saved=1');
    exit;
}

$values = [];
foreach ($pageConfig['fields'] as $key => [$label, $default]) {
    $values[$key] = qii_content($pdo, $key, $default);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>前台内容 | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/product_admin.css?v=20260607">
  <style>
    .content-editor-page { max-width: none; }
    .page-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
    .page-tabs a { min-height: 44px; padding: 0 18px; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #f4c9db; border-radius: 12px; background: #fff; color: #6d6173; text-decoration: none; font-weight: 800; }
    .page-tabs a.active { border-color: #ff4fa3; background: #ff4fa3; color: #fff; box-shadow: 0 10px 22px rgba(255,79,163,.2); }
    .content-workspace { display: grid; grid-template-columns: minmax(360px, .78fr) minmax(520px, 1.22fr); gap: 20px; align-items: start; }
    .content-editor-card, .preview-panel { border: 1px solid #f5cddd; border-radius: 18px; background: #fff; box-shadow: 0 14px 34px rgba(205,77,137,.08); overflow: hidden; }
    .content-editor-card { padding: 24px; }
    .content-editor-card h2, .preview-head h2 { margin: 0; color: #29203d; font-size: 21px; }
    .content-fields { display: grid; gap: 16px; margin-top: 20px; }
    .content-field { display: grid; gap: 7px; color: #62576c; font-weight: 800; }
    .content-field input, .content-field textarea { width: 100%; box-sizing: border-box; border: 1px solid #f3c6d8; border-radius: 12px; padding: 12px 14px; background: #fffafb; color: #29203d; font: inherit; outline: none; }
    .content-field textarea { min-height: 120px; resize: vertical; line-height: 1.6; }
    .content-save-row { display: flex; justify-content: flex-end; margin-top: 20px; }
    .content-save-row button { border: 0; cursor: pointer; }
    .preview-panel { position: sticky; top: 20px; }
    .preview-head { min-height: 58px; padding: 0 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f5d9e4; }
    .preview-head a { color: #f5368d; text-decoration: none; font-weight: 800; }
    .preview-frame { display: block; width: 100%; height: 680px; border: 0; background: #fff7fb; }
    @media (max-width: 1050px) {
      .content-workspace { grid-template-columns: 1fr; }
      .preview-panel { position: static; }
      .preview-frame { height: 620px; }
    }
    @media (max-width: 760px) {
      .page-tabs { display: grid; grid-template-columns: repeat(3, 1fr); }
      .page-tabs a { min-width: 0; padding: 0 8px; justify-content: center; font-size: 12px; }
      .content-editor-card { padding: 18px 14px; }
      .content-save-row .primary-action { width: 100%; justify-content: center; }
      .preview-frame { height: 560px; }
    }
  </style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>
<main class="main content-editor-page">
  <header class="product-topbar">
    <div><h1>前台内容</h1><p>选择页面，在左边修改，并在右边查看真实用户页面</p></div>
  </header>

  <nav class="page-tabs" aria-label="选择前台页面">
    <?php foreach ($pages as $key => $config): ?>
      <a href="hero_content.php?page=<?= urlencode($key) ?>" class="<?= $page === $key ? 'active' : '' ?>">
        <i class="fa-solid <?= $key === 'index' ? 'fa-house' : ($key === 'shop' ? 'fa-store' : 'fa-envelope') ?>"></i>
        <?= htmlspecialchars($config['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if (isset($_GET['saved'])): ?><div class="editor-alert success">内容已保存，右侧预览已更新</div><?php endif; ?>

  <section class="content-workspace">
    <form method="post" class="content-editor-card">
      <?= csrf_field() ?>
      <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
      <h2><i class="fa-solid fa-pen-to-square"></i> 修改 <?= htmlspecialchars($pageConfig['label']) ?></h2>
      <div class="content-fields">
        <?php foreach ($pageConfig['fields'] as $key => [$label, $default, $type]): ?>
          <label class="content-field"><?= htmlspecialchars($label) ?>
            <?php if ($type === 'textarea'): ?>
              <textarea name="<?= htmlspecialchars($key) ?>" required><?= htmlspecialchars($values[$key]) ?></textarea>
            <?php else: ?>
              <input name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($values[$key]) ?>" required>
            <?php endif; ?>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="content-save-row"><button class="primary-action" type="submit"><i class="fa-solid fa-floppy-disk"></i> 保存此页面</button></div>
    </form>

    <aside class="preview-panel">
      <div class="preview-head">
        <h2>用户端预览</h2>
        <a href="<?= htmlspecialchars($pageConfig['url']) ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> 打开</a>
      </div>
      <iframe class="preview-frame" src="<?= htmlspecialchars($pageConfig['url']) ?>?preview=<?= time() ?>" title="<?= htmlspecialchars($pageConfig['label']) ?> 用户端预览"></iframe>
    </aside>
  </section>
</main>
</body>
</html>
