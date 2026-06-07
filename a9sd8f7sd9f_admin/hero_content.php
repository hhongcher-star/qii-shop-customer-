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
            'hero_title' => ['主标题', 'Welcome to qii.shoppp', 'rich'],
            'hero_subtitle' => ['副标题', '发现每一份可爱的生活小物', 'rich'],
            'hero_description' => ['说明文字', '让每一天，都有一点粉色的温柔与惊喜。', 'rich'],
            'hero_button' => ['按钮文字', '立即购物', 'rich'],
            'hero_image_alt' => ['Hero 图片说明', 'Qiqi with Cart', 'input'],
            'about_title' => ['关于区标题', '关于 qii.shoppp 💌', 'rich'],
            'about_text' => ['关于区正文', "qii.shoppp 是一个关于温柔与日常的小角落。<br>我们相信，每个女孩都值得一点被生活宠爱的可爱。<br>每件商品，都像一份心意——小小、但刚刚好。", 'rich'],
            'about_image_alt' => ['关于区图片说明', 'Qiqi Bag', 'input'],
            'gift_title' => ['礼物区标题', '🎁 每一份礼物', 'rich'],
            'gift_text' => ['礼物区正文', "每一份礼物都承载着特别的心意。<br>我们为你准备的，不只是商品，而是一份温柔的陪伴。<br>让可爱成为生活的一部分。", 'rich'],
            'gift_image_alt' => ['礼物区图片说明', 'Qiqi Gift', 'input'],
            'daily_title' => ['日常区标题', '🌸 粉色的日常', 'rich'],
            'daily_text' => ['日常区正文', "每一个小物件，都能让生活多一点甜。<br>我们希望，在你的每一天里，都能遇见一点粉色的温柔。<br>qii.shoppp — 温柔从这里开始。", 'rich'],
            'daily_image_alt' => ['日常区图片说明', 'Qiqi Flower', 'input'],
            'hero_button_color' => ['按钮颜色', '#E5679C', 'color'],
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
    foreach ($pageConfig['fields'] as $key => [$label, $default, $type]) {
        $value = trim((string)($_POST[$key] ?? ''));
        $value = $value !== '' ? $value : $default;
        $settings[$key] = $type === 'rich' ? qii_sanitize_rich_text($value) : $value;
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
    .content-field input[type="color"] { height: 48px; padding: 5px; cursor: pointer; }
    .rich-toolbar { display: flex; align-items: center; gap: 8px; margin-bottom: 7px; }
    .rich-tool { display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0 10px; border: 1px solid #f3c6d8; border-radius: 9px; background: #fff; color: #62576c; font-size: 12px; cursor: pointer; }
    .rich-tool input { width: 24px; height: 24px; padding: 0; border: 0; background: transparent; cursor: pointer; }
    .rich-editor { min-height: 48px; padding: 12px 14px; border: 1px solid #f3c6d8; border-radius: 12px; background: #fffafb; color: #29203d; font-weight: 600; line-height: 1.55; outline: none; }
    .rich-editor[data-multiline="1"] { min-height: 100px; }
    .rich-editor:focus { border-color: #ff4fa3; box-shadow: 0 0 0 3px rgba(255,79,163,.1); }
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
          <?php if ($type === 'rich'): ?>
            <div class="content-field">
              <span><?= htmlspecialchars($label) ?></span>
              <div class="rich-toolbar">
                <label class="rich-tool"><i class="fa-solid fa-palette"></i> 字色 <input type="color" value="#d9488b" data-rich-color="foreColor"></label>
                <label class="rich-tool"><i class="fa-solid fa-highlighter"></i> Highlight <input type="color" value="#fff0a8" data-rich-color="hiliteColor"></label>
              </div>
              <div class="rich-editor" contenteditable="true" data-rich-editor data-multiline="<?= str_contains($key, 'text') || str_contains($key, 'description') ? '1' : '0' ?>"><?= $values[$key] ?></div>
              <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($values[$key]) ?>" data-rich-input>
            </div>
          <?php else: ?>
            <label class="content-field"><?= htmlspecialchars($label) ?>
            <?php if ($type === 'textarea'): ?>
              <textarea name="<?= htmlspecialchars($key) ?>" required><?= htmlspecialchars($values[$key]) ?></textarea>
            <?php elseif ($type === 'color'): ?>
              <input type="color" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($values[$key]) ?>" required>
            <?php else: ?>
              <input name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($values[$key]) ?>" required>
            <?php endif; ?>
            </label>
          <?php endif; ?>
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
<script>
document.querySelectorAll('[data-rich-editor]').forEach(function (editor) {
  var field = editor.closest('.content-field');
  var hidden = field.querySelector('[data-rich-input]');
  var savedRange = null;

  function rememberSelection() {
    var selection = window.getSelection();
    if (selection.rangeCount && editor.contains(selection.anchorNode)) {
      savedRange = selection.getRangeAt(0).cloneRange();
    }
  }

  editor.addEventListener('mouseup', rememberSelection);
  editor.addEventListener('keyup', rememberSelection);
  editor.addEventListener('input', function () { hidden.value = editor.innerHTML; });

  field.querySelectorAll('[data-rich-color]').forEach(function (picker) {
    picker.addEventListener('mousedown', function () { rememberSelection(); });
    picker.addEventListener('input', function () {
      editor.focus();
      if (savedRange) {
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(savedRange);
      }
      document.execCommand(picker.dataset.richColor, false, picker.value);
      hidden.value = editor.innerHTML;
      rememberSelection();
    });
  });
});

document.querySelector('.content-editor-card').addEventListener('submit', function () {
  document.querySelectorAll('[data-rich-editor]').forEach(function (editor) {
    editor.closest('.content-field').querySelector('[data-rich-input]').value = editor.innerHTML;
  });
});
</script>
</body>
</html>
