<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>qii.shoppp</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
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
   ?? HERO SECTION ????
   ????? style.css
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


/* ?? ???????? */
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

  /* ?????????? */
  overflow: hidden;
}

.hero-circle img {
  width: 78%;
  height: auto;
  object-fit: contain;
}

/* ?? ???? */
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
   ?? ABOUT SECTION — ?????
   ????? style.css
--------------------------------*/

/* ?? ABOUT ?????? */
.about-section {
  background: #FFD9E6; /* ? hero ???? */
  padding: 80px 0;
  margin: 0;
  width: 100%;
  border-radius: 0;
}

.about {
  display: flex;
  align-items: center;
  justify-content: center;  /* ? ????????? */
  padding: 60px 8%;
  gap: 60px;                /* ? ???????????? */
}

.about-left img {
  width: 280px; /* ????????? */
  max-width: 100%;
}

.about-right h2 {
  font-size: 1.8rem;
  color: #D9488B;
  margin-bottom: 12px;
}

/* ?? About ?????? */
.about-card {
  background: white;
  padding: 28px 32px;
  border-radius: 20px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.08);
  width: fit-content;
  max-width: 460px; /* ??????? */
  border: 2px solid #FFD3E3;
}

.about-right p {
  font-size: 1rem;
  color: #8A2F61;
  line-height: 1.6rem;
}

/* ??????? */
.about.reverse {
  flex-direction: row-reverse;
}

/* ???? */
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

    /* ?(??)???????????? —— ?????? */
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
    /* ?? ??????(????) */
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

    /* ?? ??????(????) */
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

    /* ?? Hover ??????(????) */
    .product-card::after {
      content: "??";
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
   ?? ?????(= 600px)
===============================*/
@media (max-width: 600px) {

  /* ?? ???????? */
  body {
    font-size: 14px;
  }

  /* ?? Loader ??????? */
  #loader p {
    font-size: 1rem;
  }

  /* ?? Hero Section ?? */
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

  /* ?? ?????? */
  .shop-btn {
    padding: 10px 20px;
    font-size: 1rem;
  }

  /* ?? ???????????? */
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

/* ?? ??? Hero ????????? */
@media (max-width: 600px) {
  .hero {
    display: flex;
    flex-direction: column-reverse !important;
    text-align: center;
    padding: 20px 10px;
  }

  /* ???????? */
  .hero .hero-img-wrap img {
    width: 70%;
    margin: 0 auto 20px auto;
    display: block;
  }

  /* ??????? */
  .hero-text {
    margin-top: 10px;
  }
}

/* ?? ?? hero ???????? */
.hero-img-wrap {
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden; /* ??:????? */
}

/* ??????,???????? */
.hero-img-wrap img {
  max-width: 85%;
  height: auto;
}
  </style>
</head>

<body>
  <!-- ?? ??????(????) -->
  <div class="falling-sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
    <img src="images/sakura1.png" class="sakura">
  </div>

  <!-- ?? ????(????????p,????) -->
  <div id="loader">
    <img src="images/loading-qii.png" alt="Loading...">
    <p>Qii ???????????... ??</p>
  </div>

  <!-- ?? Header ???(????) -->
  <?php include __DIR__ . "/includes/header.php"; ?>

  <!-- ?? ????(????) -->
  <main id="content">

    <!-- ?? Hero Section -->
    <section class="hero">
      <div class="hero-text">
        <h1>Welcome to <span>qii.shoppp</span></h1>
        <h3>????????????</h3>
        <p>????,?????????????</p>
        <button class="shop-btn">????</button>
      </div>

      <div class="hero-image" data-aos="fade-left">
        <div class="hero-img-wrap">
          <img src="images/qii-hero.png" alt="Qiqi with Cart">
        </div>
      </div>
    </section>

    <!-- ?? 1?? ?? qii.shoppp -->
    <section class="about-section">
      <div class="about">
        <div class="about-left" data-aos="fade-right">
          <img src="images/qii-bag.png" alt="Qiqi Bag">
        </div>

        <div class="about-right" data-aos="fade-left">
          <h2>?? qii.shoppp ??</h2>

          <div class="about-card">
            <p>
              qii.shoppp ???????????????<br>
              ????,??????????????????<br>
              ????,??????——????????
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ?? 2?? ?????(??) -->
    <section class="about-section">
      <div class="about reverse">
        <div class="about-left" data-aos="fade-left">
          <img src="images/qii-gift.png" alt="Qiqi Gift">
        </div>

        <div class="about-right" data-aos="fade-right">
          <h2>?? ?????</h2>

          <div class="about-card">
            <p>
              ???????????????<br>
              ???????,?????,??????????<br>
              ????????????
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ?? 3?? ????? -->
    <section class="about-section">
      <div class="about">
        <div class="about-left" data-aos="fade-right">
          <img src="images/2.png" alt="Qiqi Flower">
        </div>

        <div class="about-right" data-aos="fade-left">
          <h2>?? ?????</h2>

          <div class="about-card">
            <p>
              ??????,??????????<br>
              ????,???????,????????????<br>
              qii.shoppp — ????????
            </p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ?? ??(????) -->
  <?php include __DIR__ . "/includes/footer.php"; ?>

  <!-- ?? ????(?????,?loader???????JS) -->
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

    /* ?? Hamburger Menu Toggle */
    const hamburger = document.querySelector(".hamburger");
    const navMenu = document.querySelector(".nav-menu");
    hamburger.addEventListener("click", () => {
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
    });

  </script>
  
</body>
</html>
