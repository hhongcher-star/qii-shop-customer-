// Extracted from frontend/pages/shop.php. Keep page-specific shop behavior here.
(() => {
      const productArea = document.querySelector('.product-area');
      const categoryTitle = document.querySelector('.category-title');
      const productSection = document.querySelector('#shop-products');
      if (!productArea || !productSection) return;

      let activeRequest = null;

      function paginationHtml(category, page, totalPages) {
        if (totalPages <= 1) return '';
        const previous = page > 1
          ? `<a href="shop.php?cat=${encodeURIComponent(category)}&page=${page - 1}#shop-products">上一页</a>` : '';
        const next = page < totalPages
          ? `<a href="shop.php?cat=${encodeURIComponent(category)}&page=${page + 1}#shop-products">下一页</a>` : '';
        return `<nav class="shop-pagination" aria-label="商品分页" data-shop-pagination>${previous}<span>第 ${page} / ${totalPages} 页</span>${next}</nav>`;
      }

      async function loadShop(category, page, updateHistory = true) {
        activeRequest?.abort();
        activeRequest = new AbortController();
        productArea.classList.add('shop-products-loading');

        try {
          const url = `shop.php?cat=${encodeURIComponent(category)}&page=${page}&ajax=1`;
          const response = await fetch(url, { signal: activeRequest.signal, headers: { Accept: 'application/json' } });
          if (!response.ok) throw new Error(`HTTP ${response.status}`);
          const data = await response.json();
          productArea.innerHTML = data.html;
          productSection.querySelector('[data-shop-pagination]')?.remove();
          productArea.insertAdjacentHTML('afterend', paginationHtml(data.category, data.page, data.total_pages));

          document.querySelectorAll('.cat-link').forEach(item => {
            const active = item.dataset.cat === data.category;
            item.classList.toggle('active', active);
            if (active && categoryTitle) {
              const emoji = item.querySelector('.cat-emoji')?.textContent.trim() || '';
              categoryTitle.innerHTML = (emoji ? emoji + ' ' : '') + (item.dataset.html || item.dataset.label || item.textContent.trim());
              categoryTitle.style.removeProperty('color');
            }
          });

          if (updateHistory) history.pushState({ category: data.category, page: data.page }, '', `shop.php?cat=${encodeURIComponent(data.category)}&page=${data.page}#shop-products`);
          productSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
          if (error.name !== 'AbortError') alert('商品加载失败，请稍后再试。');
        } finally {
          productArea.classList.remove('shop-products-loading');
        }
      }

      document.addEventListener('click', event => {
        const categoryLink = event.target.closest('.cat-link a');
        const pageLink = event.target.closest('[data-shop-pagination] a');
        const link = categoryLink || pageLink;
        if (!link) return;
        event.preventDefault();
        const url = new URL(link.href, location.href);
        loadShop(url.searchParams.get('cat') || '', Math.max(1, Number(url.searchParams.get('page')) || 1));
      });

      window.addEventListener('popstate', () => {
        const params = new URLSearchParams(location.search);
        loadShop(params.get('cat') || '', Math.max(1, Number(params.get('page')) || 1), false);
      });
    })();

if (window.AOS) {
  AOS.init({ duration: 800, once: true });
}

    // Loader ÃƒÂ¥Ã…Â Ã‚Â¨ÃƒÂ§Ã¢â‚¬ÂÃ‚Â»
    window.addEventListener("load", () => {
      const loader = document.getElementById("loader");
      setTimeout(() => {
        loader.classList.add("fade-out");
        setTimeout(() => (loader.style.display = "none"), 600);
      }, 1800);
    });

