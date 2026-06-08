<?php
session_start();
require __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

function qii_text($text) {
    $text = (string)$text;
    if ($text === '') return '';

    if (preg_match('/[µÞÕÚÐþ╔═║╣╝╗▓░┤┐└┴┬├┼]/u', $text)) {
        $fixed = @iconv('UTF-8', 'CP850//IGNORE', $text);
        if (is_string($fixed) && $fixed !== '' && preg_match('/[\x{4E00}-\x{9FFF}]/u', $fixed)) {
            return $fixed;
        }
    }

    return $text;
}

require_once __DIR__ . '/../../app/bootstrap.php';

$order_number = $_GET['order_number'] ?? '';
if (!$order_number) die("❌ 订单号缺失");

// 优先使用 session 的 pending_order
if (isset($_SESSION['pending_order']) && $_SESSION['pending_order']['order_number'] === $order_number) {
    $order_data = $_SESSION['pending_order'];
    $data_source = "session";
} else {
    // fallback：从数据库读（订单已确认或用户刷新后）
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number=? LIMIT 1");
    $stmt->execute([$order_number]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) die("❌ 找不到订单");
    if (!empty($order['receipt_token'])) {
        $requestToken = (string)($_GET['token'] ?? '');
        if (!hash_equals((string)$order['receipt_token'], $requestToken)) {
            http_response_code(403);
            die("❌ 收据链接无效");
        }
    }

    $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
    $stmt_items->execute([$order['id']]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $order_data = [
        "order_number"   => $order['order_number'],
        "created_at"   => $order['created_at'],
        "total"        => $order['total'],
        "shipping"     => $order['shipping'],
        "grand_total"  => $order['grand_total'],
        "region"       => $order['region'],
        "addr_name"    => $order['addr_name'],
        "addr_phone"   => $order['addr_phone'],
        "addr_address" => $order['addr_address'],
        "addr_postcode"=> $order['addr_postcode'],
        "addr_state"   => $order['addr_state'],
        "order_note"   => $order['order_note'] ?? '',
        "items"        => []
    ];

    foreach ($items as $it) {
        $order_data['items'][] = [
            "sku"   => $it['sku'],
            "name"  => qii_text($it['product_name']),
            "qty"   => $it['quantity'],
            "price" => $it['price']
        ];
    }

    $data_source = "database";
}

// 格式化时间
$timeFormatted = date("Y年n月j日 H:i", strtotime($order_data['created_at']));

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<?php require_once __DIR__ . '/../includes/seo.php'; ?>
<?php qii_seo_meta([
  'title' => 'Order Receipt | qii.shoppp',
  'description' => 'View your qii.shoppp order receipt and order summary.',
  'path' => '/receipt.php',
  'robots' => 'noindex, nofollow'
]); ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Ma+Shan+Zheng&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ------------------- */
/*  Qii Receipt Style  */
/* ------------------- */
:root {
  --qii-pink: #FFE9F0;
  --qii-pink-deep: #E5679C;
  --qii-pink-soft: #FFF6FA;
  --qii-text: #4a3b43;
}
body {
  margin: 0;
  font-family: "Patrick Hand", Arial, sans-serif;
  background: linear-gradient(180deg, var(--qii-pink-soft), var(--qii-pink));
  color: var(--qii-text);
  animation: fadeIn .6s ease;
  overflow-x: hidden;
}
@keyframes fadeIn { from{opacity:0;} to{opacity:1;} }

/* 背景爱心 */
.bg-hearts span {
  position: fixed;
  font-size: 22px;
  color: var(--qii-pink-deep);
  opacity: .15;
  animation: float-hearts 10s linear infinite;
}
@keyframes float-hearts {
  0% { top:100%; }
  100% { top:-10%; }
}

/* Header */
.header {
  text-align: center;
  padding: 20px 10px;
  position: relative;
}
.header img {
  width: 90px;
  height: 90px;
  object-fit: cover;
  border-radius: 50%;
  border: 3px solid #fff;
  box-shadow: 0 4px 10px rgba(229,103,156,0.2);
  margin-bottom: 10px;
}
.header h2 {
  font-family: "Ma Shan Zheng", cursive;
  color: var(--qii-pink-deep);
  font-size: 24px;
}
.back-btn {
  position: absolute;
  top: 15px; left: 15px;
  padding: 8px 14px;
  border-radius: 999px;
  border: 2px solid var(--qii-pink-deep);
  background: rgba(255,255,255,0.7);
  color: var(--qii-pink-deep);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.back-btn:hover { background: var(--qii-pink-deep); color:white; }

/* Container */
.container {
  background: rgba(255,255,255,0.8);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(229,103,156,0.2);
  border-radius: 20px;
  width: min(720px, 90%);
  padding: 25px;
  box-shadow: 0 8px 24px rgba(229,103,156,0.15);
  margin: 40px auto;
}

/* Table */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
  border-radius: 12px;
}
th {
  background: #ffd9e9;
  color: var(--qii-pink-deep);
  padding: 10px;
}
td {
  text-align: center;
  padding: 10px;
  border-top: 1px dashed #f5c1d6;
}

/* Totals */
.total {
  text-align: right;
  font-weight: bold;
  color: #d2558c;
  margin: 4px 0;
}

.shipping-box {
  margin-top: 15px;
  padding: 10px;
  border: 2px dashed #f2a8c6;
  border-radius: 15px;
  background: #fff6fa;
}

/* Buttons */
.btn-row {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 10px;
}
.btn {
  border: none;
  border-radius: 999px;
  padding: 10px 18px;
  font-weight: bold;
  cursor: pointer;
}
.btn-pink {
  background: linear-gradient(180deg, #fbd3e2, #f6bcd9);
  color: white;
}

/* Modal */
#posterModal {
  display: none;
  position: fixed; inset:0;
  background: rgba(0,0,0,0.4);
  justify-content: center; align-items: center;
}
#posterModal .modal-content {
  background:white;
  border-radius:20px;
  padding:20px;
  width:min(90%,500px);
  text-align:center;
}

