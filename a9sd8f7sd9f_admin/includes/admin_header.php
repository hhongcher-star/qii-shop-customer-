<?php
  // 自动识别当前页面文件名
  $current_page = basename($_SERVER['PHP_SELF']);
?>
<header>
  <div class="admin-sidebar">
    <div class="logo-area">
      <img src="/images/logo.png" alt="Qii.shop Logo" class="logo">
      <h2>Qii.shop</h2>
    </div>

    <nav class="menu">
      <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i> 仪表盘 Dashboard
      </a>
      <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-box"></i> 商品管理
      </a>
      <a href="inventory.php" class="<?= $current_page == 'inventory.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-warehouse"></i> 库存管理
      </a>
      <a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-receipt"></i> 订单管理
      </a>
      <a href="discount.php" class="<?= $current_page == 'discount.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-tags"></i> 优惠管理
      </a>
    </nav>

    <div class="logout">
      <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> 退出登录</a>
    </div>
  </div>
</header>

<style>
/* ============ 🧭 Sidebar Layout ============ */
body {
  margin: 0;
  font-family: "Inter", "Noto Sans SC", sans-serif;
  background: #f8f9fb;
}

/* 侧边栏整体 */
.admin-sidebar {
  position: fixed;
  left: 0;
  top: 0;
  width: 230px;
  height: 100vh;
  background: #1e1f26;
  color: #fff;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: 2px 0 6px rgba(0,0,0,0.2);
}

/* logo 区域 */
.logo-area {
  text-align: center;
  padding: 25px 10px 10px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}
.logo-area .logo {
  width: 55px;
  height: 55px;
  border-radius: 50%;
  object-fit: cover;
}
.logo-area h2 {
  margin: 10px 0 0;
  font-size: 18px;
  color: #fff;
  letter-spacing: 0.5px;
}

/* 菜单 */
.menu {
  display: flex;
  flex-direction: column;
  padding: 20px 0;
}
.menu a {
  color: #ddd;
  text-decoration: none;
  padding: 12px 25px;
  display: flex;
  align-items: center;
  transition: all 0.25s ease;
  position: relative;
}
.menu a i {
  margin-right: 10px;
  font-size: 16px;
}
.menu a:hover {
  background: #2b2d35;
  color: #fff;
}
.menu a.active {
  background: linear-gradient(90deg, #2b2d35 0%, #3a3c46 100%);
  color: #fff;
  border-left: 4px solid #3a82ff;
  padding-left: 21px; /* 为左侧蓝条留空间 */
}

/* 退出按钮 */
.logout {
  padding: 15px 25px;
  border-top: 1px solid rgba(255,255,255,0.1);
}
.logout a {
  color: #ccc;
  text-decoration: none;
  display: flex;
  align-items: center;
  transition: 0.2s;
}
.logout a:hover {
  color: #fff;
}

/* 主内容区右侧留白 */
.main-content {
  margin-left: 230px;
  padding: 30px;
}

/* 响应式 */
@media (max-width: 768px) {
  .admin-sidebar {
    position: relative;
    width: 100%;
    height: auto;
    flex-direction: row;
    overflow-x: auto;
  }
  .logo-area {
    display: none;
  }
  .menu {
    flex-direction: row;
    padding: 0;
  }
  .menu a {
    padding: 10px 15px;
    font-size: 13px;
    white-space: nowrap;
  }
  .logout {
    display: none;
  }
  .main-content {
    margin-left: 0;
    padding: 20px;
  }
}
</style>

<!-- Font Awesome CDN -->
<script src="https://kit.fontawesome.com/a2d9e1a36b.js" crossorigin="anonymous"></script>
