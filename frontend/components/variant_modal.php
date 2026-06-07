<!-- ===============================
      ðŸ’— Qii.shoppp å•†å“è§„æ ¼å¼¹çª—
================================ -->

<style>
#variantModal { position: fixed; inset: 0; display: none; align-items: flex-end; justify-content: center; background: rgba(48,35,43,.32) !important; z-index: 4000; }
#variantModal .modal-box { width: min(100%, 430px); max-height: min(92vh, 760px); overflow-y: auto; background: #fff7fb !important; border-radius: 24px 24px 0 0; padding: 12px 14px 18px; position: relative; border: 1px solid #f7cfe0 !important; box-shadow: 0 -16px 38px rgba(187,72,126,.22) !important; font-family: Arial, "Microsoft YaHei", sans-serif; animation: variantSheetUp .28s ease; }
#variantModal .modal-box::before { content: ""; display: block; width: 54px; height: 5px; margin: 0 auto 14px; border-radius: 999px; background: #ff9bc8; }
@keyframes variantSheetUp { from { opacity: 0; transform: translateY(26px); } to { opacity: 1; transform: translateY(0); } }
#variantModal .close-btn { position: absolute; top: 18px; right: 14px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; color: #9b7a8a; font-size: 25px; line-height: 1; cursor: pointer; }
#variantModal .close-btn:hover { color: #e43f88; }
.variant-product-head { display: flex; gap: 12px; padding: 0 2px 16px; border-bottom: 1px solid #f6dbe7; }
#variantModal #modalImg { width: 110px; height: 110px; border-radius: 12px; object-fit: cover; border: 1px solid #f3c4d8; flex: 0 0 auto; }
.variant-product-meta { min-width: 0; flex: 1; padding-right: 26px; }
#variantModal h3 { margin: 4px 0 8px; color: #e43f88; font-size: 14px; font-weight: 800; line-height: 1.3; word-break: break-word; }
.variant-product-code { margin-bottom: 7px; color: #9b8790; font-size: 11px; }
#variantModal .price-line { margin-bottom: 7px; color: #f5368d; font-size: 20px; font-weight: 900; }
#variantModal #modalStock { color: #8d7c85; font-size: 11px; }
.variant-like { margin-top: 8px; color: #f5a8c9; font-size: 32px; text-align: right; line-height: 1; }
.variant-section { padding: 14px 0 0; }
.variant-section-title { margin: 0 0 10px; color: #7f6873; font-size: 12px; font-weight: 800; }
.variant-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 8px; margin-top: 0; }
.variant-card { min-width: 0; min-height: 48px; background: #fffafd; border: 1px solid #f3d3e0; border-radius: 12px; padding: 5px 7px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; cursor: pointer; transition: .2s; position: relative; overflow: hidden; }
.variant-card:hover { background: #fff0f7; }
.variant-card img { display: none; width: 18px; height: 18px; border-radius: 50%; object-fit: cover; border: 0; }
.variant-name { width: 100%; color: #9b536f; font-size: 11px; font-weight: 700; line-height: 1.25; text-align: center; overflow-wrap: anywhere; }
.variant-stock { display: none; }
.variant-card.active { border-color: #ff77b4; background: #fff0f7; box-shadow: 0 0 0 1px #ff77b4 inset; }
.variant-card.active::after { content: "✓"; position: absolute; top: -7px; right: -6px; width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #f5368d; color: #fff; font-size: 11px; font-weight: 900; }
.variant-card.disabled { opacity: .45; cursor: not-allowed; background: #f8edf3; }
.variant-group-title { display: none !important; }
.variant-qty-row { display: grid; grid-template-columns: 36px 1fr 36px; align-items: center; height: 42px; border: 1px solid #f4d9e5; border-radius: 12px; background: #fffafd; }
.variant-qty-btn { border: 0; background: transparent; color: #f5368d; font-size: 22px; line-height: 1; cursor: pointer; }
#variantQty { border: 0; outline: 0; background: transparent; text-align: center; color: #7a5d6b; font-weight: 800; font-size: 14px; }
.variant-stock-note { margin-top: 8px; text-align: center; color: #b9879e; font-size: 11px; }
.variant-benefits { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-top: 14px; padding: 10px 8px; border-radius: 12px; background: #ffe9f3; }
.variant-benefit { text-align: center; color: #9b536f; font-size: 10px; line-height: 1.45; }
.variant-benefit strong { display: block; color: #d94b8a; font-size: 11px; }
.addToCartFinal { width: 100%; min-height: 48px; padding: 0 16px; background: linear-gradient(180deg,#ff62aa 0%,#f5368d 100%); color: #fff; border-radius: 999px; border: none; margin-top: 12px; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 8px 18px rgba(245,54,141,.28); }
.variant-pagination { margin-top: 12px; display: none; align-items: center; justify-content: center; gap: 12px; }
.variant-page-button { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #f4c7da; border-radius: 50%; background: #fff; color: #e43f88; font-size: 22px; line-height: 1; cursor: pointer; box-shadow: 0 5px 12px rgba(213,72,137,.1); }
.variant-page-button:disabled { opacity: .3; cursor: default; box-shadow: none; }
.variant-page-status { min-width: 68px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
.variant-page-dot { width: 7px; height: 7px; border-radius: 50%; background: #f3c6da; }
.variant-page-dot.active { width: 20px; border-radius: 999px; background: #f5368d; }

</style>

<!-- Variant bottom sheet -->
<div id="variantModal">
  <div class="modal-box">
    <div class="close-btn" onclick="closeVariantModal()">&times;</div>

    <div class="variant-product-head">
      <img id="modalImg" src="" alt="">
      <div class="variant-product-meta">
        <h3 id="modalName"></h3>
        <div class="variant-product-code">SKU: <span id="modalSku">-</span></div>
        <div class="price-line">RM <span id="modalPrice"></span></div>
        <div id="modalStock">Stock: -</div>
        <div class="variant-like">♡</div>
      </div>
    </div>

    <div class="variant-section">
      <div class="variant-section-title">🎀 选择规格</div>
      <div id="variantBox">Loading...</div>
    </div>

    <div id="variantPagination" class="variant-pagination">
      <button id="prevVariantPage" class="variant-page-button" type="button" aria-label="上一页">&#8249;</button>
      <span id="variantPageInfo" class="variant-page-status" aria-label="规格页码"></span>
      <button id="nextVariantPage" class="variant-page-button" type="button" aria-label="下一页">&#8250;</button>
    </div>

    <div class="variant-section">
      <div class="variant-section-title">🛒 数量 (Quantity)</div>
      <div class="variant-qty-row">
        <button type="button" class="variant-qty-btn" onclick="changeVariantQty(-1)">−</button>
        <input id="variantQty" type="number" value="1" min="1" inputmode="numeric">
        <button type="button" class="variant-qty-btn" onclick="changeVariantQty(1)">＋</button>
      </div>
      <div class="variant-stock-note">💗 最多可购买 <span id="variantMaxQty">-</span> 件</div>
    </div>

    <div class="variant-benefits">
      <div class="variant-benefit"><strong>🚚 西马满 RM60 / 东马满 RM80 免运费</strong>  </div>
      <div class="variant-benefit"><strong>✨ 100% 正品保证</strong></div>
      <div class="variant-benefit"><strong>💖 24小时以内 退换保障</strong></div>
    </div>

    <input type="hidden" id="selectedVariantId">
    <input type="hidden" id="selectedVariantName">
    <input type="hidden" id="selectedProductId">

    <button class="addToCartFinal" onclick="finalAddToCart(false)">🛍 加入购物袋</button>
  </div>
</div>

<script>
function qiiAssetPath(path) {
    path = (path || "").trim();
    if (!path) return "images/logo.png";
    if (/^(https?:)?\/\//.test(path)) return path;
    path = path.replace(/^\/+/, "");
    if (path.startsWith("uploads/") || path.startsWith("images/")) return path;
    return "uploads/" + path;
}

function setVariantMaxQty(stock) {
    const max = Math.max(1, parseInt(stock || 1, 10));
    const qty = document.getElementById("variantQty");
    document.getElementById("variantMaxQty").textContent = max;
    qty.max = max;
    qty.value = Math.min(Math.max(1, parseInt(qty.value || 1, 10)), max);
}

function changeVariantQty(delta) {
    const qty = document.getElementById("variantQty");
    const max = Math.max(1, parseInt(qty.max || 1, 10));
    const next = Math.min(max, Math.max(1, parseInt(qty.value || 1, 10) + delta));
    qty.value = next;
}

/* æ‰“å¼€å¼¹çª— */
function openVariantModal(p) {
    const modal = document.getElementById("variantModal");

    document.getElementById("modalName").textContent = p.name;
    document.getElementById("modalSku").textContent = p.sku || "-";
    document.getElementById("modalPrice").textContent = parseFloat(p.price).toFixed(2);
    document.getElementById("modalImg").src = qiiAssetPath(p.img);
    document.getElementById("variantQty").value = 1;

    document.getElementById("selectedProductId").value = p.id;
    document.getElementById("selectedVariantId").value = "";
    document.getElementById("selectedVariantName").value = "";
    document.getElementById("modalStock").textContent =
        p.stock ? ("Stock: " + p.stock) : "Stock: -";
    setVariantMaxQty(p.stock || 1);

    if (String(p.has_variant) === "0") {
        document.getElementById("selectedVariantId").value = "0";
        document.getElementById("selectedVariantName").value = "";
        document.getElementById("variantBox").innerHTML =
            '<div class="no-variant" data-novariant="1" style="padding:12px; font-size:13px; color:#C94B82;">无需选择规格</div>';
        document.getElementById("variantPagination").style.display = "none";
        modal.style.display = "flex";
        return;
    }

    fetch("api/variant_box_front.php?product_id=" + p.id)
        .then(res => res.text())
        .then(html => {
            document.getElementById("variantBox").innerHTML = html;
            const noVariantEl = document.querySelector("#variantBox .no-variant");
            if (noVariantEl) {
                document.getElementById("selectedVariantId").value = "0";
                document.getElementById("selectedVariantName").value = "";
                document.getElementById("modalImg").src = qiiAssetPath(noVariantEl.dataset.img);
                document.getElementById("modalPrice").textContent = noVariantEl.dataset.price;
                document.getElementById("modalStock").textContent = "Stock: " + noVariantEl.dataset.stock;
                setVariantMaxQty(noVariantEl.dataset.stock || 1);
                document.getElementById("variantPagination").style.display = "none";
                return;
            }
            setTimeout(() => {
                autoSelectFirstCard();
                setupVariantPagination();
            }, 50);
        });

    modal.style.display = "flex";
}
function closeVariantModal() {
    document.getElementById("variantModal").style.display = "none";
}

/* åŠ å…¥è´­ç‰©è½¦ */
function finalAddToCart(goCheckout = false) {
    let vid = document.getElementById("selectedVariantId").value;
    let vname = document.getElementById("selectedVariantName").value;
    const price = document.getElementById("modalPrice").textContent;
    const pname = document.getElementById("modalName").textContent;
    const pid = document.getElementById("selectedProductId").value;

    // å…è®¸æ— è§„æ ¼å•†å“ï¼šè‹¥å­˜åœ¨ .no-variant ä¸”æœªå¡« idï¼Œåˆ™è®¾ä¸º 0
    const noVariantEl = document.querySelector("#variantBox .no-variant");
    if (noVariantEl && (vid === "" || vid === undefined)) {
        vid = "0";
        vname = vname || pname;
    }

    // æœ‰è§„æ ¼å•†å“æ‰è¦æ±‚é€‰æ‹©
    if (!noVariantEl && vid === "") {
        if (typeof qiiToast === "function") {
            qiiToast("Please choose a variant first.");
        } else {
            alert("Please choose a variant first.");
        }
        return;
    }
    if (!pid) {
        alert("Product ID is missing.");
        return;
    }

    const form = new FormData();
    form.append("id", pid);
    form.append("variant_id", vid);
    form.append("variant_name", vname);
    form.append("name", pname);
    form.append("price", price);
    form.append("qty", Math.max(1, parseInt(document.getElementById("variantQty").value || 1, 10)));

    fetch("api/add_to_cart.php", {
        method: "POST",
        headers: qiiCsrfHeaders(),
        body: form
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof getCartAndUpdate === "function") getCartAndUpdate();
            if (goCheckout) {
                closeVariantModal();
                window.location.href = "checkout.php";
                return;
            }
            document.getElementById("variantQty").value = 1;
            if (typeof qiiToast === "function") {
                qiiToast("Added to cart. You can keep choosing.");
            } else {
                alert("Added to cart. You can keep choosing.");
            }
        } else {
            const msg = data.message || "Add failed. Please try again.";
            if (typeof qiiToast === "function") {
                if (msg.includes("库存") || msg.includes("stock")) {
                    qiiToast("This variant has reached the stock limit.");
                } else {
                    qiiToast(msg);
                }
            } else {
                alert(msg);
            }
        }
    });
}
// ===================================================
// ðŸ©· è®©è§„æ ¼å¡ç‰‡ç‚¹å‡»åŽèƒ½æ›´æ–° modal å†…å®¹ï¼ˆæœ€å…³é”®ï¼‰
// ===================================================

// ç»™ variant_box_front.php æ³¨å…¥äº‹ä»¶ï¼ˆåŠ¨æ€å†…å®¹éœ€è¦äº‹ä»¶ä»£ç†ï¼‰
document.addEventListener("click", function(e){
    let card = e.target.closest(".variant-card");
    if(!card) return;

    // ä¸æ˜¯å¼¹çª—å†…çš„ variant card â†’ æ— è§†
    if(!document.getElementById("variantModal").contains(card)) return;

    // ç§»é™¤æ—§ active
    document.querySelectorAll("#variantModal .variant-card")
        .forEach(c => c.classList.remove("active"));

    // è®¾ç½® active
    card.classList.add("active");

    // æ›´æ–°éšè—å­—æ®µ
    document.getElementById("selectedVariantId").value = card.dataset.vid;
    document.getElementById("selectedVariantName").value = card.dataset.vname;

    // æ›´æ–° modal å›¾ç‰‡
    document.getElementById("modalImg").src = qiiAssetPath(card.dataset.vimg);

    // æ›´æ–° modal Price
    document.getElementById("modalPrice").textContent =
        parseFloat(card.dataset.vprice).toFixed(2);

    // æ›´æ–°åº“å­˜
    document.getElementById("modalStock").textContent =
        "Stock: " + card.dataset.vstock;
    setVariantMaxQty(card.dataset.vstock || 1);
});


// ===================================================
// ðŸ©· è‡ªåŠ¨é€‰ä¸­ç¬¬ä¸€ä¸ªè§„æ ¼ï¼ˆç¬¬ä¸€æ¬¡æ‰“å¼€ modal æ—¶ï¼‰
// ===================================================
function autoSelectFirstCard(){
    let first = document.querySelector("#variantModal .variant-card");
    if(first){
        first.classList.add("active");

        document.getElementById("selectedVariantId").value = first.dataset.vid;
        document.getElementById("selectedVariantName").value = first.dataset.vname;
        document.getElementById("modalImg").src = qiiAssetPath(first.dataset.vimg);
        document.getElementById("modalPrice").textContent =
            parseFloat(first.dataset.vprice).toFixed(2);
        document.getElementById("modalStock").textContent =
            "Stock: " + first.dataset.vstock;
        setVariantMaxQty(first.dataset.vstock || 1);
    }
}

// æ¯æ¬¡ modal è½½å…¥è§„æ ¼å®ŒæˆåŽè‡ªåŠ¨é€‰ä¸­ç¬¬ä¸€ä¸ª
document.addEventListener("DOMContentLoaded", () => {
    // åœ¨ openVariantModal çš„ fetch load å®Œä¹‹åŽ 0.1 ç§’è¿è¡Œ
    setTimeout(autoSelectFirstCard, 150);
});
/* ==================================================
   ðŸŒ¸ Variant åˆ†é¡µé€»è¾‘ï¼ˆæ¯é¡µ 5 æ¡ï¼‰
================================================== */
let variantList = [];
let variantPage = 1;
const variantPerPage = 6;

/* åœ¨è½½å…¥ variant_box_front.php åŽè§¦å‘ */
function setupVariantPagination() {
    const cards = document.querySelectorAll("#variantModal .variant-card");
    variantList = Array.from(cards);
    variantPage = 1;

    if (variantList.length <= variantPerPage) {
        document.getElementById("variantPagination").style.display = "none";
        return;
    }

    document.getElementById("variantPagination").style.display = "flex";
    renderVariantPage();
}

function renderVariantPage() {
    let start = (variantPage - 1) * variantPerPage;
    let end = start + variantPerPage;

    variantList.forEach((c, i) => {
        c.style.display = (i >= start && i < end) ? "flex" : "none";
    });

    let totalPage = Math.ceil(variantList.length / variantPerPage);
    document.getElementById("variantPageInfo").innerHTML = Array.from(
        { length: totalPage },
        (_, index) => '<span class="variant-page-dot' + (index + 1 === variantPage ? ' active' : '') + '"></span>'
    ).join("");

    document.getElementById("prevVariantPage").disabled = variantPage === 1;
    document.getElementById("nextVariantPage").disabled = variantPage === totalPage;
}

/* æŒ‰é’®ç‚¹å‡» */
document.getElementById("prevVariantPage").onclick = () => {
    if (variantPage > 1) {
        variantPage--;
        renderVariantPage();
    }
};

document.getElementById("nextVariantPage").onclick = () => {
    let totalPage = Math.ceil(variantList.length / variantPerPage);
    if (variantPage < totalPage) {
        variantPage++;
        renderVariantPage();
    }
};

</script>