/* First Screen */
#qii-thankyou {
  position: fixed; inset:0;
  background: linear-gradient(180deg,#FFF6FA,#FFE9F0);
  display:flex; flex-direction:column;
  justify-content:center; align-items:center;
  z-index:3000;
  transition: opacity .8s ease;
}
#qii-thankyou.fade-out { opacity:0; pointer-events:none; }

.qii-img {
  width:200px; height:200px;
  border-radius:50%; object-fit:cover;
  animation: float 3s infinite ease-in-out;
}
@keyframes float {
  0%,100%{transform:translateY(0);}
  50%{transform:translateY(-8px);}
}

.qii-close-btn {
    margin-top: 15px;
    padding: 10px 24px;
    border: none;
    border-radius: 30px;
    background: linear-gradient(180deg, #fbc7d4, #f49ac1);
    color: white;
    font-weight: bold;
    font-size: 16px;
    box-shadow: 0 4px 10px rgba(244,154,193,0.4);
    cursor: pointer;
    transition: 0.25s ease;
}

.qii-close-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(244,154,193,0.55);
}

/* ============================
      📱 手机端（像图一一样整齐）
============================ */
@media(max-width: 768px) {

  body {
    margin:0;
    padding:0;
    overflow-x:hidden;
  }

  /* 整个页面整体更紧凑 */
  #receipt-content {
    padding: 10px 0 !important;
  }

  /* Header */
  .header {
    padding: 15px 0;
  }
  .header img {
    width: 70px;
    height: 70px;
  }
  .header h2 {
    font-size: 20px;
    margin-top: 10px;
  }

  /* 返回按钮 */
  .back-btn {
    top: 10px;
    left: 10px;
    padding: 5px 10px;
    font-size: 13px;
  }

  /* 白色主容器（像图一的 card） */
  .container {
    width: 92% !important;
    padding: 15px !important;
    border-radius: 15px !important;
    margin-top: 15px !important;
    margin-bottom: 15px !important;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
  }

  /* 订单号 / 下单时间 */
  .container p {
    font-size: 15px !important;
    margin: 6px 0 !important;
  }

  /* 表格外框支持横向滚动，但不溢出 */
  table {
    width: 100%;
    display: table;
    overflow-x: auto;
    border-radius: 10px;
  }

  th {
    font-size: 14px !important;
    padding: 8px !important;
  }
  td {
    font-size: 14px !important;
    padding: 8px !important;
  }

  /* 总价文字 */
  .total {
    font-size: 15px !important;
    padding-right: 5px;
    margin: 3px 0 !important;
  }

  /* 运费说明卡片 */
  .shipping-box {
    font-size: 14px !important;
    padding: 10px !important;
    border-radius: 12px !important;
    margin-top: 12px !important;
  }

  /* 按钮一列显示 */
  .btn-row {
    flex-direction: column !important;
    width: 92%;
    margin: 0 auto;
    padding-bottom: 20px;
  }

  .btn {
    width: 100%;
    font-size: 15px !important;
    padding: 10px !important;
    margin-top: 5px;
  }

  /* Modal 适配手机 */
  #couponModal .modal-content,
  #addressModal > div,
  #payModal > div {
    width: 92% !important;
    padding: 18px !important;
  }

  input {
    font-size: 15px !important;
    padding: 8px !important;
  }

  #payModal img {
    width: 220px !important;
  }
}
</style>
</head>

