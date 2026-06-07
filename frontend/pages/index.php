<!DOCTYPE html>
<?php
require_once __DIR__ . '/../../a9sd8f7sd9f_admin/config.php';
require_once __DIR__ . '/../../app/content_settings.php';
$heroTitle = qii_content($pdo, 'hero_title', 'Welcome to qii.shoppp');
$heroSubtitle = qii_content($pdo, 'hero_subtitle', '发现每一份可爱的生活小物');
$heroDescription = qii_content($pdo, 'hero_description', '让每一天，都有一点粉色的温柔与惊喜。');
$heroButton = qii_content($pdo, 'hero_button', '立即购物');
?>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">  <?php require_once __DIR__ . '/../includes/seo.php'; ?>
  <?php qii_seo_meta([
    'title' => 'qii.shoppp | Kawaii Gifts, Phone Charms & Cute Accessories Malaysia',
    'description' => 'Shop cute phone charms, hair accessories, stationery, dolls, snacks and kawaii gifts from qii.shoppp in Malaysia.',
    'path' => '/',
    'keywords' => 'qii.shoppp, kawaii gifts Malaysia, phone charms, cute accessories, hair clips, stationery, dolls, cute shop Malaysia'
  ]); ?>
  <?php qii_store_json_ld(); ?>
  <!-- External Styles -->
  <link rel="stylesheet" href="css/style.css">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

  <!-- Your Inline CSS -->
  <style>
    html, body {
      margin: 0;
      padding: 0;
      max-width: 100%;
      overflow-x: hidden;
    }

    body {
      background: #FFD9E6 !important;
    }

    footer {
      width: 100%;
      background: #FFE0EB;
      padding: 20px 0;
      text-align: center;
      color: #D9488B;
      font-family: "Patrick Hand", cursive;
      font-size: 1rem;
      border-top: 2px solid #FFBFD3;
      position: static;
    }

    /* -------------------------------
   🌸 HERO SECTION 全新版本
   完全不依赖 style.css
--------------------------------*/

.hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 60px 8%;
  background: #FFD9E6;
  margin-top: 20px;
}

.hero-text h1 {
  font-size: 2.6rem;
  font-weight: bold;
  color: #D9488B;
  margin-bottom: 10px;
}

.hero-text h3 {
  font-size: 1.4rem;
  color: #C43A80;
  margin-bottom: 12px;
}

.hero-text p {
  font-size: 1rem;
  color: #A0336B;
  margin-bottom: 16px;
}

.shop-btn {
  background: white;
  color: #E5679C;
  border: 2px solid #E5679C;
  padding: 10px 22px;
  border-radius: 30px;
  cursor: pointer;
  font-size: 1rem;
  transition: 0.3s;
}

.shop-btn:hover {
  background: #E5679C;
  color: white;
}


/* 🌸 白圈背景包住图片 */
.hero-image {
  display: flex;
  justify-content: center;
  align-items: center;
}

.hero-circle {
  width: 360px;
  height: 360px;
  background: white;
  border-radius: 50%;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
  display: flex;
  justify-content: center;
  align-items: center;

  /* 防止图片加载瞬间跳动 */
  overflow: hidden;
}

.hero-circle img {
  width: 78%;
  height: auto;
  object-fit: contain;
}

/* 📱 手机版本 */
@media (max-width: 768px) {
  .hero {
    flex-direction: column-reverse;
    text-align: center;
    padding: 40px 20px;
  }

  .hero-circle {
    width: 240px;
    height: 240px;
    margin-bottom: 20px;
  }

  .hero-circle img {
    width: 85%;
  }

  .hero-text h1 {
    font-size: 2rem;
  }
}

/* -------------------------------
   🌸 ABOUT SECTION — 粉色可爱风
   完全不依赖 style.css
--------------------------------*/

/* 🌸 ABOUT 背景粉色区块 */
.about-section {
  background: #FFD9E6; /* 与 hero 完全一样 */
  padding: 80px 0;
  margin: 0;
  width: 100%;
  border-radius: 0;
}

