<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
?>

<header class="mobile-app-header">
  <button class="mobile-menu-btn" type="button" aria-label="Menu"><span></span><span></span><span></span></button>
  <a class="mobile-brand" href="index.php">Qii Shop <span>&#128149;</span></a>
  <div class="mobile-actions">
    <button class="mobile-icon-link mobile-search-toggle" type="button" aria-label="Search">
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M16.5 16.5 21 21"></path></svg>
    </button>
    <button class="mobile-cart-shortcut" type="button" aria-label="Cart" onclick="document.getElementById('floating-cart')?.click()">
      <span class="bag-icon">&#128717;</span><span class="cart-count"><?= $cartCount ?></span>
    </button>
  </div>
</header>

<header class="site-header">
  <div class="logo">
    <a href="index.php">
      <img src="images/logo.png" alt="Qii.shop Logo">
    </a>
  </div>

  <nav class="site-nav">
    <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">首页 Home</a>
    <a href="shop.php" class="<?= $currentPage === 'shop.php' ? 'active' : '' ?>">商店 Shop</a>
    <a href="contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">联系我们 Contact</a>
  </nav>

  <div class="nav-right">
    <form class="search-box" action="search.php" method="get">
      <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input type="text" id="searchBox" name="q" placeholder="Search Here..." autocomplete="off" required>
      <div id="suggestions"></div>
    </form>
  </div>
</header>

<div id="mobileSideMenu" class="mobile-side-menu" aria-hidden="true">
  <div class="mobile-side-panel">
    <button class="mobile-menu-close" type="button" aria-label="Close menu">&times;</button>
    <a href="index.php">首页 Home</a>
    <a href="shop.php">商店 Shop</a>
    <a href="contact.php">联系我们 Contact</a>
  </div>
</div>

<div class="mobile-drop-search" id="mobileDropSearch" aria-hidden="true">
  <form action="search.php" method="get">
    <input type="text" name="q" placeholder="Search Here..." autocomplete="off">
  </form>
</div>