<!-- 🎀 Qii Thank You Screen -->
<div id="qii-thankyou" style="display:none;">
  <img src="images/29.png" class="qii-img">
  <h2 style="color:#E5679C;">谢谢你光顾 <span style="color:#D65286;">Qii.shoppp</span> 🎀</h2>
  <p style="color:#A0587E;">小东西也能带来大心情 💕<br>谢谢你喜欢 Qii 的小可爱。</p>
  <button class="qii-close-btn" onclick="closeQii()">关闭</button>
</div>

<!-- 🌸 Receipt -->
<div id="receipt-content" style="display:none;">
  <div class="bg-hearts">
    <span>❤</span><span>♡</span><span>💗</span><span>💖</span><span>💕</span>
  </div>

  <div class="header">
    <a href="shop.php" class="back-btn"><i class="fas fa-arrow-left"></i> 返回商店</a>
    <img src="images/28.png">
    <h2>Qii 的甜心收据 💕</h2>
  </div>

  <div class="container" id="receipt">
    <p><b>订单号：</b> <?= htmlspecialchars($order_data['order_number']) ?></p>
    <p><b>下单时间：</b> <?= $timeFormatted ?></p>

    <?php if (!empty($order_data['addr_name'])): ?>
    <div class="shipping-box" style="margin-top:20px;">
      <h3 style="color:#E5679C;">收货信息 📦</h3>
      <p>👤 <?= htmlspecialchars($order_data['addr_name']) ?></p>
      <p>📱 <?= htmlspecialchars($order_data['addr_phone']) ?></p>
      <p>🏡 <?= htmlspecialchars($order_data['addr_address']) ?>, <?= htmlspecialchars($order_data['addr_postcode']) ?> <?= htmlspecialchars($order_data['addr_state']) ?></p>
    </div>
    <?php endif; ?>

    <table>
      <tr><th>数量</th><th>商品</th><th>单价</th><th>小计</th></tr>

      <?php
      $total = 0;

      foreach ($order_data['items'] as $item) {
        $subtotal = $item['qty'] * $item['price'];
        $total += $subtotal;

        echo "<tr>
              <td>{$item['qty']}</td>
              <td>".htmlspecialchars(qii_text($item['product_name'] ?? $item['name'] ?? ''));
        if (!empty($item['variant_name'])) {
          echo " <span style='color:#C86A9B;'>（" . htmlspecialchars(qii_text($item['variant_name'])) . "）</span>";
        }
        echo "</td>
              <td>RM ".number_format($item['price'],2)."</td>
              <td>RM ".number_format($subtotal,2)."</td>
            </tr>";
      }

      // 运费规则
      $region = $order_data['region'] ?? 'west';
      if ($region === 'west')
          $shipping = $total >= 65 ? 0 : 10;
      else
          $shipping = $total >= 80 ? 0 : 15;

      // Apply coupon code from session; discount is calculated server-side.
      $couponCode = $_SESSION['coupon_code'][$order_number] ?? ($_SESSION['coupon_code_pending'] ?? '');
      $discount = 0;
      if ($couponCode !== '' && function_exists('qii_calculate_coupon')) {
        $couponResult = qii_calculate_coupon($pdo, (string)$couponCode, (float)$total);
        if ($couponResult['valid']) {
          $discount = (float)$couponResult['discount'];
        }
      }
      $grand_total = max(0, ($total + $shipping - $discount));

      echo "<tr><td colspan='3' style='text-align:right;'>运费</td><td>RM ".number_format($shipping,2)."</td></tr>";
      if ($discount > 0) {
        echo "<tr><td colspan='3' style='text-align:right;'>优惠</td><td>-RM ".number_format($discount,2)."</td></tr>";
      }
      ?>
    </table>

    <p class="total">商品总额：RM <?= number_format($total,2) ?></p>
    <p class="total">运费：RM <?= number_format($shipping,2) ?></p>
    <?php if (($discount ?? 0) > 0): ?>
    <p class="total">优惠：-RM <?= number_format($discount,2) ?></p>
    <?php endif; ?>
    <p class="total">总价：RM <?= number_format($grand_total,2) ?></p>

    <div class="shipping-box">
      <?= ($region === 'west')
          ? "📦 西马：RM10 满 RM65 免邮"
          : "📦 东马：RM15 满 RM80 免邮" ?>
