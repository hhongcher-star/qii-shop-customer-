<header>
  <div class="logo">
    <a href="/">
      <img src="images/logo.png" alt="Yummy Diary Logo">
    </a>
  </div>

  <nav>
    <a href="/">🏠 Home</a>
    <a href="/shop.php">🛒 Shop</a>
    <a href="/contact.php">📮 Contact</a>
  </nav>

  <div class="nav-right">
    <form class="search-box" action="/search.php" method="get" style="position:relative;">
      <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>

      <input type="text" id="searchBox" name="q" placeholder="Search Here..." autocomplete="off" required>

      <div id="suggestions"></div>
    </form>
  </div>
</header>

<style>
/* ========== 顶部导航 ========== */
header {
  position: sticky;
  top: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 40px;
  background: #ffffff;
  border-bottom: 1px solid #e5e5e5;
}

.logo img {
  height: 40px;
  width: auto;
}

nav {
  flex: 1;
  display: flex;
  justify-content: center;
  gap: 30px;
}

nav a {
  text-decoration: none;
  font-weight: 500;
  color: #FF7FAE;
  font-size: 1rem;
  transition: color 0.2s ease;
}

nav a:hover {
  color: #FF4F93;
}

.nav-right {
  display: flex;
  align-items: center;
}

.search-box {
  display: flex;
  align-items: center;
  background-color: #f5f5f5;
  border-radius: 30px;
  padding: 4px 12px;
  transition: background-color 0.3s ease;
}

.search-box input {
  border: none;
  outline: none;
  background: none;
  font-size: 0.9rem;
  color: #333;
  padding: 6px 8px;
}

.search-box input::placeholder {
  color: #999;
}

.search-box:hover,
.search-box:focus-within {
  background-color: #eaeaea;
}

.search-icon {
  width: 16px;
  height: 16px;
  margin-right: 6px;
  color: #777;
  flex-shrink: 0;
}

/* header 响应式 */
@media (max-width: 768px) {
  header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  nav {
    justify-content: center;
    width: 100%;
    gap: 20px;
    margin-top: 5px;
  }

  .nav-right {
    width: 100%;
    margin-top: 8px;
    display: flex;
    justify-content: flex-end;
  }

  .search-box {
    width: 100%;
    max-width: 300px;
  }
}

@media (max-width: 480px) {
  header {
    padding: 8px 15px;
  }

  nav {
    gap: 10px;
  }

  nav a {
    font-size: 0.9rem;
  }
}
</style>

<style>
/* 搜索建议框 */
#suggestions {
  position:absolute;
  top:100%; left:0; right:0;
  background:white;
  border-radius:10px;
  border:1px solid #eee;
  border-top:none;
  max-height:260px;
  overflow-y:auto;
  z-index:9999;
  display:none;
}

.skeleton {
  height:60px;
  background:linear-gradient(90deg,#eee,#f8f8f8,#eee);
  background-size:200%;
  animation:skeleton 1.2s infinite;
}
@keyframes skeleton {
  0% { background-position:0% }
  100% { background-position:-200% }
}

.suggest-item {
  display:flex;
  align-items:center;
  gap:10px;
  padding:10px;
  cursor:pointer;
  border-bottom:1px solid #f5f5f5;
  transition:0.2s;
}
.suggest-item:hover {
  background:#fff4fb;
}

.suggest-img {
  width:48px; height:48px;
  border-radius:6px;
  border:1px solid #f6bdd9;
  object-fit:cover;
}

.suggest-name {
  font-size:14px;
  color:#e5679c;
}
.suggest-sku {
  font-size:12px;
  color:#888;
}

@media(max-width:600px) {
  #suggestions {
    position:fixed;
    left:10px; right:10px;
    top:60px;
    max-height:70vh;
    box-shadow:0 0 10px rgba(0,0,0,0.15);
  }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector("#searchBox");
  const box = document.querySelector("#suggestions");

  let timer = null;

  input.addEventListener("input", () => {
    const q = input.value.trim();

    if (!q) {
      box.style.display = "none";
      return;
    }

    // Skeleton
    box.innerHTML = `
      <div class='skeleton'></div>
      <div class='skeleton'></div>
      <div class='skeleton'></div>
    `;
    box.style.display = "block";

    clearTimeout(timer);
    timer = setTimeout(() => {
      fetch("/search_suggest.php?q=" + encodeURIComponent(q))
        .then(res => res.json())
        .then(data => {
          if (!data.results.length) {
            box.innerHTML = `
              <div style="padding:10px; text-align:center; color:#999;">
                没有找到相关商品
              </div>
            `;
            return;
          }

          box.innerHTML = "";
          data.results.forEach(item => {
            const div = document.createElement("div");
            div.className = "suggest-item";
            div.innerHTML = `
              <img src="${item.image_url}" class="suggest-img">
              <div>
                <div class="suggest-name">${item.name}</div>
                <div class="suggest-sku">SKU: ${item.sku} � RM ${item.price}</div>
              </div>
            `;
            div.onclick = () => {
              location.href = "/search.php?q=" + encodeURIComponent(item.name);
            };
            box.appendChild(div);
          });
          });
        });
    }, 150);
  });

  // Enter 跳转
  input.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      location.href = "/search.php?q=" + encodeURIComponent(input.value);
    }
  });

  // 点击外部关闭
  document.addEventListener("click", e => {
    if (!input.contains(e.target) && !box.contains(e.target)) {
      box.style.display = "none";
    }
  });
});
</script>

