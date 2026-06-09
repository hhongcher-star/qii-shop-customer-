<?php
$current_page = basename($_SERVER['PHP_SELF']);

$menu_items = [
    ['file' => 'dashboard.php', 'icon' => 'fa-chart-line', 'label' => '仪表盘 Dashboard'],
    ['file' => 'product.php', 'icon' => 'fa-box-open', 'label' => '商品管理'],
    ['file' => 'inventory.php', 'icon' => 'fa-boxes-stacked', 'label' => '库存管理'],
    ['file' => 'order.php', 'icon' => 'fa-bag-shopping', 'label' => '订单管理'],
    ['file' => 'customers.php', 'icon' => 'fa-users', 'label' => '用户管理'],
    ['file' => 'discount_center.php', 'icon' => 'fa-ticket', 'label' => '优惠管理'],
    ['file' => 'reply.php', 'icon' => 'fa-comments', 'label' => '用户消息'],
];

$mobile_items = [
    ['file' => 'dashboard.php', 'icon' => 'fa-house', 'label' => '首页'],
    ['file' => 'order.php', 'icon' => 'fa-bag-shopping', 'label' => '订单'],
    ['file' => 'product_editor.php', 'icon' => 'fa-plus', 'label' => '发布商品', 'class' => 'publish'],
    ['file' => 'product.php', 'icon' => 'fa-box-open', 'label' => '商品'],
    ['file' => '#', 'icon' => 'fa-table-cells-large', 'label' => '更多', 'class' => 'more'],
];

$more_pages = ['discount_center.php', 'reply.php', 'inventory.php', 'hero_content.php', 'customers.php'];
?>
<link rel="stylesheet" href="includes/admin_layout.css?v=20260604c">

<header class="admin-shell">
  <aside class="admin-sidebar">
    <div class="brand-panel">
      <img src="../images/logo.png" alt="Qii.shop Logo" class="brand-logo">
      <div>
        <p class="brand-kicker">Qii Admin</p>
        <h2>Qii.shop</h2>
      </div>
    </div>

    <nav class="menu" aria-label="后台导航">
      <?php foreach ($menu_items as $item): ?>
        <a href="<?= htmlspecialchars($item['file']) ?>" class="<?= $current_page === $item['file'] ? 'active' : '' ?>">
          <span class="menu-icon"><i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i></span>
          <span><?= htmlspecialchars($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
      <a href="hero_content.php" class="<?= $current_page === 'hero_content.php' ? 'active' : '' ?>">
        <span class="menu-icon"><i class="fa-solid fa-pen-to-square"></i></span>
        <span>前台内容</span>
      </a>
    </nav>

    <div class="logout">
      <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>退出登录</span></a>
    </div>
  </aside>
</header>

<nav class="mobile-bottom-nav" aria-label="手机底部导航">
  <?php foreach ($mobile_items as $item): ?>
    <?php
      $classes = [];
      if (($item['class'] ?? '') === 'publish') {
          $classes[] = 'publish';
      }
      if (($item['class'] ?? '') === 'more') {
          $classes[] = 'mobile-more-trigger';
      }
      if ($current_page === $item['file'] || (($item['class'] ?? '') === 'more' && in_array($current_page, $more_pages, true))) {
          $classes[] = 'active';
      }
    ?>
    <a href="<?= htmlspecialchars($item['file']) ?>" class="<?= htmlspecialchars(implode(' ', $classes)) ?>" <?= (($item['class'] ?? '') === 'more') ? 'data-mobile-more aria-expanded="false"' : '' ?>>
      <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
      <span><?= htmlspecialchars($item['label']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<div class="mobile-more-menu" data-mobile-more-menu>
  <a href="hero_content.php"><i class="fa-solid fa-pen-to-square"></i><span>前台内容</span></a>
  <a href="discount_center.php"><i class="fa-solid fa-ticket"></i><span>优惠管理</span></a>
  <a href="customers.php"><i class="fa-solid fa-users"></i><span>用户管理</span></a>
  <a href="reply.php"><i class="fa-solid fa-comments"></i><span>用户消息</span></a>
  <a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i><span>库存管理</span></a>
  <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>退出登录</span></a>
</div>

<script src="https://kit.fontawesome.com/a2d9e1a36b.js" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var trigger = document.querySelector('[data-mobile-more]');
  var menu = document.querySelector('[data-mobile-more-menu]');
  if (!trigger || !menu) return;

  trigger.addEventListener('click', function (event) {
    event.preventDefault();
    var open = menu.classList.toggle('open');
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  document.addEventListener('click', function (event) {
    if (!menu.contains(event.target) && !trigger.contains(event.target)) {
      menu.classList.remove('open');
      trigger.setAttribute('aria-expanded', 'false');
    }
  });
});
</script>