</div>

<!-- Coupon Modal -->
<div id="couponModal" style=
    "display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5);
    justify-content:center; align-items:center; z-index:4000;">
    
    <div style=
        "background:white; padding:25px; border-radius:18px; width:300px;
        text-align:center; box-shadow:0 8px 20px rgba(0,0,0,0.2);">

        <h3 style="color:#E5679C;">输入优惠码 🎀</h3>

        <input id="couponInput" type="text"
            placeholder="如：QII5"
            style="width:90%; padding:10px; border:1px solid #f4b8cd; border-radius:8px;">

        <p id="couponMsg" style="color:#D65286; font-size:14px; min-height:20px;"></p>

        <button class="btn btn-pink" onclick="applyCoupon()">确认使用</button>
        <button class="btn btn-pink" onclick="closeCoupon()">关闭</button>
    </div>
</div>

</div>
  </div>

  <div class="btn-row">
      <button class="btn btn-pink" onclick="openAddress()">确认订单</button>
      <button class="btn btn-pink" onclick="openCoupon()">优惠码</button>
  </div>

</div>

<!-- JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
function qiiCsrfHeaders(extra = {}) {
  const token = document.querySelector('meta[name="qii-csrf-token"]')?.content || "";
  return token ? { ...extra, "X-QII-CSRF-Token": token } : extra;
}

window.addEventListener("load", () => {
  document.getElementById("qii-thankyou").style.display = "flex";
});

// 关闭动画
function closeQii(){
  let qii = document.getElementById("qii-thankyou");
  // 先显示收据内容，再淡出感谢层，避免中间空白
  document.getElementById("receipt-content").style.display = "block";
  qii.classList.add("fade-out");
  setTimeout(()=> {
    qii.style.display = "none";
  }, 800);
}

// Coupon modal controls
function openCoupon(){
    document.getElementById("couponModal").style.display = "flex";
}
function closeCoupon(){
    document.getElementById("couponModal").style.display = "none";
}

// Address modal controls
function openAddress(){
    document.getElementById("addressModal").style.display = "flex";
}
function closeAddress(){
    document.getElementById("addressModal").style.display = "none";
}

function submitAddress(){
    let name = document.getElementById("addr_name").value.trim();
    let phone = document.getElementById("addr_phone").value.trim();
    let address = document.getElementById("addr_address").value.trim();
    let postcode = document.getElementById("addr_postcode").value.trim();
    let state = document.getElementById("addr_state").value.trim();
    let orderNote = document.getElementById("order_note").value.trim();

 if (!name || !phone) {
    alert("请填写收件人姓名和联系电话");
    return;
}

    let fd = new URLSearchParams();
    fd.append("order_number", "<?= $order_data['order_number'] ?>");

    fd.append("name", name);
    fd.append("phone", phone);
    fd.append("address", address);
    fd.append("postcode", postcode);
    fd.append("state", state);
    fd.append("order_note", orderNote);

    fetch("api/submit_order.php", {
        method:"POST",
        headers: qiiCsrfHeaders({ "Content-Type":"application/x-www-form-urlencoded" }),
        body: fd.toString()
    })
    .then(r => r.text())
    .then(res => {
        console.log("SUBMIT:", res);
        const trimmed = res.trim();
        if (trimmed !== "OK" && trimmed !== "EXISTS") {
            alert("❌ 订单提交失败：" + res);
            return;
        }
        closeAddress();
        openPay();
    });
}

