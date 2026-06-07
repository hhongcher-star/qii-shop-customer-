<?php
require_once __DIR__ . '/../../app/bootstrap.php';
qii_start_session();
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
require_once __DIR__ . '/../../app/content_settings.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

$contactTitle = qii_sanitize_rich_text(qii_content($pdo, 'contact_title', '联系我们 📬'));
$contactDescription = qii_sanitize_rich_text(qii_content($pdo, 'contact_description', "如果你对我们的商品有任何疑问，<br>或者只是想聊聊生活里的小确幸，<br>欢迎随时来和我们说说话 🌷<br><br>有时候，一句“嗨～”也能让一天变得更可爱。"));
$contactButton = qii_sanitize_rich_text(qii_content($pdo, 'contact_button', '发送'));
$contactSocialText = qii_sanitize_rich_text(qii_content($pdo, 'contact_social_text', '或在社交平台找到我们 🌸'));
$contactImage = qii_content($pdo, 'contact_image', 'images/qii-mail.png');

/* 💌 表单提交逻辑 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  qii_verify_frontend_csrf();
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $message = trim($_POST['message']);

  if ($name && $email && $message) {
    $stmt = $pdo->prepare("INSERT INTO messages (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$name, $email, $message]);
    $success = "谢谢你，留言已送达给 Qii 💌";
  } else {
    $error = "请填写完整信息哦 🌸";
  }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">  <?php require_once __DIR__ . '/../includes/seo.php'; ?>
  <?php qii_seo_meta([
    'title' => 'Contact qii.shoppp | Customer Support & Orders',
    'description' => 'Contact qii.shoppp for order questions, product enquiries, shipping details and customer support in Malaysia.',
    'path' => '/contact.php',
    'keywords' => 'contact qii shop, qii.shoppp support, cute shop Malaysia contact, order enquiry'
  ]); ?><link rel="stylesheet" href="css/style.css">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <style>
    /* ✅（新增）加载层基础样式与淡出动画 */
    #loader{
      position: fixed;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: linear-gradient(180deg,#FFF6FA 0%, #FFE9F0 100%);
      z-index: 2000;
      opacity: 1;
      transition: opacity .8s ease;
    }
    #loader.fade-out{ opacity: 0; }
    #loader img{
      width: 160px;
      height: auto;
      animation: float 2s ease-in-out infinite;
    }
    #loader p{
      margin-top: 18px;
      font-family: "Patrick Hand", cursive;
      color: #E5679C;
      font-size: 1.2rem;
      letter-spacing: .5px;
      animation: fadeInText 2s ease-in-out infinite alternate;
    }
    @keyframes float{
      0%,100%{ transform: translateY(0); }
      50%{ transform: translateY(-8px); }
    }
    @keyframes fadeInText{
      0%{ opacity:.7; transform: scale(.98); }
      100%{ opacity:1; transform: scale(1.02); }
    }

    /* 💌 飘落信封动画 */
    .floating-letters {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      pointer-events: none;
      z-index: 0;
    }

    .letter {
      position: absolute;
      top: -100px;
      width: 60px;
      opacity: 0;
      animation: fall 8s linear infinite;
    }

    @keyframes fall {
      0% { transform: translateY(0) rotate(0deg); opacity: 0; }
      10% { opacity: 1; }
      90% { opacity: 1; transform: translateY(850px) rotate(360deg); }
      100% { opacity: 0; transform: translateY(900px) rotate(390deg); }
    }

    .letter:nth-child(1) { left: 10%; animation-delay: 0s; animation-duration: 10s; }
    .letter:nth-child(2) { left: 30%; animation-delay: 2s; animation-duration: 9s; }
    .letter:nth-child(3) { left: 50%; animation-delay: 4s; animation-duration: 11s; }
    .letter:nth-child(4) { left: 70%; animation-delay: 6s; animation-duration: 10s; }
    .letter:nth-child(5) { left: 85%; animation-delay: 8s; animation-duration: 12s; }

    /* 💗 联系我们布局调整 */
    .contact {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 60px;
      padding: 80px 10%;
      position: relative;
      z-index: 2;
    }

    .contact-left img { width: 320px; }

    .contact-right { max-width: 500px; }

    .contact-right h2 {
      color: #D84C8E;
      margin-bottom: 10px;
      font-size: 2rem;
    }

    .contact-right p {
      color: #C94B82;
      font-family: 'Patrick Hand', cursive;
      font-size: 1.1rem;
      line-height: 1.8;
      margin-bottom: 20px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    input, textarea {
      padding: 10px 15px;
      border-radius: 8px;
      border: none;
      outline: none;
      font-size: 1rem;
      background-color: #fff;
    }

    textarea { resize: none; height: 100px; }

    button[type="submit"] {
      background-color: #fcbad3;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      cursor: pointer;
      color: #fff;
      font-weight: bold;
      transition: 0.3s;
    }

    button[type="submit"]:hover { background-color: #f79ab2; }

    .social { margin-top: 15px; }

    .social a {
      color: #b64e7b;
      text-decoration: none;
      font-weight: bold;
      margin: 0 3px;
    }

    .social a:hover { text-decoration: underline; }

    body {
      background: linear-gradient(to bottom, #ffd1e8, #ffbad7);
      overflow-x: hidden;
    }

    .msg-success {
      background: #e3ffe6;
      color: #2e7d32;
      border-left: 5px solid #81c784;
      padding: 10px 15px;
      border-radius: 8px;
      font-weight: 500;
    }

    .msg-error {
      background: #ffe3e3;
      color: #c62828;
      border-left: 5px solid #ef5350;
      padding: 10px 15px;
      border-radius: 8px;
      font-weight: 500;
    }
    /* ==========================
   📱 手机端（最大 600px）
   联系我们页面优化
==========================*/
@media (max-width: 600px) {

  /* 🩷 整个页面 padding 缩小 */
  main {
    padding-top: 80px !important;
  }

  /* 💌 联系我们布局改成上下 */
  .contact {
    flex-direction: column;
    gap: 25px;
    padding: 40px 20px;
  }

  /* 左边插画缩小 */
  .contact-left img {
    width: 65%;
    max-width: 240px;
    margin: 0 auto;
    display: block;
  }

  /* 右边内容宽度缩小 */
  .contact-right {
    max-width: 100%;
    text-align: center;
  }

  /* 标题缩小 */
  .contact-right h2 {
    font-size: 1.6rem;
  }

  /* 说明文字 */
  .contact-right p {
    font-size: 0.95rem;
    line-height: 1.55rem;
  }

  /* 表单输入框宽度拉满 */
  form {
    width: 100%;
    margin: 0 auto;
  }

  input, textarea {
    width: 100%;
    font-size: 0.95rem;
    padding: 12px;
  }

  button[type="submit"] {
    font-size: 1rem;
    padding: 12px 20px;
  }

  /* 社交链接 */
  .social p {
    font-size: 0.95rem;
  }
  .social a {
    font-size: 1rem;
  }

  /* 封信飘落动画 - 缩小 & 不挡内容 */
  .letter {
    width: 40px;
    opacity: 0.55;
  }
}

  </style>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      max-width: 100%;
      overflow-x: hidden;
    }
  </style>
</head>

<body>
  <!-- 🩷 加载动画 -->
  <div id="loader">
    <img src="images/26.png" alt="Loading...">
    <p>Qii 正在准备倾听你的话语中… 💌</p>
  </div>

  <!-- 💌 飘落信封动画 -->
  <div class="floating-letters">
    <img src="images/letter1.png" class="letter">
    <img src="images/letter1.png" class="letter">
    <img src="images/letter1.png" class="letter">
    <img src="images/letter1.png" class="letter">
    <img src="images/letter1.png" class="letter">
  </div>

  <!-- 🩰 Header 导航栏 -->
  <?php include __DIR__ . "/../includes/header.php"; ?>

  <!-- ☎️ 联系我们页面 -->
  <main style="padding-top:120px;">
    <section class="contact">
      <div class="contact-left" data-aos="fade-right">
        <img src="<?= htmlspecialchars($contactImage) ?>" alt="Qiqi with Letter" data-image-key="contact_image">
      </div>

      <div class="contact-right" data-aos="fade-left">
        <h2 data-content-key="contact_title"><?= $contactTitle ?></h2>

        <?php if (isset($success)): ?>
          <div class="msg-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif (isset($error)): ?>
          <div class="msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <p data-content-key="contact_description"><?= $contactDescription ?></p>

        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(qii_frontend_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="text" name="name" placeholder="姓名" required>
          <input type="email" name="email" placeholder="Email" required>
          <textarea name="message" placeholder="想对我们说的话..." required></textarea>
          <button type="submit" data-content-key="contact_button"><?= $contactButton ?></button>
        </form>

        <div class="social">
          <p data-content-key="contact_social_text" style="margin-top:16px; color:#E5679C;"><?= $contactSocialText ?></p>
          <a href="https://www.instagram.com/qii.shoppp?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank">Instagram</a>
        </div>
      </div>
    </section>
  </main>

  <!-- 🩷 页脚 -->
  <?php include __DIR__ . "/../includes/footer.php"; ?>

  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init({ duration: 800, offset: 100, once: true });
    window.addEventListener("load", () => {
      const loader = document.getElementById("loader");
      setTimeout(() => {
        loader.classList.add("fade-out");
        setTimeout(() => { loader.style.display = "none"; }, 600);
      }, 2000);
    });
  </script>
</body>
</html>
