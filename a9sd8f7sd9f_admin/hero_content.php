<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/content_settings.php';

$pages = [
    'index' => [
        'label' => '首页 Index',
        'url' => '../index.php',
        'keys' => [
            'hero_title', 'hero_subtitle', 'hero_description', 'hero_button',
            'about_title', 'about_text', 'gift_title', 'gift_text', 'daily_title', 'daily_text',
        ],
    ],
    'shop' => [
        'label' => '商店 Shop',
        'url' => '../shop.php',
        'keys' => ['shop_title', 'shop_promo_title', 'shop_promo_text', 'shop_promo_button'],
    ],
    'contact' => [
        'label' => '联系 Contact',
        'url' => '../contact.php',
        'keys' => ['contact_title', 'contact_description', 'contact_button', 'contact_social_text'],
    ],
    'announcement' => [
        'label' => '公告弹窗',
        'url' => '../index.php?edit_popup=announcement',
        'keys' => ['announcement_title', 'announcement_intro', 'announcement_quality', 'announcement_storage', 'announcement_shipping', 'announcement_dispatch', 'announcement_warning', 'announcement_button'],
    ],
    'variant' => [
        'label' => '商品弹窗',
        'url' => '../shop.php?edit_popup=variant',
        'keys' => ['variant_choose_title', 'variant_quantity_title', 'variant_max_text', 'variant_shipping_text', 'variant_quality_text', 'variant_return_text', 'variant_cart_button'],
    ],
];