// Apply coupon via AJAX
function applyCoupon(){
    const code = document.getElementById("couponInput").value.trim();
    if(!code){
        document.getElementById("couponMsg").innerText = "请输入优惠码";
        return;
    }

    fetch("api/validate_coupon.php", {
        method: "POST",
        headers: qiiCsrfHeaders({ "Content-Type": "application/x-www-form-urlencoded" }),
        body: "code=" + encodeURIComponent(code) + "&total=<?= $total ?>"
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("couponMsg").innerText = data.msg;

        if (data.success) {
            // Save coupon code to session; final discount and usage are handled on order submit.
            fetch("api/save_coupon.php", {
                method: "POST",
                headers: qiiCsrfHeaders({ "Content-Type": "application/x-www-form-urlencoded" }),
                body: "code=" + encodeURIComponent(code)
            })
            .then(() => {
                location.reload();
            });
        }
    })
    .catch(() => {
        document.getElementById("couponMsg").innerText = "网络错误，请稍后再试";
    });
}

// Payment modal controls
function openPay(){
    document.getElementById("payModal").style.display = "flex";
}
function closePay(){
    document.getElementById("payModal").style.display = "none";
}
</script>

<!-- Address Modal -->
<div id="addressModal"
     style="display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.5);
            justify-content:center; align-items:center;
            z-index:5000;">
  
  <div style="background:white; padding:25px; border-radius:18px;
              width:320px; text-align:center;
              box-shadow:0 8px 20px rgba(0,0,0,0.25);">

      <h3 style="color:#E5679C;">填写收货地址 🩷</h3>

      <input id="addr_name" type="text" placeholder="收件人姓名"
             style="width:90%; padding:10px; margin-bottom:10px; border-radius:10px; border:1px solid #f4b8cd;">

      <input id="addr_phone" type="text" placeholder="联系电话"
             style="width:90%; padding:10px; margin-bottom:10px; border-radius:10px; border:1px solid #f4b8cd;">

      <input id="addr_address" type="text" placeholder="详细地址（选填）"
             style="width:90%; padding:10px; margin-bottom:10px; border-radius:10px; border:1px solid #f4b8cd;">

      <input id="addr_postcode" type="text" placeholder="邮编（选填）"
             style="width:90%; padding:10px; margin-bottom:10px; border-radius:10px; border:1px solid #f4b8cd;">

      <input id="addr_state" type="text" placeholder="州属（选填）"
             style="width:90%; padding:10px; margin-bottom:10px; border-radius:10px; border:1px solid #f4b8cd;">

      <textarea id="order_note" maxlength="500" rows="3" placeholder="订单备注（选填）"
                style="width:90%; padding:10px; margin-bottom:10px; border-radius:10px; border:1px solid #f4b8cd; resize:vertical; font:inherit;"></textarea>

      <button class="btn btn-pink" onclick="submitAddress()">确认付款</button>
      <button class="btn btn-pink" onclick="closeAddress()">取消</button>
  </div>

</div>

<!-- Payment Modal -->
<div id="payModal"
     style="display:none; position:fixed; inset:0; 
            background:rgba(0,0,0,0.5); 
            justify-content:center; align-items:center; 
            z-index:5000;">

    <div style="background:white; padding:25px; border-radius:18px; 
                width:320px; text-align:center; 
                box-shadow:0 8px 20px rgba(0,0,0,0.25);">



        <h3 style="color:#E5679C;">请扫描付款二维码</h3>

<p style="font-size:14px; color:#A0587E; margin-top:-5px; line-height:1.7;">
    付款后请将付款记录发送给店主确认 💕<br>
    Instagram:
    <a href="https://www.instagram.com/qii.shoppp?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw=="
       target="_blank"
       style="color:#E5679C; font-weight:bold; text-decoration:none;">
       @qii.shoppp
    </a>
</p>

<a href="images/image.png" target="_blank" rel="noopener">
    <img src="images/image.png"
         alt="付款二维码"
         style="width:260px; border-radius:10px; margin:10px auto; display:block;">
</a>

        <button class="btn btn-pink"
                onclick="window.location.href='shop.php'">
            返回商店
        </button>
    </div>

</div>

</body>
</html>
