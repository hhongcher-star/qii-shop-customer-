<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/customers.php';
qii_start_session();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<footer class="site-footer">
  <div class="floating-buttons">
    <button class="floating-cart" id="floating-cart">
      &#128717;<span class="cart-count"><?= isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0 ?></span>
    </button>
    <button class="floating-speaker" id="floating-speaker">&#128227;</button>
  </div>

  <div class="footer-info">
    <p>&copy; 2025 qii.shoppp | All Rights Reserved</p>
  </div>
  
</footer>




<!-- Cart modal -->
<div id="cartModal" class="modal">
  <div class="modal-card">
    <span class="close">&times;</span>
    <img src="images/27.png" alt="Qii cart helper" class="qii-on-bag">
    <h2>Qii &#36141;&#29289;&#34955;</h2>
    <div class="cart-content"><!-- JS render --></div>
    <div class="cart-footer">
      <button id="clearCartBtn">&#28165;&#31354;</button>
      <button id="checkoutBtn"><strong>&#21435;&#32467;&#36134;</strong></button>
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
  bottom: 10px;
  right: 14px; 
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

/* ðŸ“± æ‰‹æœºæ¨¡å¼æŒ‰é’®å¾€å·¦ä¸€ç‚¹ */
@media (max-width: 600px) {
  .floating-buttons {
    right: 12px !important;
    bottom: 12px !important;
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

/* è´­ç‰©è¢‹å¼¹çª— */
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
  max-height: min(86vh, 720px);
  max-height: min(86dvh, 720px);
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
}
@keyframes fadeIn { from {opacity:0;transform:translate(-50%,-55%);} to {opacity:1;transform:translate(-50%,-50%);} }
.close {
  position: absolute; right: 15px; top: 10px;
  font-size: 20px; cursor: pointer; color: #c94b82;
}
.close:hover { color: #e5679c; }
.modal-card h2 { text-align: center; color: #e5679c; }

.cart-content {
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
  padding-right: 4px;
}
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

/* åœ°åŒºé€‰æ‹©ï¼ˆè¥¿é©¬ / ä¸œé©¬ï¼‰ */
.region-select {
  display:flex;
  align-items:center;
  justify-content:flex-end;
  flex-wrap:wrap;
  gap:10px;
  text-align:right;
  margin-top: 10px;
  color:#e5679c;
  font-size:14px;
}
.region-select label {
  display:inline-flex !important;
  align-items:center;
  gap:6px;
  width:auto !important;
  margin:0 !important;
  font-weight:700;
  white-space:nowrap;
}
.region-select input[type="radio"] {
  appearance:auto !important;
  -webkit-appearance:radio !important;
  width:16px !important;
  min-width:16px !important;
  max-width:16px !important;
  height:16px !important;
  min-height:16px !important;
  margin:0 !important;
  padding:0 !important;
  border:0 !important;
  box-shadow:none !important;
  accent-color:#e5679c;
}

.cart-footer {
  flex: 0 0 auto;
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

/* Qii æµ®åŠ¨åŠ¨ç”»ï¼ˆä¸Žå–‡å­/è§„åˆ™å…±ç”¨ï¼‰ */
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
  .modal {
    height: 100dvh;
  }
  .modal-card {
    width: calc(100% - 24px);
    max-width: 420px;
    max-height: calc(100dvh - 112px - env(safe-area-inset-bottom, 0px));
    padding: 15px 14px calc(15px + env(safe-area-inset-bottom, 0px));
    border-radius: 18px;
  }
  .qii-on-bag {
    width: 110px;
    top: -60px;
  }
}

/* è§„åˆ™/å…¬å‘Šå¼¹çª—é€šç”¨æ ·å¼ */
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

/* è§„åˆ™å¼¹çª—å¢žå¼ºæ ·å¼ä¸ŽåŠ¨ç”» */
.fade-in { animation: rulesFadeIn 0.45s ease; }
@keyframes rulesFadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.rules-text { font-size: 14px; line-height: 1.7; color: #b55678; margin-top: 10px; white-space: normal; }
.qii-float { top: -85px; animation: qiiFlyIn 1s ease-out, qiiFloat 2.5s ease-in-out infinite 1s; pointer-events: none; }
@keyframes qiiFlyIn { 0% { transform: translate(-50%, -40px); opacity: 0; } 100% { transform: translate(-50%, 0); opacity: 1; } }
#closeRules { padding: 8px 22px; font-size: 14px; margin-top: 18px; transition: 0.25s; }

/* ðŸŒ¸ å›¾2å…¬å‘Šå¤§å¡ç‰‡å°ºå¯¸ä¼˜åŒ– */
.big-rules {
  max-width: 500px;
  padding: 30px 25px;
}

/* æ‰‹æœºä¼˜åŒ– */
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

/* ç²‰è‰²æ ‡é¢˜ */
.pink-title {
  color: #e5679c;
  font-size: 24px;
  margin-bottom: 10px;
}

/* ç²‰è‰²å†…å®¹ */
.text-block p {
  color: #c94b82;
  font-size: 15px;
  line-height: 1.7;
  margin: 10px 0;
}

/* åˆ†éš”çº¿ */
.text-block hr {
  border: none;
  border-top: 1px solid #f6bdd9;
  margin: 15px 0;
}

/* ç²‰è‰²æŒ‰é’®ï¼ˆæˆ‘å·²é˜…è¯»å®Œæ¯•ï¼‰ */
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

/* æ‰‹æœºç«¯ä¼˜åŒ– */
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

/* ðŸŽ€ å…¬å‘Šæ ‡é¢˜æ›´å¯çˆ± */
.popup-title {
  font-size: 24px;
  color: #e5679c;
  font-weight: 700;
  margin-bottom: 12px;
  font-family: "Patrick Hand", cursive;
}

/* ðŸŽ€ å†…å®¹æ–‡å­—æ›´èˆ’æœ */
.popup-text p {
  color: #c94b82;
  font-size: 15px;
  line-height: 1.7;
  margin: 8px 0;
  font-family: "Patrick Hand", cursive;
}

/* é‡ç‚¹æ–‡å­—æ©˜è‰² */
.popup-text .highlight {
  color: #e58d6f;
  font-weight: 600;
}

/* çº¢è‰²è­¦å‘Šæ›´æ¸…æ¥šä½†æ˜¯å¯çˆ± */
.popup-text .warn {
  color: #e05a86;
  font-weight: bold;
}

/* åˆ†éš”çº¿æ›´æ·¡æ›´å°‘å¥³ */
.rules-content hr {
  border: none;
  border-top: 1px dashed #f6bdd9;
  margin: 12px 0;
}

/* ðŸŽ€ è¶…æ¼‚äº®çš„â€œæˆ‘å·²é˜…è¯»å®Œæ¯•â€æŒ‰é’® */
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

/* å¡ç‰‡æ›´ç«‹ä½“ã€æ›´å°‘å¥³ */
.cute-popup {
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 10px 35px rgba(230,103,156,0.25);
}

/* æ‰‹æœºä¼˜åŒ– */
@media (max-width: 600px) {
  .popup-title { font-size: 20px; }
  .popup-text p { font-size: 14px; }
  .pink-confirm-btn { width: 100%; font-size: 15px; }
  .qii-float { width: 110px; top: -70px; }
}



</style>

<?php
require_once __DIR__ . '/../../app/content_settings.php';
$announcementEditableContent = [
  'announcement_title' => qii_sanitize_rich_text(qii_content($pdo, 'announcement_title', '💕 请阅读完毕~')),
  'announcement_intro' => qii_sanitize_rich_text(qii_content($pdo, 'announcement_intro', '本店以直播间过款为主，有卖各种可爱商品💕')),
  'announcement_quality' => qii_sanitize_rich_text(qii_content($pdo, 'announcement_quality', '价格优惠，质量不错')),
  'announcement_storage' => qii_sanitize_rich_text(qii_content($pdo, 'announcement_storage', '💛 可存单（需付款即可）付款后存多久都没问题')),
  'announcement_shipping' => qii_sanitize_rich_text(qii_content($pdo, 'announcement_shipping', '西马10满65 🍞 东马15满80 🍞')),
  'announcement_dispatch' => qii_sanitize_rich_text(qii_content($pdo, 'announcement_dispatch', '发货时间：1-3-6（有发货都会先在群通知）')),
  'announcement_warning' => qii_sanitize_rich_text(qii_content($pdo, 'announcement_warning', '未满18岁（需父母同意购买✅）<br>如发现逃单一律公开＋拉黑‼')),
  'announcement_button' => qii_sanitize_rich_text(qii_content($pdo, 'announcement_button', '我已阅读完毕')),
];
?>
<script>
const qiiAnnouncementEditableContent = <?= json_encode($announcementEditableContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
function qiiCsrfHeaders(extra = {}) {
  const token = document.querySelector('meta[name="qii-csrf-token"]')?.content || "";
  return token ? { ...extra, "X-QII-CSRF-Token": token } : extra;
}

// ===============
// ðŸŒ¸ å…¬å‘Šå¼¹çª—å‡½æ•°
// ===============
function showAnnouncementPopup() {
  const popup = document.createElement("div");
  popup.classList.add("rules-popup");

  popup.innerHTML = `
    <div class="rules-content big-rules cute-popup fade-in">

      <img src="images/27.png" class="qii-float" alt="Qii Girl">

      <h2 class="popup-title">&#128149; &#35831;&#38405;&#35835;&#23436;&#27605;~</h2>

      <div class="popup-text">
        <p>&#26412;&#24215;&#20197;&#30452;&#25773;&#38388;&#36807;&#27454;&#20026;&#20027;&#65292;&#26377;&#21334;&#21508;&#31181;&#21487;&#29233;&#21830;&#21697;&#128149;</p>
        <p>&#20215;&#26684;&#20248;&#24800;&#65292;&#36136;&#37327;&#19981;&#38169;</p>

        <p class="highlight">&#128155; &#21487;&#23384;&#21333;&#65288;&#38656;&#20184;&#27454;&#21363;&#21487;&#65289;&#20184;&#27454;&#21518;&#23384;&#22810;&#20037;&#37117;&#27809;&#38382;&#39064;</p>

        <hr>

        <p>&#35199;&#39532;10&#28385;65 &#127838; &#19996;&#39532;15&#28385;80 &#127838;</p>
        <p>&#21457;&#36135;&#26102;&#38388;&#65306;1-3-6&#65288;&#26377;&#21457;&#36135;&#37117;&#20250;&#20808;&#22312;&#32676;&#36890;&#30693;&#65289;</p>

        <hr>

        <p class="warn">未满18岁(需父母同意购买✅）<br>
如发现逃单一律公开➕拉黑‼️</p>
      </div>

      <button id="closeSpeakerPopup" class="pink-confirm-btn">&#25105;&#24050;&#38405;&#35835;&#23436;&#27605;</button>
    </div>
  `;

  document.body.appendChild(popup);

  const announcementBindings = [
    [popup.querySelector(".popup-title"), "announcement_title"],
    [popup.querySelectorAll(".popup-text p")[0], "announcement_intro"],
    [popup.querySelectorAll(".popup-text p")[1], "announcement_quality"],
    [popup.querySelector(".popup-text .highlight"), "announcement_storage"],
    [popup.querySelectorAll(".popup-text p")[3], "announcement_shipping"],
    [popup.querySelectorAll(".popup-text p")[4], "announcement_dispatch"],
    [popup.querySelector(".popup-text .warn"), "announcement_warning"],
    [popup.querySelector("#closeSpeakerPopup"), "announcement_button"]
  ];
  announcementBindings.forEach(([element, key]) => {
    if (!element) return;
    element.dataset.contentKey = key;
    element.innerHTML = qiiAnnouncementEditableContent[key];
  });

  document.getElementById("closeSpeakerPopup").onclick = () => {
    popup.remove();
    document.dispatchEvent(new CustomEvent("qii:announcement-closed"));
  };
}

// èŽ·å–è´­ç‰©è¢‹å†…å®¹å¹¶æ›´æ–°
function getCartAndUpdate() {
  return fetch("api/add_to_cart.php?mode=getCart")
    .then(res => res.json())
    .then(data => { if (data.success) updateCartUI(data); })
    .catch(err => console.error("getCart failed", err));
}

function qiiCartAssetPath(path) {
  path = (path || "").trim();
  if (!path) return "images/logo.png";
  if (/^(https?:)?\/\//.test(path)) return path;
  path = path.replace(/^\/+/, "");
  if (path.startsWith("uploads/") || path.startsWith("images/")) return path;
  return "uploads/" + path;
}

function qiiFixText(value) {
  let text = String(value ?? "");

  try {
    if (/[ÃÂâ]/.test(text)) {
      text = decodeURIComponent(escape(text));
    }
  } catch (e) {}

  return text;
}

function qiiText(value) {
  return qiiFixText(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// æ›´æ–°è´­ç‰©è¢‹UI
function updateCartUI(data) {
  const countEls = document.querySelectorAll(".cart-count");
  const cartContent = document.querySelector(".cart-content");
  countEls.forEach(el => { el.textContent = data.count ?? 0; });

  if (!data.cart || data.cart.length === 0) {
    cartContent.innerHTML = '<p style="text-align:center;color:#aaa;">&#36141;&#29289;&#34955;&#31354;&#31354;&#30340;~</p>';
    return;
  }

  // å½“å‰åœ°åŒºï¼ˆé»˜è®¤è¥¿é©¬ï¼‰
  const currentRegion = document.querySelector('input[name="region"]:checked')?.value || 'west';

  let html = '<ul>';
  let total = 0;
  data.cart.forEach(item => {
    const price = parseFloat(item.price);
    const qty = parseInt(item.qty, 10);
    const subtotal = price * qty;
    const productId = item.product_id || item.id || "";
    const variantId = item.variant_id || 0;
    const productName = qiiFixText(item.product_name || item.name || "");
    const variantNameRaw = qiiFixText(item.variant_name || "");
    const variantName = variantNameRaw && variantNameRaw !== "??" ? variantNameRaw : "";
    const displayName = variantName ? `${productName} (${variantName})` : productName;
    const imagePath = qiiCartAssetPath(item.img);
    total += subtotal;
    html += `
      <li class="cart-item" data-product-id="${qiiText(productId)}" data-variant-id="${qiiText(variantId)}">
        <img src="${qiiText(imagePath)}" class="cart-thumb" alt="${qiiText(displayName)}">
        <div class="cart-info">
          <div class="cart-name">${qiiText(displayName)}</div>
          <div class="cart-qty">
            <button class="qty-btn dec" data-id="${qiiText(productId)}" data-variant-id="${qiiText(variantId)}">-</button>
            <span class="qty-value">${qty}</span>
            <button class="qty-btn inc" data-id="${qiiText(productId)}" data-variant-id="${qiiText(variantId)}">+</button>
          </div>
          <div class="cart-meta">&#21333;&#20215;: RM${price.toFixed(2)} &#23567;&#35745;: RM${subtotal.toFixed(2)}</div>
        </div>
      </li>`;
  });

  // åœ°åŒºé€‰æ‹©å™¨ï¼ˆæ’å…¥åœ¨å°è®¡åŒºåŸŸä¹‹å‰ï¼‰
  html += `</ul>
    <div class="region-select">
      <label>
        <input type="radio" name="region" value="west" ${currentRegion === 'west' ? 'checked' : ''}>
        西马 RM10
      </label>
      <label style="margin-left:15px;">
        <input type="radio" name="region" value="east" ${currentRegion === 'east' ? 'checked' : ''}>
        东马 RM15
      </label>
      <label style="margin-left:15px;">
        <input type="radio" name="region" value="hold" ${currentRegion === 'hold' ? 'checked' : ''}>
        存单 RM0
      </label>
    </div>`;

  // æŒ‰åœ°åŒºè®¡ç®—è¿è´¹ä¸Žå…é‚®é—¨æ§›
  let shipping_cost = 0;
  if (currentRegion === 'hold') {
    shipping_cost = 0;
  } else if (currentRegion === 'west') {
    shipping_cost = total >= 65 ? 0 : 10;
  } else {
    shipping_cost = total >= 80 ? 0 : 15;
  }
  const grand_total = total + shipping_cost;

  html += `
    <div class="cart-summary">
      <div>&#23567;&#35745;: RM${total.toFixed(2)}</div>
      <div>&#36816;&#36153;: RM${shipping_cost.toFixed(2)}</div>
      <div><strong>&#21512;&#35745;: RM${grand_total.toFixed(2)}</strong></div>
    </div>`;
  cartContent.innerHTML = html;
}

// åˆå§‹åŒ–äº¤äº’
document.addEventListener("DOMContentLoaded", () => {
  // ===============
  // ðŸŒ¸ æ¯æ¬¡è¿›å…¥ç½‘ç«™è‡ªåŠ¨å¼¹å‡ºå…¬å‘Š
  // ===============
  showAnnouncementPopup();
  
  getCartAndUpdate();

  const fab = document.getElementById("floating-cart");
  const modal = document.getElementById("cartModal");
  const close = document.querySelector(".close");
  fab.onclick = () => modal.style.display = "block";
  close.onclick = () => modal.style.display = "none";
  window.onclick = (e) => { if (e.target === modal) modal.style.display = "none"; };

  // æ•°é‡å¢žå‡
  document.querySelector(".cart-content").addEventListener("click", e => {
      const t = e.target;
      if (t.classList.contains("dec") || t.classList.contains("inc")) {
        const fd = new FormData();
        fd.append("id", t.dataset.id);
        fd.append("variant_id", t.dataset.variantId || 0);
        const url = t.classList.contains("dec") ? "api/add_to_cart.php?mode=removeOne" : "api/add_to_cart.php?mode=add";
        fetch(url, { method:"POST", body: fd, headers: qiiCsrfHeaders() })
        .then(r => r.json())
        .then(d => updateCartUI(d))
        .catch(() => getCartAndUpdate());
    }
  });

  // æ¸…ç©º
  const clearBtn = document.getElementById("clearCartBtn");
  if (clearBtn) clearBtn.addEventListener("click", () => {
    fetch("api/add_to_cart.php?mode=clear", { method: "POST", headers: qiiCsrfHeaders() })
      .then(r => r.json())
      .then(d => {
        updateCartUI(d);
      });
  });

  // åŽ»ç»“è´¦
  const checkoutBtn = document.getElementById("checkoutBtn");
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", () => {

      const region = document.querySelector('input[name="region"]:checked')?.value;

      if (!region) {
        alert("请选择邮费方式（西马 / 东马 / 存单）后再结账");
        return;
      }

      const fd = new FormData();
      fd.append("region", region);

      fetch("api/checkout.php", {
        method: "POST",
        headers: qiiCsrfHeaders(),
        body: fd
      })
        .then((r) => r.json())
        .then((d) => {
          if (d.success && d.redirect) {
            window.location.href = d.redirect;
          } else {
            alert(d.msg || "Checkout failed. Please try again.");
          }
        })
        .catch(() => console.error("Checkout error"));
    });
  }

  // ç›‘å¬åœ°åŒºåˆ‡æ¢ï¼Œè‡ªåŠ¨åˆ·æ–°å°è®¡ä¸Žè¿è´¹
  document.addEventListener("change", (e) => {
    if (e.target && e.target.name === "region") {
      getCartAndUpdate();
    }
  });

  // ðŸ“£ å–‡å­æŒ‰é’®å†æ¬¡æ‰“å¼€å…¬å‘Š
  const speaker = document.getElementById("floating-speaker");
  if (speaker) {
    speaker.addEventListener("click", () => showAnnouncementPopup());
  }
});

</script>