// Decorative candy animation.
document.querySelectorAll(".sakura").forEach((el, i) => {
  el.style.left = Math.random() * 100 + "vw";          
  el.style.animationDuration = 8 + Math.random()*6 + "s";
  el.style.animationDelay = Math.random()*3 + "s";
  el.style.opacity = 0.4 + Math.random() * 0.6;
});
// ÃƒÂ°Ã…Â¸Ã¢â‚¬â€œÃ‚Â¼ÃƒÂ¯Ã‚Â¸Ã‚Â ÃƒÂ§Ã¢â‚¬Å¡Ã‚Â¹ÃƒÂ¥Ã¢â‚¬Â¡Ã‚Â»ÃƒÂ¥Ã¢â‚¬Â¢Ã¢â‚¬Â ÃƒÂ¥Ã¢â‚¬Å“Ã‚ÂÃƒÂ¥Ã¢â‚¬ÂºÃ‚Â¾ÃƒÂ§Ã¢â‚¬Â°Ã¢â‚¬Â¡ ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ ÃƒÂ¦Ã¢â‚¬ÂÃ‚Â¾ÃƒÂ¥Ã‚Â¤Ã‚Â§ÃƒÂ©Ã‚Â¢Ã¢â‚¬Å¾ÃƒÂ¨Ã‚Â§Ã‹â€ 
document.addEventListener("click", function(e) {
  const favoriteButton = e.target.closest("[data-favorite-product]");
  if (favoriteButton) {
    e.preventDefault();
    e.stopPropagation();
    const token = document.querySelector('meta[name="qii-csrf-token"]')?.content || "";
    const body = new URLSearchParams({ product_id: favoriteButton.dataset.favoriteProduct });
    fetch("api/toggle_favorite.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-QII-CSRF-Token": token
      },
      body
    }).then(async response => {
      const data = await response.json();
      if (response.status === 401 || data.login_required) {
        location.href = "login.php?next=" + encodeURIComponent(location.pathname + location.search);
        return;
      }
      if (!data.success) throw new Error(data.message || "Favorite failed");
      favoriteButton.classList.toggle("active", data.favorite);
      const icon = favoriteButton.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-solid", data.favorite);
        icon.classList.toggle("fa-regular", !data.favorite);
      } else {
        favoriteButton.textContent = data.favorite ? "已收藏" : "收藏";
      }
    }).catch(() => {
      alert("收藏失败，请稍后再试。");
    });
    return;
  }

  // ÃƒÂ¥Ã‚ÂÃ‚ÂªÃƒÂ¦Ã¢â‚¬ÂÃ‚Â¾ÃƒÂ¥Ã‚Â¤Ã‚Â§ product-card ÃƒÂ§Ã…Â¡Ã¢â‚¬Å¾ÃƒÂ¥Ã¢â‚¬ÂºÃ‚Â¾ÃƒÂ§Ã¢â‚¬Â°Ã¢â‚¬Â¡ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¤Ã‚Â¸Ã‚ÂÃƒÂ¦Ã¢â‚¬ÂÃ‚Â¾ÃƒÂ¥Ã‚Â¤Ã‚Â§ header/footer/logo
  if (e.target.matches(".product-card img")) {
    const modal = document.getElementById("imgPreview");
    const modalImg = document.getElementById("imgPreviewPic");
    if (!modal || !modalImg) return;

    modalImg.src = e.target.src;  // ÃƒÂ§Ã¢â‚¬ÂÃ‚Â¨ÃƒÂ¤Ã‚Â½Ã‚Â ÃƒÂ§Ã…Â¡Ã¢â‚¬Å¾ÃƒÂ¥Ã…Â½Ã…Â¸ÃƒÂ¥Ã¢â‚¬ÂºÃ‚Â¾ÃƒÂ¯Ã‚Â¼Ã…â€™ÃƒÂ¤Ã‚Â¸Ã‚ÂÃƒÂ¦Ã¢â‚¬ÂÃ‚Â¹ÃƒÂ¨Ã‚Â·Ã‚Â¯ÃƒÂ¥Ã‚Â¾Ã¢â‚¬Å¾
    modal.style.display = "flex";
  }
});

// ÃƒÂ§Ã¢â‚¬Å¡Ã‚Â¹ÃƒÂ¥Ã¢â‚¬Â¡Ã‚Â»ÃƒÂ¨Ã†â€™Ã…â€™ÃƒÂ¦Ã¢â€žÂ¢Ã‚Â¯ÃƒÂ¥Ã¢â‚¬Â¦Ã‚Â³ÃƒÂ©Ã¢â‚¬â€Ã‚Â­ÃƒÂ©Ã‚Â¢Ã¢â‚¬Å¾ÃƒÂ¨Ã‚Â§Ã‹â€ 
const imagePreview = document.getElementById("imgPreview");
if (imagePreview) {
  imagePreview.addEventListener("click", function() {
    this.style.display = "none";
  });
}

function qiiToast(msg) {
      const t = document.getElementById("qiiToast");
      if (!t) return;
      t.textContent = msg;
      t.classList.add("show");
      setTimeout(() => t.classList.remove("show"), 2000);
    }