.about {
  display: flex;
  align-items: center;
  justify-content: center;  /* ⭐ 两边不会被推到最边 */
  padding: 60px 8%;
  gap: 60px;                /* ⭐ 控制图片与文字之间的距离 */
}

.about-left img {
  width: 280px; /* 图小一点间距感更好 */
  max-width: 100%;
}

.about-right h2 {
  font-size: 1.8rem;
  color: #D9488B;
  margin-bottom: 12px;
}

/* 🌸 About 文本白色卡片 */
.about-card {
  background: white;
  padding: 28px 32px;
  border-radius: 20px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.08);
  width: fit-content;
  max-width: 460px; /* 文字区不会太宽 */
  border: 2px solid #FFD3E3;
}

.about-right p {
  font-size: 1rem;
  color: #8A2F61;
  line-height: 1.6rem;
}

/* 第二组反向排列 */
.about.reverse {
  flex-direction: row-reverse;
}

/* 手机版本 */
@media (max-width: 768px) {
  .about {
    flex-direction: column;
    text-align: center;
    padding: 40px 20px;
  }

  .about.reverse {
    flex-direction: column;
  }

  .about-left img {
    width: 240px;
  }

  .about-right h2 {
    font-size: 1.5rem;
  }

  .about-right p {
    font-size: 0.95rem;
  }
}

    /* ✅（新增）加载层基础样式与淡出动画 —— 仅此最小改动 */
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
    /* 🌸 樱花飘落动画（原样保留） */
    .falling-sakura {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      pointer-events: none;
      z-index: 10;
    }
    .sakura {
      position: absolute;
      top: -80px;
      width: 50px;
      opacity: 0.6;
      animation: sakuraFall 10s linear infinite;
    }
    @keyframes sakuraFall {
      0% { transform: translateY(0) rotate(0deg); opacity: 0; }
      10% { opacity: 1; }
      90% { transform: translateY(900px) rotate(360deg); opacity: 1; }
      100% { transform: translateY(1000px) rotate(390deg); opacity: 0; }
    }
    .sakura:nth-child(1) { left: 10%; animation-delay: 0s; animation-duration: 9s; }
    .sakura:nth-child(2) { left: 25%; animation-delay: 2s; animation-duration: 11s; }
    .sakura:nth-child(3) { left: 45%; animation-delay: 4s; animation-duration: 10s; }
    .sakura:nth-child(4) { left: 65%; animation-delay: 6s; animation-duration: 12s; }
    .sakura:nth-child(5) { left: 80%; animation-delay: 8s; animation-duration: 13s; }
    .sakura:nth-child(6) { left: 95%; animation-delay: 10s; animation-duration: 14s; }

    /* 🩷 商品网格布局（原样保留） */
    .product-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
      justify-items: center;
      margin-top: 40px;
    }

    .product-card {
      position: relative;
      background: #FFE9F0;
      border-radius: 20px;
      box-shadow: 0 6px 14px rgba(230,103,156,0.15);
      padding: 20px;
      text-align: center;
      transition: transform 0.3s ease;
      width: 90%;
      max-width: 300px;
      font-family: "Patrick Hand", cursive;
      overflow: hidden;
    }

    .product-card:hover {
      transform: translateY(-8px);
    }

    .product-card img {
      width: 100%;
      border-radius: 12px;
    }

    .product-card h3 {
      color: #E5679C;
      margin: 12px 0 6px;
      font-size: 1.2rem;
    }

    .product-card p {
      color: #C94B82;
      font-weight: 600;
      margin-bottom: 10px;
    }

    .product-card button {
      background: #F6BDD9;
      border: none;
      color: white;
      border-radius: 30px;
      padding: 8px 16px;
      cursor: pointer;
      transition: all 0.3s;
      font-family: "Patrick Hand", cursive;
    }

    .product-card button:hover {
      background: #E5679C;
      transform: scale(1.05);
    }

    /* 🌸 Hover 旋转樱花动画（原样保留） */
    .product-card::after {
      content: "🌸";
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%) scale(0);
      opacity: 0;
      transition: all 0.3s ease;
      font-size: 22px;
      pointer-events: none;
    }

    .product-card:hover::after {
      opacity: 1;
      animation: blossom 1.5s ease-in-out infinite;
    }

    @keyframes blossom {
      0% { transform: translateX(-50%) translateY(0) scale(0); opacity: 0; }
      30% { opacity: 1; transform: translateX(-50%) translateY(-10px) scale(1); }
      70% { transform: translateX(-50%) translateY(-15px) rotate(180deg) scale(1.1); }
      100% { transform: translateX(-50%) translateY(-25px) rotate(360deg) scale(0.9); opacity: 0; }
    }

    @media (max-width: 900px) {
      .product-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 600px) {
      .product-grid {
        grid-template-columns: 1fr;
      }
    }