$page = $_GET['page'] ?? $_POST['page'] ?? 'index';
if (!isset($pages[$page])) $page = 'index';
$pageConfig = $pages[$page];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $settings = [];
    foreach ($pageConfig['keys'] as $key) {
        if (isset($_POST[$key])) {
            $settings[$key] = qii_sanitize_rich_text((string)$_POST[$key]);
        }
    }
    qii_save_content($pdo, $settings);
    header('Location: hero_content.php?page=' . rawurlencode($page) . '&saved=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>前台可视化编辑 | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/product_admin.css?v=20260607">
  <style>
    .visual-page { max-width: none; padding-bottom: 40px; }
    .desktop-only-note { display:flex; align-items:center; gap:10px; margin-bottom:16px; padding:13px 16px; border:1px solid #f2c9da; border-radius:12px; background:#fff6fa; color:#8a6175; font-weight:800; }
    .visual-toolbar { position:sticky; top:0; z-index:1200; display:flex; flex-wrap:wrap; align-items:center; gap:10px; padding:12px; margin-bottom:16px; border:1px solid #f3c8da; border-radius:14px; background:rgba(255,255,255,.96); box-shadow:0 10px 26px rgba(205,77,137,.1); backdrop-filter:blur(10px); }
    .page-switch { display:flex; gap:8px; margin-right:auto; }
    .page-switch a, .tool-control, .save-visual { min-height:40px; display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:0 13px; border:1px solid #f1c4d7; border-radius:10px; background:#fff; color:#695b70; text-decoration:none; font-weight:800; }
    .page-switch a.active { background:#ff4fa3; border-color:#ff4fa3; color:#fff; }
    .tool-control input { width:25px; height:25px; padding:0; border:0; background:transparent; cursor:pointer; }
    .tool-control select { border:0; outline:0; background:transparent; color:#695b70; font-weight:800; }
    .autosave-state { min-height:40px; display:inline-flex; align-items:center; gap:7px; padding:0 13px; border-radius:10px; background:#effbf4; color:#25845b; font-weight:800; }
    .canvas-shell { overflow:hidden; border:1px solid #f2c9da; border-radius:16px; background:#fff; box-shadow:0 16px 38px rgba(185,75,126,.1); }
    .canvas-head { min-height:50px; display:flex; align-items:center; justify-content:space-between; padding:0 16px; border-bottom:1px solid #f4d5e2; font-weight:800; color:#3b3044; }
    .canvas-head span { color:#9a7b8a; font-size:13px; }
    #visualFrame { display:block; width:100%; height:calc(100vh - 285px); min-height:650px; border:0; background:#fff7fb; }
    .mobile-block { display:none; }
    @media(max-width:900px) {
      .visual-toolbar, .canvas-shell { display:none; }
      .mobile-block { display:block; padding:28px 20px; border:1px solid #f3c8da; border-radius:16px; background:#fff; color:#6f6171; text-align:center; font-weight:800; }
    }
  </style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>
<main class="main visual-page">
  <header class="product-topbar"><div><h1>前台可视化编辑</h1><p>直接点击网页文字修改，像 WordPress 或 PPT 一样操作</p></div></header>
  <div class="desktop-only-note"><i class="fa-solid fa-desktop"></i> 前端编辑功能只可以在电脑端使用</div>
  <?php if (isset($_GET['saved'])): ?><div class="editor-alert success">内容已保存</div><?php endif; ?>

  <form method="post" id="visualForm">
    <?= csrf_field() ?>
    <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
    <input type="file" id="visualImagePicker" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
    <?php foreach ($pageConfig['keys'] as $key): ?>
      <input type="hidden" name="<?= htmlspecialchars($key) ?>" data-key="<?= htmlspecialchars($key) ?>">
    <?php endforeach; ?>

    <div class="visual-toolbar">
      <nav class="page-switch">
        <?php foreach ($pages as $key => $config): ?>
          <a href="hero_content.php?page=<?= urlencode($key) ?>" class="<?= $page === $key ? 'active' : '' ?>"><?= htmlspecialchars($config['label']) ?></a>
        <?php endforeach; ?>
      </nav>
      <label class="tool-control"><i class="fa-solid fa-palette"></i><input type="color" value="#d9488b" data-style="color"></label>
      <label class="tool-control"><i class="fa-solid fa-highlighter"></i><input type="color" value="#fff0a8" data-style="background-color"></label>
      <label class="tool-control"><select data-style="font-size"><option value="">字号</option><option value="12px">12</option><option value="14px">14</option><option value="16px">16</option><option value="18px">18</option><option value="20px">20</option><option value="24px">24</option><option value="28px">28</option><option value="32px">32</option><option value="40px">40</option><option value="48px">48</option></select></label>
      <label class="tool-control"><select data-style="font-weight"><option value="">粗细</option><option value="300">细</option><option value="400">正常</option><option value="600">半粗</option><option value="700">粗体</option><option value="900">特粗</option></select></label>
      <button class="tool-control" type="button" data-align="left" title="靠左"><i class="fa-solid fa-align-left"></i></button>
      <button class="tool-control" type="button" data-align="center" title="居中"><i class="fa-solid fa-align-center"></i></button>
      <button class="tool-control" type="button" data-align="right" title="靠右"><i class="fa-solid fa-align-right"></i></button>
      <span class="autosave-state" data-autosave-state><i class="fa-solid fa-cloud-check"></i> 已自动保存</span>
    </div>

    <section class="canvas-shell">
      <div class="canvas-head">用户端编辑画布 <span>点击带粉色边框的文字即可编辑</span></div>
      <iframe id="visualFrame" src="<?= htmlspecialchars($pageConfig['url']) ?><?= str_contains($pageConfig['url'], '?') ? '&' : '?' ?>visual_edit=1&t=<?= time() ?>"></iframe>
    </section>
  </form>
  <div class="mobile-block"><i class="fa-solid fa-desktop"></i><br><br>请使用电脑打开此功能进行前台编辑。</div>
</main>
<script>
var frame = document.getElementById('visualFrame');
var form = document.getElementById('visualForm');
var saveState = document.querySelector('[data-autosave-state]');
var activeElement = null;
var savedRange = null;
var saveTimer = null;

function setSaveState(text, saving) {
  saveState.innerHTML = '<i class="fa-solid ' + (saving ? 'fa-cloud-arrow-up' : 'fa-cloud-check') + '"></i> ' + text;
}
function autoSaveElement(el) {
  clearTimeout(saveTimer);
  setSaveState('正在保存...', true);
  saveTimer = setTimeout(function () {
    var data = new FormData();
    data.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
    data.append('key', el.dataset.contentKey);
    data.append('value', el.innerHTML);
    fetch('api_content_autosave.php', { method:'POST', body:data })
      .then(function (response) { return response.json(); })
      .then(function (result) { setSaveState(result.success ? '已自动保存' : '保存失败', false); })
      .catch(function () { setSaveState('保存失败', false); });
  }, 550);
}
function markDirty() {
  if (activeElement) autoSaveElement(activeElement);
}
function rememberSelection() {
  var win = frame.contentWindow;
  var selection = win.getSelection();
  if (selection.rangeCount && activeElement && activeElement.contains(selection.anchorNode)) {
    savedRange = selection.getRangeAt(0).cloneRange();
  }
}
function applyStyle(property, value) {
  if (!activeElement || !savedRange || savedRange.collapsed || !value) return;
  var doc = frame.contentDocument;
  var win = frame.contentWindow;
  var selection = win.getSelection();
  selection.removeAllRanges(); selection.addRange(savedRange);
  var span = doc.createElement('span'); span.style.setProperty(property, value);
  try { savedRange.surroundContents(span); }
  catch (e) { var fragment=savedRange.extractContents(); span.appendChild(fragment); savedRange.insertNode(span); }
  var range=doc.createRange(); range.selectNodeContents(span);
  selection.removeAllRanges(); selection.addRange(range); savedRange=range.cloneRange();
  markDirty();
}
frame.addEventListener('load', function () {
  var doc = frame.contentDocument;
  <?php if ($page === 'announcement'): ?>
  if (typeof frame.contentWindow.showAnnouncementPopup === 'function') frame.contentWindow.showAnnouncementPopup();
  <?php elseif ($page === 'variant'): ?>
  var openFirstVariant = function () {
    var firstProductButton = doc.querySelector('.choose-btn:not([disabled])');
    if (firstProductButton) {
      firstProductButton.click();
      return true;
    }
    return false;
  };
  if (!openFirstVariant()) {
    setTimeout(openFirstVariant, 700);
  }
  <?php endif; ?>
  setTimeout(function () {
  doc.addEventListener('click', function (event) {
    var editable = event.target.closest('[data-content-key], [data-image-key]');
    if (!editable) {
      event.preventDefault();
      event.stopPropagation();
    }
  }, true);
  doc.querySelectorAll('a, button, input, select, textarea, .cat-link, .product-card').forEach(function (element) {
    if (!element.closest('[data-content-key]')) {
      element.style.pointerEvents='none';
      element.setAttribute('tabindex','-1');
    }
  });
  doc.querySelectorAll('[data-content-key]').forEach(function (el) {
    el.contentEditable = 'true';
    el.style.outline = '2px dashed rgba(245,54,141,.45)';
    el.style.outlineOffset = '4px';
    el.style.cursor = 'text';
    el.addEventListener('focus', function () { activeElement=el; });
    el.addEventListener('click', function (event) { event.preventDefault(); event.stopPropagation(); });
    el.addEventListener('mouseup', rememberSelection);
    el.addEventListener('keyup', rememberSelection);
    el.addEventListener('input', markDirty);
  });
  doc.querySelectorAll('[data-image-key]').forEach(function (img) {
    img.style.outline='3px dashed rgba(72,155,255,.7)';
    img.style.outlineOffset='4px';
    img.style.cursor='pointer';
    img.title='点击更换图片';
    img.addEventListener('click', function (event) {
      event.preventDefault(); event.stopPropagation();
      document.getElementById('visualImagePicker').dataset.key=img.dataset.imageKey;
      document.getElementById('visualImagePicker').click();
    });
  });
  }, <?= $page === 'variant' ? '1200' : ($page === 'announcement' ? '500' : '0') ?>);
});
document.getElementById('visualImagePicker').addEventListener('change', function () {
  if (!this.files[0] || !this.dataset.key) return;
  setSaveState('正在上传图片...', true);
  var data=new FormData();
  data.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
  data.append('key', this.dataset.key);
  data.append('image', this.files[0]);
  fetch('api_content_image.php', {method:'POST', body:data})
    .then(function(r){return r.json();})
    .then(function(result){
      if(result.success){ frame.contentWindow.location.reload(); setSaveState('图片已更新', false); }
      else setSaveState('图片上传失败', false);
    }).catch(function(){setSaveState('图片上传失败', false);});
  this.value='';
});
document.querySelectorAll('[data-style]').forEach(function (control) {
  control.addEventListener('mousedown', rememberSelection);
  control.addEventListener('input', function () { applyStyle(control.dataset.style, control.value); });
  control.addEventListener('change', function () { applyStyle(control.dataset.style, control.value); if (control.tagName==='SELECT') control.value=''; });
});
document.querySelectorAll('[data-align]').forEach(function (button) {
  button.addEventListener('click', function () {
    if (!activeElement) return;
    var doc=frame.contentDocument;
    var wrapper=doc.createElement('span');
    wrapper.style.display='block';
    wrapper.style.textAlign=button.dataset.align;
    wrapper.innerHTML=activeElement.innerHTML;
    activeElement.innerHTML='';
    activeElement.appendChild(wrapper);
    markDirty();
  });
});
form.addEventListener('submit', function () {
  frame.contentDocument.querySelectorAll('[data-content-key]').forEach(function (el) {
    var input=form.querySelector('[data-key="'+el.dataset.contentKey+'"]');
    if (input) input.value=el.innerHTML;
  });
});
</script>
</body>
</html>
