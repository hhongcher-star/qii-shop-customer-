<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>Qii.Shop 管理后台</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body {
      font-family: 'Inter','Noto Sans SC', sans-serif;
      margin: 0;
      background: #f7f8fb;
      color: #2c3e50;
    }
    .main{
      margin-left:230px;
      padding:40px;
    }
    h1{
      margin:0 0 10px;
    }

    /* ⚠️ 库存提醒 */
    .warning-box{
      background:#fff7d6;
      padding:20px;
      border-radius:12px;
      margin:20px 0;
      border-left:5px solid #ffb100;
    }

    /* 数据概览卡片 */
    .stats{
      display:flex;
      gap:20px;
      margin-top:25px;
      flex-wrap:wrap;
    }
    .stat-card{
      flex:1;
      min-width:230px;
      background:white;
      border-radius:10px;
      padding:25px;
      text-align:center;
      box-shadow:0 2px 6px rgba(0,0,0,0.08);
      transition:.2s;
    }
    .stat-card:hover{ transform:translateY(-3px); }

    .stat-card h3{
      font-size:16px; color:#777; margin-bottom:8px;
    }
    .stat-card p{
      font-size:26px; font-weight:600;
    }

    /* 图表卡片 */
    .chart-container{
      background:#fff;
      border-radius:10px;
      padding:30px;
      margin-top:35px;
      box-shadow:0 2px 6px rgba(0,0,0,0.08);
    }
  </style>

</head>
<body>

<?php include 'includes/admin_header.php'; ?>

<div class="main">

  <h1>欢迎回来，admin 🎉</h1>
  <p>这是 Yummy Diary 的后台首页。</p>


  <!-- ⚠️ 库存预警 -->
  <?php
    $lowStock = $pdo->query("
      SELECT name, stock, warning_level
      FROM products
      WHERE stock <= warning_level
      ORDER BY stock
    ")->fetchAll(PDO::FETCH_ASSOC);
  ?>

  <?php if($lowStock): ?>
  <div class="warning-box">
    <h3>⚠️ 库存不足提醒</h3>
    <ul>
      <?php foreach ($lowStock as $item): ?>
        <li><?= $item['name'] ?>（库存 <?= $item['stock'] ?>/预警 <?= $item['warning_level'] ?>）</li>
      <?php endforeach; ?>
    </ul>
    <a href="products.php" style="color:#d35400; font-weight:600;">👉 查看全部库存不足</a>
  </div>
  <?php endif; ?>


  <!-- 📊 数据概览 -->
  <div class="stats">

    <div class="stat-card">
      <h3>今日订单</h3>
      <p><?= $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn() ?></p>
    </div>

    <div class="stat-card">
      <h3>本月销售额</h3>
      <?php $monthSales = $pdo->query("SELECT SUM(total) FROM orders WHERE MONTH(created_at)=MONTH(CURDATE())")->fetchColumn(); ?>
      <p>RM <?= number_format($monthSales ?: 0, 2) ?></p>
    </div>

  </div>


  <!-- 📈 图表 -->
  <?php
    $stmt = $pdo->query("
      SELECT DATE(created_at) AS date, SUM(total) AS total
      FROM orders
      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
      GROUP BY DATE(created_at)
      ORDER BY DATE(created_at)
    ");
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dates = array_column($salesData, 'date');
    $totals = array_map('floatval', array_column($salesData, 'total'));
  ?>

  <div class="chart-container">
    <h2>📈 最近30天销售额趋势</h2>
    <canvas id="salesChart"></canvas>
  </div>

</div>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx,{
  type:'line',
  data:{
    labels:<?=json_encode($dates)?>,
    datasets:[{
      label:'销售额 (RM)',
      data:<?=json_encode($totals)?>,
      borderColor:'#ff7675',
      backgroundColor:'rgba(255,118,117,0.25)',
      fill:true,
      tension:0.3
    }]
  },
  options:{
    plugins:{ legend:{ display:false }},
    scales:{ y:{ beginAtZero:true }}
  }
});
</script>

</body>
</html>