/* ============================
   📱 手机端优化（≤ 600px）
===============================*/
@media (max-width: 600px) {

  /* 🔥 全局字体缩小一点 */
  body {
    font-size: 14px;
  }

  /* 🔥 Loader 提示字更小一点 */
  #loader p {
    font-size: 1rem;
  }

  /* 🌸 Hero Section 优化 */
  .hero {
    flex-direction: column;
    text-align: center;
    padding: 20px 10px;
  }

  .hero-text h1 {
    font-size: 1.7rem;
    line-height: 2rem;
  }

  .hero-text h3 {
    font-size: 1.1rem;
  }

  .hero-text p {
    font-size: 0.9rem;
  }

  .hero .hero-img-wrap img {
    width: 80%;
    margin-top: 10px;
  }

  /* 🌸 按钮再小一点 */
  .shop-btn {
    padding: 10px 20px;
    font-size: 1rem;
  }

  /* 🍬 商品卡片缩小、间距更舒服 */
  .product-card {
    max-width: 260px;
    padding: 14px;
  }

  .product-card img {
    border-radius: 10px;
  }

  .product-card h3 {
    font-size: 1rem;
  }

  .product-card p {
    font-size: 0.9rem;
  }

  .product-card button {
    padding: 6px 14px;
    font-size: 0.9rem;
  }
}

/* 📱 手机端 Hero 图片在上、文字在下 */
@media (max-width: 600px) {
  .hero {
    display: flex;
    flex-direction: column-reverse !important;
    text-align: center;
    padding: 20px 10px;
  }

  /* 让图片更小更可爱 */
  .hero .hero-img-wrap img {
    width: 70%;
    margin: 0 auto 20px auto;
    display: block;
  }

  /* 文字居中更好看 */
  .hero-text {
    margin-top: 10px;
  }
}

/* 🔒 确保 hero 图片不会跳出白圈 */
.hero-img-wrap {
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden; /* 关键：防止跑出来 */
}

/* 限制图片尺寸，避免瞬间放大溢出 */
.hero-img-wrap img {
  max-width: 85%;
  height: auto;
}
/* 🌸 白色圆圈包住 Qii */
.hero-image {
  display: flex;
  justify-content: center;
  align-items: center;
}

.hero-circle {
  width: 360px;
  height: 360px;
  background: white;
  border-radius: 50%;
  box-shadow: 0 10px 25px rgba(255, 150, 170, 0.25);
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden; /* 防止图片溢出 */
}

.hero-circle img {
  width: 70%;    /* 调整 qii 的大小 */
  height: auto;
  object-fit: contain;
}

/* 🌸 手机端：让圆圈变小 + 居中 */
@media (max-width: 768px) {
  .hero {
    flex-direction: column-reverse;
    text-align: center;
    padding: 40px 20px;
  }

  .hero-circle {
    width: 240px;
    height: 240px;
    margin-bottom: 25px;
  }

  .hero-circle img {
    width: 80%;
  }

  .hero-text h1 {
    font-size: 2rem;
  }
}