<style>
.mobile-app-header { display: none; }
.site-header {
  position: sticky; top: 0; z-index: 1000;
  display: flex; align-items: center; justify-content: space-between; gap: 20px;
  padding: 12px 40px; background: #fff; border-bottom: 1px solid #f3c6d8;
}
.logo img { height: 40px; width: auto; display: block; }
.site-nav { flex: 1; display: flex; justify-content: center; gap: 30px; }
.site-nav a { text-decoration: none; font-weight: 600; color: #ff7fae; font-size: 1rem; transition: color .2s ease; }
.site-nav a:hover, .site-nav a.active { color: #f5368d; }
.nav-right { display: flex; align-items: center; }
.search-box { position: relative; display: flex; align-items: center; background: #f7f7f7; border-radius: 30px; padding: 4px 12px; }
.search-box:focus-within, .search-box:hover { background: #f0f0f0; }
.search-box input { width: 170px; border: 0; outline: 0; background: transparent; font-size: .9rem; color: #333; padding: 6px 8px; }
.search-box input::placeholder { color: #999; }
.search-icon { width: 16px; height: 16px; margin-right: 6px; color: #777; flex-shrink: 0; fill: none; stroke: currentColor; stroke-width: 2; }
#suggestions { position: absolute; top: calc(100% + 8px); left: 0; right: 0; display: none; max-height: 260px; overflow-y: auto; z-index: 3500; background: #fff; border: 1px solid #f1d4df; border-radius: 12px; box-shadow: 0 12px 24px rgba(160,80,126,.16); }
.skeleton { height: 60px; background: linear-gradient(90deg,#eee,#f8f8f8,#eee); background-size: 200%; animation: skeleton 1.2s infinite; }
@keyframes skeleton { 0% { background-position: 0%; } 100% { background-position: -200%; } }
.suggest-item { display: flex; align-items: center; gap: 10px; padding: 10px; cursor: pointer; border-bottom: 1px solid #f8edf3; transition: background .2s; }
.suggest-item:hover { background: #fff4fb; }
.suggest-img { width: 48px; height: 48px; border-radius: 6px; border: 1px solid #f6bdd9; object-fit: cover; }
.suggest-name { font-size: 14px; color: #e5679c; }
.suggest-sku { font-size: 12px; color: #888; }

@media (max-width: 768px) {
  .site-header { display: none !important; }
  .mobile-app-header {
    position: sticky;
    top: 0;
    z-index: 2200;
    display: grid;
    grid-template-columns: 44px 1fr 88px;
    align-items: center;
    min-height: 64px;
    padding: 8px 18px;
    background: rgba(255, 247, 251, .94);
    border-bottom: 1px solid rgba(246, 189, 217, .72);
    box-shadow: 0 8px 22px rgba(214, 82, 142, .08);
    backdrop-filter: blur(18px);
  }
  .mobile-menu-btn,
  .mobile-cart-shortcut,
  .mobile-icon-link {
    border: 0;
    background: transparent;
    color: #5f4e57;
    text-decoration: none;
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    position: relative;
  }
  .mobile-menu-btn {
    flex-direction: column;
    gap: 5px;
  }
  .mobile-menu-btn span {
    display: block;
    width: 22px;
    height: 2px;
    border-radius: 999px;
    background: #6b5963;
  }
  .mobile-icon-link svg {
    width: 21px;
    height: 21px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }
  .mobile-brand {
    justify-self: center;
    color: #d94b8a;
    text-decoration: none;
    font-family: "Patrick Hand", "Comic Sans MS", cursive;
    font-size: 27px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: 0;
    text-shadow: 0 2px 0 rgba(255,255,255,.88);
    white-space: nowrap;
  }
  .mobile-brand span {
    font-size: 20px;
    vertical-align: 7px;
  }
  .mobile-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 6px;
  }
  .mobile-cart-shortcut .bag-icon {
    font-size: 23px;
    line-height: 1;
  }
  .mobile-cart-shortcut .cart-count {
    top: 1px;
    right: 1px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
    font-size: 11px;
  }
}

.mobile-side-menu {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: none;
  background: rgba(0,0,0,.25);
}

.mobile-side-menu.show {
  display: flex;
  align-items: center;
  justify-content: center;
}

.mobile-side-panel {
  width: 82%;
  max-width: 320px;
  min-height: 280px;
  padding: 34px 26px;
  background: #fff7fb;
  border-radius: 28px;
  box-shadow: 0 18px 45px rgba(214,82,142,.25);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 22px;
  position: relative;
}

.mobile-side-panel a {
  color: #d94b8a;
  text-decoration: none;
  font-size: 20px;
  font-weight: 800;
  text-align: center;
}

.mobile-menu-close {
  position: absolute;
  top: 14px;
  right: 18px;
  border: 0;
  background: transparent;
  color: #d94b8a;
  font-size: 34px;
}

.mobile-drop-search {
  display: none;
}

@media (max-width: 768px) {
  .mobile-drop-search {
    position: sticky;
    top: 64px;
    z-index: 2199;
    padding: 10px 16px;
    background: #fff7fb;
    border-bottom: 1px solid #f6bdd9;
  }

  .mobile-drop-search.show {
    display: block;
  }

  .mobile-drop-search input {
    width: 100%;
    height: 44px;
    border: 1px solid #f8c9da;
    border-radius: 999px;
    padding: 0 18px;
    outline: none;
    background: #fff;
    color: #5d4b55;
  }
}

</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector("#searchBox");
  const box = document.querySelector("#suggestions");
  if (!input || !box) return;
  let timer = null;
  input.addEventListener("input", () => {
    const q = input.value.trim();
    if (!q) { box.style.display = "none"; return; }
    box.innerHTML = "<div class='skeleton'></div><div class='skeleton'></div><div class='skeleton'></div>";
    box.style.display = "block";
    clearTimeout(timer);
    timer = setTimeout(() => {
      fetch("api/search_suggest.php?q=" + encodeURIComponent(q))
        .then(res => res.json())
        .then(data => {
          const results = Array.isArray(data.results) ? data.results : [];
          box.innerHTML = "";
          if (!results.length) {
            const empty = document.createElement("div");
            empty.style.cssText = "padding:10px;text-align:center;color:#999;";
            empty.textContent = "Ã¦Â²Â¡Ã¦Å“â€°Ã¦â€°Â¾Ã¥Ë†Â°Ã§â€ºÂ¸Ã¥â€¦Â³Ã¥â€¢â€ Ã¥â€œÂ";
            box.appendChild(empty);
            return;
          }
          results.forEach(item => {
            const div = document.createElement("div");
            div.className = "suggest-item";
            const img = document.createElement("img");
            img.src = item.image_url || "images/logo.png";
            img.className = "suggest-img";
            img.alt = item.name || "";
            const text = document.createElement("div");
            const name = document.createElement("div");
            name.className = "suggest-name";
            name.textContent = item.name || "";
            const sku = document.createElement("div");
            sku.className = "suggest-sku";
            sku.textContent = `SKU: ${item.sku || "-"} Ã‚Â· RM ${item.price || "0.00"}`;
            text.appendChild(name); text.appendChild(sku); div.appendChild(img); div.appendChild(text);
            div.onclick = () => { location.href = "search.php?q=" + encodeURIComponent(item.name || ""); };
            box.appendChild(div);
          });
        })
        .catch(() => { box.innerHTML = "<div style='padding:10px;text-align:center;color:#999;'>Ã¦ÂÅ“Ã§Â´Â¢Ã¥Â¤Â±Ã¨Â´Â¥Ã¯Â¼Å’Ã¨Â¯Â·Ã§Â¨ÂÃ¥ÂÅ½Ã¥â€ ÂÃ¨Â¯â€¢</div>"; });
    }, 180);
  });
  input.addEventListener("keydown", e => {
    if (e.key === "Enter" && input.value.trim()) {
      e.preventDefault(); location.href = "search.php?q=" + encodeURIComponent(input.value.trim());
    }
  });
  document.addEventListener("click", e => {
    if (!input.contains(e.target) && !box.contains(e.target)) box.style.display = "none";
  });

  const searchToggle = document.querySelector(".mobile-search-toggle");
  const dropSearch = document.querySelector(".mobile-drop-search");

  if (searchToggle && dropSearch) {
    searchToggle.addEventListener("click", () => {
      dropSearch.classList.toggle("show");
      dropSearch.querySelector("input")?.focus();
    });
  }

  const menuBtn = document.querySelector(".mobile-menu-btn");
  const sideMenu = document.getElementById("mobileSideMenu");
  const closeBtn = document.querySelector(".mobile-menu-close");

  if (menuBtn && sideMenu && closeBtn) {
    menuBtn.addEventListener("click", () => {
      sideMenu.classList.add("show");
    });

    closeBtn.addEventListener("click", () => {
      sideMenu.classList.remove("show");
    });

    sideMenu.addEventListener("click", (e) => {
      if (e.target === sideMenu) {
        sideMenu.classList.remove("show");
      }
    });
  }
});
</script>
