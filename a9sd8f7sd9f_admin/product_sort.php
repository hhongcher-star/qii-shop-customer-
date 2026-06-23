<?php
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/categories.php';

$categories = qii_categories($pdo, false);
$category = trim((string)($_GET['cat'] ?? ''));
if (!isset($categories[$category])) {
    $category = array_key_first($categories) ?? '';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>商品排序 | Qii.shop Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/product_admin.css?v=20260612">
  <style>
    .sort-page { max-width:none; padding-bottom:40px; }
    .sort-toolbar { display:flex; align-items:center; gap:12px; margin:18px 0; padding:16px; border:1px solid #f2c9da; border-radius:16px; background:#fff; }
    .sort-toolbar select { min-width:220px; height:46px; padding:0 14px; border:1px solid #f1c4d7; border-radius:12px; background:#fff; color:#4c4053; font-weight:800; }
    .sort-state { margin-left:auto; color:#8b7481; font-weight:800; }
    .sort-state.dirty { color:#e43f88; }
    .sort-save { min-height:46px; padding:0 20px; border:0; border-radius:12px; background:#ff3e96; color:#fff; font-weight:900; cursor:pointer; }
    .sort-canvas { overflow:hidden; border:1px solid #f2c9da; border-radius:18px; background:#fff; box-shadow:0 16px 38px rgba(185,75,126,.1); }
    .sort-canvas-head { display:flex; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #f4d5e2; font-weight:800; }
    .sort-help { margin:0; padding:12px 18px; border-bottom:1px solid #f4d5e2; background:#fff8fb; color:#7c6572; font-weight:700; }
    #shopFrame { display:block; width:100%; height:calc(100vh - 285px); min-height:680px; border:0; background:#fff7fb; }
    .desktop-only-note { display:flex; align-items:center; gap:10px; margin:18px 0; padding:13px 16px; border:1px solid #f2c9da; border-radius:12px; background:#fff6fa; color:#8a6175; font-weight:800; }
    .mobile-block { display:none; }
    @media(max-width:900px) {
      .desktop-only-note, .sort-toolbar, .sort-canvas { display:none; }
      .mobile-block { display:block; margin-top:18px; padding:32px 20px; border:1px solid #f3c8da; border-radius:16px; background:#fff; color:#6f6171; text-align:center; font-weight:800; }
    }
  </style>
</head>
<body>
<?php include 'includes/admin_header.php'; ?>
<main class="main sort-page">
  <header class="product-topbar">
    <div><h1>商品排序</h1><p>选择分类后，直接拖动前台商品卡片调整显示顺序</p></div>
  </header>

  <div class="desktop-only-note"><i class="fa-solid fa-desktop"></i> 商品排序功能只可以在电脑端使用</div>

  <div class="sort-toolbar">
    <label>
      <select id="sortCategory" aria-label="选择商品分类">
        <?php foreach ($categories as $key => $row): ?>
          <option value="<?= htmlspecialchars($key) ?>" <?= $key === $category ? 'selected' : '' ?>>
            <?= htmlspecialchars(trim(($row['emoji'] ?? '') . ' ' . ($row['name'] ?? $key))) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <span id="sortState" class="sort-state">拖动商品即可调整</span>
    <button id="saveSort" class="sort-save" type="button"><i class="fa-solid fa-floppy-disk"></i> 保存顺序</button>
  </div>

  <section class="sort-canvas">
    <div class="sort-canvas-head"><span>前台商品页预览</span><span>按住商品卡片拖动</span></div>
    <p class="sort-help">也可以在每个商品右侧的“排序”数字框输入目标位置，例如输入 1 后，商品会移动到第一位。调整完成后点击“保存顺序”。</p>
    <iframe id="shopFrame" src="../shop.php?cat=<?= rawurlencode($category) ?>&sort_edit=1&t=<?= time() ?>"></iframe>
  </section>
  <div class="mobile-block"><i class="fa-solid fa-desktop"></i><br><br>请使用电脑打开商品排序功能。</div>
</main>

<form id="csrfForm" hidden><?= csrf_field() ?></form>
<script>
const categorySelect = document.getElementById('sortCategory');
const frame = document.getElementById('shopFrame');
const state = document.getElementById('sortState');
const saveButton = document.getElementById('saveSort');
let draggedCard = null;
let observedArea = null;

function setState(text, dirty = false) {
  state.textContent = text;
  state.classList.toggle('dirty', dirty);
}

function renumberProducts(area) {
  const cards = [...area.querySelectorAll('.product-card[data-product-id]')];
  cards.forEach((card, index) => {
    const input = card.querySelector('.sort-position-input');
    if (input) {
      input.value = String(index + 1);
      input.max = String(cards.length);
    }
  });
}

function moveProductToPosition(card, requestedPosition) {
  const area = card.closest('.product-area');
  if (!area) return;
  const cards = [...area.querySelectorAll('.product-card[data-product-id]')];
  const currentIndex = cards.indexOf(card);
  const position = Math.max(1, Math.min(cards.length, Number.parseInt(requestedPosition, 10) || currentIndex + 1));
  const targetIndex = position - 1;

  if (targetIndex !== currentIndex) {
    const cardsWithoutCurrent = cards.filter(item => item !== card);
    const target = cardsWithoutCurrent[targetIndex];
    if (target) area.insertBefore(card, target);
    else area.appendChild(card);
    setState('有未保存的顺序修改', true);
  }
  renumberProducts(area);
}

async function switchCategory(nextCategory) {
  if (!nextCategory || nextCategory === categorySelect.value) return;
  const doc = frame.contentDocument;
  const area = doc.querySelector('.product-area');
  const title = doc.querySelector('.category-title');
  const item = doc.querySelector('.cat-link[data-cat="' + CSS.escape(nextCategory) + '"]');
  if (!area || !item) return;

  categorySelect.value = nextCategory;
  setState('正在加载分类...');
  try {
    const response = await frame.contentWindow.fetch(
      'shop.php?cat=' + encodeURIComponent(nextCategory) + '&ajax=1&sort_edit=1',
      { cache:'no-store' }
    );
    if (!response.ok) throw new Error('分类加载失败');
    const result = await response.json();
    if (!result || typeof result.html !== 'string') throw new Error('分类内容格式错误');
    area.innerHTML = result.html;
    doc.querySelectorAll('.cat-link').forEach(link => link.classList.remove('active'));
    item.classList.add('active');
    if (title) title.textContent = item.textContent.trim();
    decorateProducts();
    setState('拖动商品即可调整');
  } catch (error) {
    setState(error.message || '分类加载失败，请重试', true);
  }
}

function decorateProducts() {
  const doc = frame.contentDocument;
  const announcementButton = doc.getElementById('closeSpeakerPopup');
  if (announcementButton) {
    announcementButton.style.pointerEvents = 'auto';
    announcementButton.removeAttribute('tabindex');
    announcementButton.click();
  }
  const area = doc.querySelector('.product-area');
  if (!area) return;

  doc.querySelectorAll('a, button:not(#closeSpeakerPopup), input').forEach(el => {
    el.style.pointerEvents = 'none';
    el.setAttribute('tabindex', '-1');
  });

  doc.querySelectorAll('.cat-link:not([data-sort-category-bound])').forEach(item => {
    item.dataset.sortCategoryBound = '1';
    item.style.pointerEvents = 'auto';
    item.style.cursor = 'pointer';
    item.setAttribute('tabindex', '0');
    item.addEventListener('click', event => {
      event.preventDefault();
      event.stopImmediatePropagation();
      const nextCategory = item.dataset.cat;
      switchCategory(nextCategory);
    }, true);
  });

  area.querySelectorAll('.product-card[data-product-id]:not([data-sort-bound])').forEach(card => {
    card.dataset.sortBound = '1';
    card.draggable = true;
    card.style.position = 'relative';
    card.style.cursor = 'grab';
    card.style.outline = '2px dashed rgba(245,54,141,.45)';
    card.style.outlineOffset = '3px';

    const control = doc.createElement('label');
    control.className = 'sort-position-control';
    control.style.cssText = 'position:absolute;top:10px;right:10px;z-index:20;display:flex;align-items:center;gap:6px;padding:6px 8px;border:1px solid #f2bfd5;border-radius:10px;background:rgba(255,255,255,.96);box-shadow:0 4px 14px rgba(128,58,91,.14);color:#6f5060;font-size:12px;font-weight:800;cursor:default;';
    control.textContent = '排序';

    const input = doc.createElement('input');
    input.className = 'sort-position-input';
    input.type = 'number';
    input.min = '1';
    input.inputMode = 'numeric';
    input.style.cssText = 'width:54px;height:30px;padding:0 5px;border:1px solid #e8aac5;border-radius:7px;background:#fff;color:#4f3c46;text-align:center;font-weight:900;pointer-events:auto;';
    input.addEventListener('pointerdown', event => event.stopPropagation());
    input.addEventListener('click', event => event.stopPropagation());
    input.addEventListener('keydown', event => {
      event.stopPropagation();
      if (event.key === 'Enter') {
        event.preventDefault();
        moveProductToPosition(card, input.value);
        input.blur();
      }
    });
    input.addEventListener('change', () => moveProductToPosition(card, input.value));
    control.addEventListener('dragstart', event => event.preventDefault());
    control.appendChild(input);
    card.appendChild(control);

    card.addEventListener('dragstart', event => {
      if (event.target.closest('.sort-position-control')) {
        event.preventDefault();
        return;
      }
      draggedCard = card;
      card.style.opacity = '.45';
      event.dataTransfer.effectAllowed = 'move';
    });
    card.addEventListener('dragend', () => {
      card.style.opacity = '1';
      draggedCard = null;
      renumberProducts(area);
      setState('有未保存的顺序修改', true);
    });
  });
  renumberProducts(area);

  if (area !== observedArea) {
    observedArea = area;
    area.addEventListener('dragover', event => {
      event.preventDefault();
      if (!draggedCard) return;
      const cards = [...area.querySelectorAll('.product-card[data-product-id]')]
        .filter(card => card !== draggedCard);
      const next = cards.find(card => {
        const rect = card.getBoundingClientRect();
        return event.clientY < rect.top + rect.height / 2;
      });
      if (next) area.insertBefore(draggedCard, next);
      else area.appendChild(draggedCard);
    });
  }
}

frame.addEventListener('load', () => {
  const doc = frame.contentDocument;
  const observer = new MutationObserver(decorateProducts);
  observer.observe(doc.documentElement, { childList:true, subtree:true });
  decorateProducts();
  setState('拖动商品即可调整');
});

categorySelect.addEventListener('change', () => {
  const nextCategory = categorySelect.value;
  categorySelect.value = frame.contentDocument.querySelector('.cat-link.active')?.dataset.cat || '';
  switchCategory(nextCategory);
});

saveButton.addEventListener('click', async () => {
  const ids = [...frame.contentDocument.querySelectorAll('.product-area .product-card[data-product-id]')]
    .map(card => Number(card.dataset.productId))
    .filter(Boolean);
  const data = new FormData();
  data.append('csrf_token', document.querySelector('#csrfForm [name="csrf_token"]').value);
  data.append('category', categorySelect.value);
  data.append('product_ids', JSON.stringify(ids));
  saveButton.disabled = true;
  setState('正在保存...');
  try {
    const response = await fetch('api_product_sort.php', { method:'POST', body:data });
    const result = await response.json();
    if (!result.success) throw new Error(result.message || '保存失败');
    setState(result.message || '商品顺序已保存');
  } catch (error) {
    setState(error.message || '保存失败，请稍后重试', true);
  } finally {
    saveButton.disabled = false;
  }
});
</script>
</body>
</html>