/* 📱 超小屏（≤ 480px）进一步优化 */
@media (max-width: 480px) {

  .hero-circle {
    width: 200px;
    height: 200px;
  }

  .hero-circle img {
    width: 85%;
  }

  .hero-text h1 {
    font-size: 1.7rem;
  }

  .hero-text h3 {
    font-size: 1.1rem;
  }

  .shop-btn {
    padding: 8px 18px;
    font-size: 0.9rem;
  }
}
  </style>
</head>

<body>
  <!-- 🌸 樱花飘落动画（原样保留） -->
  <div class="falling-sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
  </div>

  <!-- 🩷 加载动画（仅新增了一行文字p，其余保持） -->
  <div id="loader">
    <img src="images/loading-qii.png" alt="Loading...">
    <p>Qii 正在为你准备粉色世界中... 🌸</p>
  </div>

  <!-- 🩰 Header 导航栏（原样保留） -->
  <?php include __DIR__ . "/../includes/header.php"; ?>

  <!-- 🩰 主体内容（原样保留） -->
  <main id="content">

    <!-- 🌷 Hero Section -->
    <section class="hero">
      <div class="hero-text" data-aos="fade-right">
        <h1><?= htmlspecialchars($heroTitle) ?></h1>
        <h3><?= htmlspecialchars($heroSubtitle) ?></h3>
        <p><?= htmlspecialchars($heroDescription) ?></p>
        <button class="shop-btn" onclick="window.location.href='shop.php'">
    <?= htmlspecialchars($heroButton) ?>
</button>
      </div>

      <div class="hero-image" data-aos="fade-left">
        <div class="hero-circle">
          <img src="images/qii-hero.png" alt="Qiqi with Cart">
        </div>
      </div>
    </section>

    <!-- 🩷 1️⃣ 关于 qii.shoppp -->
    <section class="about-section">
      <div class="about">
        <div class="about-left" data-aos="fade-right">
          <img src="images/qii-bag.png" alt="Qiqi Bag">
        </div>

        <div class="about-right" data-aos="fade-left">
          <h2>关于 qii.shoppp 💌</h2>

          <div class="about-card">
            <p>
              qii.shoppp 是一个关于温柔与日常的小角落。<br>
              我们相信，每个女孩都值得一点被生活宠爱的可爱。<br>
              每件商品，都像一份心意——小小、但刚刚好。
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- 🎁 2️⃣ 每一份礼物（反向） -->
    <section class="about-section">
      <div class="about reverse">
        <div class="about-left" data-aos="fade-left">
          <img src="images/qii-gift.png" alt="Qiqi Gift">
        </div>

        <div class="about-right" data-aos="fade-right">
          <h2>🎁 每一份礼物</h2>

          <div class="about-card">
            <p>
              每一份礼物都承载着特别的心意。<br>
              我们为你准备的，不只是商品，而是一份温柔的陪伴。<br>
              让可爱成为生活的一部分。
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- 🌸 3️⃣ 粉色的日常 -->
    <section class="about-section">
      <div class="about">
        <div class="about-left" data-aos="fade-right">
          <img src="images/2.png" alt="Qiqi Flower">
        </div>

        <div class="about-right" data-aos="fade-left">
          <h2>🌸 粉色的日常</h2>

          <div class="about-card">
            <p>
              每一个小物件，都能让生活多一点甜。<br>
              我们希望，在你的每一天里，都能遇见一点粉色的温柔。<br>
              qii.shoppp — 温柔从这里开始。
            </p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- 🩷 页脚（原样保留） -->
  <?php include __DIR__ . "/../includes/footer.php"; ?>

  <!-- 🌸 动画控制（原逻辑保留，仅loader加了文字不用改JS） -->
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

    /* 🍔 Hamburger Menu Toggle */
    const hamburger = document.querySelector(".hamburger");
    const navMenu = document.querySelector(".nav-menu");
    hamburger.addEventListener("click", () => {
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
    });

  </script>
  
</body>
</html>


