<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>

<footer class="site-footer">
  <div class="floating-buttons">
    <button class="floating-cart" id="floating-cart">
      🛍️<span class="cart-count"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span>
    </button>
    <button class="floating-speaker" id="floating-speaker">📣</button>
  </div>

  <div class="footer-info">
    <p>c 2025 qii.shoppp | All Rights Reserved</p>
  </div>
  
</footer>

<!-- 购物袋弹窗 -->
<div id="cartModal" class="modal">
  <div class="modal-card">
    <span class="close">&times;</span>
    <img src="images/27.png" alt="Qii 坐在购物袋上" class="qii-on-bag">
    <h2>Qii 购物袋</h2>
    <div class="cart-content"><!-- JS 渲染 --></div>
    <div class="cart-footer">
      <button id="clearCartBtn">清空</button>
      <button id="checkoutBtn"><strong>去结账</strong></button>
    </div>
  </div>
  
</div>

<style>
.site-footer {
  background: #fff6fa;
  text-align: center;
  padding: 20px 10px 80px;
  font-size: 14px;
  color: #c94b82;
  border-top: 2px solid #f6bdd9;
}
.site-footer a { color: #c94b82; text-decoration: none; }
.site-footer a:hover { text-decoration: underline; }

/* Floating buttons */
.floating-buttons {
  position: fixed;
  bottom: 70px;
  right: 16px; /* 稍微往内一点避免溢出 */
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 2000;
}
.floating-cart {
  position: relative;
  background: #fff;
  border-radius: 50%;
  border: 2px solid #f6bdd9;
  color: #e5679c;
  width: 65px;
  height: 65px;
  font-size: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 12px rgba(0,0,0,0.15);
  cursor: pointer;
  transition: transform .3s ease, background .3s;
}
.floating-cart:hover { transform: scale(1.1); background: #fff0f7; }

.floating-speaker {
  position: relative;
  background: #fff;
  border-radius: 50%;
  border: 2px solid #f6bdd9;
  color: #e5679c;
  width: 55px;
  height: 55px;
  font-size: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 12px rgba(0,0,0,0.15);
  cursor: pointer;
  transition: transform .3s ease, background .3s;
}
.floating-speaker:hover {
  transform: scale(1.1);
  background: #fff0f7;
}

@media (max-width: 600px) {
  .floating-buttons {
    bottom: 75px;
    right: 10px;
  }
  .floating-cart,
  .floating-speaker {
    transform: scale(0.9);
  }
}

/* Override floating buttons for safer layout */
.floating-buttons {
  position: fixed;
  bottom: 20px;
  right: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  z-index: 2000;
}
.floating-cart,
.floating-speaker {
  position: relative;
}

/* 📱 手机模式按钮往左一点 */
@media (max-width: 600px) {
  .floating-buttons {
    right: 8px !important;
    bottom: 30px;
  }
  .floating-cart,
  .floating-speaker {
    transform: scale(0.92);
  }
}

.cart-count {
  position: absolute;
  top: 5px; right: 8px;
  background: #e5679c;
  color: #fff;
  font-size: 13px;
  padding: 2px 7px;
  border-radius: 50%;
  font-weight: bold;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

/* 购物袋弹窗 */
.modal {
  display: none; position: fixed;
  left: 0; top: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.3);
  z-index: 3000;
}
.modal-card {
  background: #fff;
  width: 90%;
  max-width: 420px;
  padding: 20px;
  border-radius: 20px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  animation: fadeIn .3s ease;
}
@keyframes fadeIn { from {opacity:0;transform:translate(-50%,-55%);} to {opacity:1;transform:translate(-50%,-50%);} }
.close {
  position: absolute; right: 15px; top: 10px;
  font-size: 20px; cursor: pointer; color: #c94b82;
}
.close:hover { color: #e5679c; }
.modal-card h2 { text-align: center; color: #e5679c; }

.cart-content ul { list-style: none; padding: 0; margin: 0; }
.cart-item {
  display: flex; align-items: flex-start;
  justify-content: space-between;
  border-bottom: 1px solid #f6bdd9;
  padding: 10px 0;
}
.cart-thumb { width: 55px; height: 55px; border-radius: 8px; object-fit: cover; border:1px solid #f6bdd9;}
.cart-info { flex:1; margin-left:10px; text-align:left;}
.cart-name { color:#c94b82; font-size:14px; margin-bottom:4px;}
.cart-meta { font-size:12px; color:#777;}
.cart-qty { display:flex; align-items:center; gap:6px; margin:5px 0;}
.qty-btn {
  width:24px; height:24px;
  border:1px solid #f6bdd9;
  border-radius:5px;
  color:#e5679c;
  background:#fff;
  cursor:pointer;
  font-weight:bold;
  transition:.2s;
}
.qty-btn:hover { background:#f6bdd9; color:#fff; }
.qty-value { min-width:20px; text-align:center; }

.cart-summary {
  text-align:right;
  margin-top:10px;
  border-top:1px solid #f6bdd9;
  padding-top:8px;
  color:#c94b82;
  line-height:1.6;
}
.cart-summary strong { font-size:15px; color:#e5679c; }

/* 地区选择（西马 / 东马） */
.region-select {
  text-align:right;
  margin-top: 10px;
  color:#e5679c;
  font-size:14px;
}
.region-select input { accent-color: #e5679c; }

.cart-footer {
  margin-top: 15px;
  display: flex; justify-content: space-around;
}
.cart-footer button {
  background: transparent;
  border: 2px solid #e5679c;
  color: #e5679c;
  padding: 8px 18px;
  border-radius: 25px;
  cursor: pointer;
  font-weight: bold;
  transition: all .3s;
}
.cart-footer button:hover {
  background: #e5679c;
  color: #fff;
}

/* Qii 浮动动画（与喇叭/规则共用） */
.qii-on-bag {
  position: absolute;
  top: -80px;
  left: 50%;
  transform: translateX(-50%);
  width: 130px;
  z-index: 10;
  pointer-events: none;
  animation: qiiFloat 2.5s ease-in-out infinite;
}
@keyframes qiiFloat {
  0%, 100% { transform: translate(-50%, 0); }
  50% { transform: translate(-50%, -6px); }
}

@media (max-width: 600px) {
  .modal-card {
    width: 80%;
    padding: 15px;
  }
  .qii-on-bag {
    width: 110px;
    top: -60px;
  }
}

/* 规则/公告弹窗通用样式 */
.rules-popup {
  position: fixed; inset: 0;
  background: rgba(255,230,240,0.7);
  backdrop-filter: blur(10px);
  display: flex; justify-content: center; align-items: center;
  z-index: 5000;
}
.rules-content {
  background: #fff; border-radius: 20px;
  box-shadow: 0 6px 20px rgba(230,103,156,0.3);
  width: 90%; max-width: 450px;
  text-align: center; padding: 25px 20px;
  position: relative;
}
.rules-content h2 { color: #e5679c; margin-bottom: 10px; }
.rules-image { width: 100%; border-radius: 15px; margin: 10px 0; }
.qii-float {
  position: absolute;
  top: -80px;
  left: 50%;
  transform: translateX(-50%);
  width: 130px;
  animation: qiiFloat 2.5s ease-in-out infinite;
}
#closeRules {
  background: #e5679c;
  border: none;
  color: white;
  padding: 8px 20px;
  border-radius: 20px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}
#closeRules:hover { background: #c94b82; }

/* 规则弹窗增强样式与动画 */
.fade-in { animation: rulesFadeIn 0.45s ease; }
@keyframes rulesFadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.rules-text { font-size: 14px; line-height: 1.7; color: #b55678; margin-top: 10px; white-space: normal; }
.qii-float { top: -85px; animation: qiiFlyIn 1s ease-out, qiiFloat 2.5s ease-in-out infinite 1s; pointer-events: none; }
@keyframes qiiFlyIn { 0% { transform: translate(-50%, -40px); opacity: 0; } 100% { transform: translate(-50%, 0); opacity: 1; } }
#closeRules { padding: 8px 22px; font-size: 14px; margin-top: 18px; transition: 0.25s; }

/* 🌸 图2公告大卡片尺寸优化 */
.big-rules {
  max-width: 500px;
  padding: 30px 25px;
}

/* 手机优化 */
@media (max-width: 600px) {
  .big-rules {
    width: 90%;
    padding: 20px 18px;
  }
  .big-rules h2 {
    font-size: 20px;
  }
  .rules-text {
    font-size: 14px;
    line-height: 1.5;
  }
  #closeSpeakerPopup {
    width: 100%;
    padding: 10px 0;
    font-size: 15px;
  }
  .qii-float {
    width: 110px;
    top: -70px;
  }
}

/* 粉色标题 */
.pink-title {
  color: #e5679c;
  font-size: 24px;
  margin-bottom: 10px;
}

/* 粉色内容 */
.text-block p {
  color: #c94b82;
  font-size: 15px;
  line-height: 1.7;
  margin: 10px 0;
}

/* 分隔线 */
.text-block hr {
  border: none;
  border-top: 1px solid #f6bdd9;
  margin: 15px 0;
}

/* 粉色按钮（我已阅读完毕） */
.pink-btn {
  background: #e5679c;
  color: white;
  padding: 10px 25px;
  border: none;
  border-radius: 25px;
  font-size: 15px;
  font-weight: bold;
  cursor: pointer;
  margin-top: 15px;
  transition: .25s;
}

.pink-btn:hover {
  background: #d44a86;
}

/* 手机端优化 */
@media (max-width: 600px) {
  .big-rules {
    padding: 20px 18px;
  }
  .pink-title {
    font-size: 20px;
  }
  .text-block p {
    font-size: 14px;
  }
  .pink-btn {
    width: 100%;
    font-size: 15px;
  }
  .qii-float {
    width: 110px;
    top: -70px;
  }
}

/* 🎀 公告标题更可爱 */
.popup-title {
  font-size: 24px;
  color: #e5679c;
  font-weight: 700;
  margin-bottom: 12px;
  font-family: "Patrick Hand", cursive;
}

/* 🎀 内容文字更舒服 */
.popup-text p {
  color: #c94b82;
  font-size: 15px;
  line-height: 1.7;
  margin: 8px 0;
  font-family: "Patrick Hand", cursive;
}

/* 重点文字橘色 */
.popup-text .highlight {
  color: #e58d6f;
  font-weight: 600;
}

/* 红色警告更清楚但是可爱 */
.popup-text .warn {
  color: #e05a86;
  font-weight: bold;
}

/* 分隔线更淡更少女 */
.rules-content hr {
  border: none;
  border-top: 1px dashed #f6bdd9;
  margin: 12px 0;
}

/* 🎀 超漂亮的“我已阅读完毕”按钮 */
.pink-confirm-btn {
  background: linear-gradient(145deg, #f6bdd9, #e88ab0);
  border: none;
  color: white;
  padding: 12px 28px;
  border-radius: 30px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  margin-top: 20px;
  width: 75%;
  transition: all .25s ease;
  box-shadow: 0 4px 10px rgba(230,103,156,0.25);
}

.pink-confirm-btn:hover {
  background: linear-gradient(145deg, #e5679c, #d14b84);
  transform: scale(1.04);
  box-shadow: 0 4px 15px rgba(230,103,156,0.35);
}

/* 卡片更立体、更少女 */
.cute-popup {
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 10px 35px rgba(230,103,156,0.25);
}

/* 手机优化 */
@media (max-width: 600px) {
  .popup-title { font-size: 20px; }
  .popup-text p { font-size: 14px; }
  .pink-confirm-btn { width: 100%; font-size: 15px; }
  .qii-float { width: 110px; top: -70px; }
}

</style>

<script>
// ===============
// 🌸 公告弹窗函数
// ===============
function showAnnouncementPopup() {
  const popup = document.createElement("div");
  popup.classList.add("rules-popup");

  popup.innerHTML = `
    <div class="rules-content big-rules cute-popup fade-in">

      <img src="images/27.png" class="qii-float" alt="Qii Girl">

      <h2 class="popup-title">💕 请阅读完毕～</h2>

      <div class="popup-text">
        <p>本店是以直播间过款为主，有卖各种可爱商品💕</p>
        <p>价格优惠质量不错</p>

        <p class="highlight">🧡 可存单（需付款即可）付款后存多久都没问题</p>

        <hr>

        <p>西马10满65 🍞　东马15满80 🍞</p>
        <p>发货时间：1-3-6（有发货都会先在群通知）</p>

        <hr>

        <p class="warn">请各位理性消费　先付后选<br>
        如发现逃单一律公开＋永久拉黑‼️</p>
      </div>

      <button id="closeSpeakerPopup" class="pink-confirm-btn">我已阅读完毕</button>
    </div>
  `;

  document.body.appendChild(popup);

  document.getElementById("closeSpeakerPopup").onclick = () => popup.remove();
}

// 获取购物袋内容并更新
function getCartAndUpdate() {
  return fetch("add_to_cart.php?mode=getCart")
    .then(res => res.json())
    .then(data => { if (data.success) updateCartUI(data); })
    .catch(err => console.error("getCart failed", err));
}

// 更新购物袋UI
function updateCartUI(data) {
  const countEl = document.querySelector(".cart-count");
  const cartContent = document.querySelector(".cart-content");
  if (countEl) countEl.textContent = data.count ?? 0;

  if (!data.cart || data.cart.length === 0) {
    cartContent.innerHTML = '<p style="text-align:center;color:#aaa;">购物袋空空的~喵</p>';
    return;
  }

  // 当前地区（默认西马）
  const currentRegion = document.querySelector('input[name="region"]:checked')?.value || 'west';

  let html = '<ul>';
  let total = 0;
  data.cart.forEach(item => {
    const price = parseFloat(item.price);
    const qty = parseInt(item.qty, 10);
    const subtotal = price * qty;
    total += subtotal;
    html += `
      <li class="cart-item" data-sku="${item.sku}">
        <img src="${item.img}" class="cart-thumb">
        <div class="cart-info">
          <div class="cart-name">${item.name}</div>
          <div class="cart-qty">
            <button class="qty-btn dec" data-sku="${item.sku}" data-name="${item.name}" data-price="${item.price}" data-img="${item.img}">-</button>
            <span class="qty-value">${qty}</span>
            <button class="qty-btn inc" data-sku="${item.sku}" data-name="${item.name}" data-price="${item.price}" data-img="${item.img}">+</button>
          </div>
          <div class="cart-meta">单价:RM${price.toFixed(2)} 小计:RM${subtotal.toFixed(2)}</div>
        </div>
      </li>`;
  });

  // 地区选择器（插入在小计区域之前）
  html += `</ul>
    <div class="region-select">
      <label>
        <input type="radio" name="region" value="west" ${currentRegion === 'west' ? 'checked' : ''}>
        西马 (West MY)
      </label>
      <label style="margin-left:15px;">
        <input type="radio" name="region" value="east" ${currentRegion === 'east' ? 'checked' : ''}>
        东马 (East MY)
      </label>
    </div>`;

  // 按地区计算运费与免邮门槛
  let shipping_cost = 0;
  if (currentRegion === 'west') {
    shipping_cost = total >= 65 ? 0 : 10;
  } else {
    shipping_cost = total >= 80 ? 0 : 15;
  }
  const grand_total = total + shipping_cost;

  html += `
    <div class="cart-summary">
      <div>小计:RM${total.toFixed(2)}</div>
      <div>运费:RM${shipping_cost.toFixed(2)}</div>
      <div><strong>合计:RM${grand_total.toFixed(2)}</strong></div>
    </div>`;
  cartContent.innerHTML = html;
}

// 初始化交互
document.addEventListener("DOMContentLoaded", () => {
  // ===============
  // 🌸 每次进入网站自动弹出公告
  // ===============
  showAnnouncementPopup();
  
  getCartAndUpdate();

  const fab = document.getElementById("floating-cart");
  const modal = document.getElementById("cartModal");
  const close = document.querySelector(".close");
  fab.onclick = () => modal.style.display = "block";
  close.onclick = () => modal.style.display = "none";
  window.onclick = (e) => { if (e.target === modal) modal.style.display = "none"; };

  // 数量增减
  document.querySelector(".cart-content").addEventListener("click", e => {
    const t = e.target;
    if (t.classList.contains("dec") || t.classList.contains("inc")) {
      const fd = new FormData();
      fd.append("sku", t.dataset.sku);
      fd.append("name", t.dataset.name);
      fd.append("price", t.dataset.price);
      fd.append("img", t.dataset.img);
      const url = t.classList.contains("dec") ? "add_to_cart.php?mode=removeOne" : "add_to_cart.php";
      fetch(url, { method:"POST", body: fd })
        .then(r => r.json())
        .then(d => updateCartUI(d))
        .catch(() => getCartAndUpdate());
    }
  });

  // 清空
  const clearBtn = document.getElementById("clearCartBtn");
  if (clearBtn) clearBtn.addEventListener("click", () => {
    fetch("add_to_cart.php?mode=clear")
      .then(r => r.json())
      .then(d => {
        updateCartUI(d);
        try {
          if (window.outOfStockSKU && typeof window.outOfStockSKU.clear === 'function') {
            window.outOfStockSKU.clear();
          }
          document.querySelectorAll('.add-btn').forEach(btn => {
            if (btn.textContent && btn.textContent.includes('已售罄')) {
              btn.disabled = false;
              btn.textContent = '加入购物袋';
              btn.style.background = '#f6bdd9';
              btn.style.color = '#fff';
              btn.style.cursor = 'pointer';
              btn.style.opacity = '1';
              btn.style.transform = 'scale(1)';
            }
          });
        } catch (e) { /* no-op */ }
      });
  });

  // 去结账
  const checkoutBtn = document.getElementById("checkoutBtn");
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {

      const region = document.querySelector('input[name="region"]:checked')?.value;

      if (!region) {
        alert("请选择地区（西马 / 东马）后才能结账喵💕");
        return;
      }

      const fd = new FormData();
      fd.append("region", region);

      fetch("checkout.php", {
          method: "POST",
          body: fd
      })
        .then((r) => r.json())
        .then((d) => {
          if (d.success && d.redirect) {
            window.location.href = d.redirect;
          } else {
            alert(d.msg || "结账失败，请稍后重试");
          }
        })
        .catch(() => console.error("Checkout error"));
    });
  }

  // 监听地区切换，自动刷新小计与运费
  document.addEventListener("change", (e) => {
    if (e.target && e.target.name === "region") {
      getCartAndUpdate();
    }
  });

  // 📣 喇叭按钮再次打开公告
  const speaker = document.getElementById("floating-speaker");
  if (speaker) {
    speaker.addEventListener("click", () => showAnnouncementPopup());
  }
});

</script>
