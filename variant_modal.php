<!-- ===============================
      💗 Qii.shoppp 商品规格弹窗
================================ -->

<style>
/* 整个遮罩层 */
/* ===============================
      💗 Qii.shoppp 商品规格弹窗（粉色主题）
================================ */

/* 遮罩层 */
#variantModal {
  position: fixed;
  inset: 0;
  display: none;
  align-items: center;
  justify-content: center;
  background: rgba(255,195,215,0.55); /* 粉色透明遮罩 */
  z-index: 9999;
}

/* Modal 主体 */
#variantModal .modal-box {
  width: 90%;
  max-width: 380px;
  background: linear-gradient(180deg,#FFF0F7 0%, #FFE1EE 100%);
  border-radius: 22px;
  padding: 24px;
  text-align: center;
  position: relative;
  animation: fadeInUp .35s ease;
  border: 2px solid #F8C9DC;
  box-shadow: 0 8px 25px rgba(255,140,180,0.25);
}

/* 商品主图 */
#variantModal #modalImg {
  width: 140px;
  height: 140px;
  border-radius: 18px;
  object-fit: cover;
  margin-bottom: 12px;
  border: 2px solid #f6bdd9;
  box-shadow: 0 4px 10px rgba(240,150,180,0.25);
}

/* 标题 */
#variantModal h3 {
  font-size: 18px;
  margin: 8px 0 5px;
  color: #E44B87;
  font-weight: 700;
}

/* 价格 */
#variantModal .price-line {
  font-size: 20px;
  font-weight: bold;
  margin-bottom: 6px;
  color: #d94b8a;
}

/* 库存 */
#variantModal #modalStock {
  font-size: 14px;
  color: #b36a88;
  margin-bottom: 14px;
}

/* 关闭按钮 */
#variantModal .close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  font-size: 28px;
  cursor: pointer;
  color: #E5679C;
  transition: 0.2s;
}
#variantModal .close-btn:hover {
  color: #C94B82;
  transform: scale(1.1);
}

/* 加入购物车按键（粉色版本） */
.addToCartFinal {
  width: 100%;
  padding: 14px 0;
  background: linear-gradient(180deg,#FFB5D3,#FF95C2);
  color: #fff;
  border-radius: 16px;
  border: none;
  margin-top: 18px;
  font-size: 16px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(255,120,160,0.35);
  transition: 0.25s;
  font-family: "Patrick Hand", cursive;
}
.addToCartFinal:hover {
  background: linear-gradient(180deg,#FF95C2,#FF7FB2);
  transform: scale(1.05);
}

/* 动画 */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ===============================
      🌸 规格卡片（粉色 kawaii 风）
================================ */
.variant-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-top: 10px;
}

.variant-card {
  background: #FFEFF7;
  border: 2px solid #F6C7D8;
  border-radius: 16px;
  padding: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  transition: 0.25s;
  box-shadow: 0 3px 6px rgba(255,160,190,0.2);
}

.variant-card:hover {
  background: #FFE3F1;
  transform: scale(1.04);
}

.variant-card img {
  width: 45px;
  height: 45px;
  border-radius: 10px;
  object-fit: cover;
  border: 2px solid #f6bdd9;
}

/* 规格文字 */
.variant-name {
  font-size: 14px;
  color: #C94B82;
  font-weight: 600;
}

.variant-stock {
  font-size: 12px;
  color: #A87891;
}

/* 选中状态 */
.variant-card.active {
  border-color: #FF80B3;
  background: #FFE4EF;
  box-shadow: 0 0 0 3px rgba(255,128,179,0.35);
  transform: scale(1.05);
}

/* 没库存状态 */
.variant-card.disabled {
  opacity: 0.45;
  cursor: not-allowed;
  background: #F9E0E8;
}
.variant-group-title {
    display: none !important;
}

</style>

<!-- 弹窗 HTML -->
<div id="variantModal">
  <div class="modal-box">

    <div class="close-btn" onclick="closeVariantModal()">×</div>

    <img id="modalImg" src="">

    <h3 id="modalName"></h3>

    <div class="price-line">RM <span id="modalPrice"></span></div>

    <div id="modalStock">库存：-</div>

    <!-- 动态载入规格内容 -->
    <div id="variantBox">载入中...</div>

    <!-- 🌸 分页按钮 -->
    <div id="variantPagination" style="margin-top:15px; display:none;">
        <button id="prevVariantPage" 
            style="padding:6px 14px; border-radius:10px; border:none; background:#FFC1D8; color:#fff; margin-right:10px;">
            上一页
        </button>

        <span id="variantPageInfo" style="color:#C94B82; font-weight:600;"></span>

        <button id="nextVariantPage" 
            style="padding:6px 14px; border-radius:10px; border:none; background:#FF9EC7; color:#fff; margin-left:10px;">
            下一页
        </button>
    </div>

    <!-- 隐藏的选中数据 -->
    <input type="hidden" id="selectedVariantId">
    <input type="hidden" id="selectedVariantName">
    <input type="hidden" id="selectedProductId">

    <button class="addToCartFinal" onclick="finalAddToCart()">加入购物车</button>

  </div>
</div>

<script>
/* 打开弹窗 */
function openVariantModal(p) {
    const modal = document.getElementById("variantModal");

    document.getElementById("modalName").textContent = p.name;
    document.getElementById("modalPrice").textContent = parseFloat(p.price).toFixed(2);

    // ⭐ 自动判断前端图片路径（补 uploads/）
    let imgPath = (p.img && (p.img.includes("uploads/") || p.img.includes("/")))
        ? p.img
        : "uploads/" + p.img;
    document.getElementById("modalImg").src = imgPath;

    document.getElementById("selectedProductId").value = p.id;
    document.getElementById("selectedVariantId").value = "";
    document.getElementById("selectedVariantName").value = "";
    document.getElementById("modalStock").textContent =
        p.stock ? ("库存：" + p.stock) : "库存：-";

    // 载入规格
    fetch("variant_box_front.php?product_id=" + p.id)
        .then(res => res.text())
        .then(html => {
            document.getElementById("variantBox").innerHTML = html;
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

/* 加入购物车 */
function finalAddToCart() {
    let vid = document.getElementById("selectedVariantId").value;
    let vname = document.getElementById("selectedVariantName").value;
    const price = document.getElementById("modalPrice").textContent;
    const pname = document.getElementById("modalName").textContent;
    const pid = document.getElementById("selectedProductId").value;

    // 允许无规格商品：若存在 .no-variant 且未填 id，则设为 0
    const noVariantEl = document.querySelector("#variantBox .no-variant");
    if (noVariantEl && (vid === "" || vid === undefined)) {
        vid = "0";
        vname = vname || pname;
    }

    // 有规格商品才要求选择
    if (!noVariantEl && vid === "") {
        if (typeof qiiToast === "function") {
            qiiToast("请先选择规格哦～💗");
        } else {
            alert("请先选择规格");
        }
        return;
    }
    if (!pid) {
        alert("商品 ID 缺失");
        return;
    }

    const form = new FormData();
    form.append("id", pid);
    form.append("variant_id", vid);
    form.append("variant_name", vname);
    form.append("name", pname);
    form.append("price", price);
    form.append("qty", 1);

    fetch("add_to_cart.php", {
        method: "POST",
        body: form
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeVariantModal();
            if (typeof getCartAndUpdate === "function") getCartAndUpdate();
            if (typeof qiiToast === "function") {
                qiiToast("已加入购物车");  // 🌸 粉色提示
            } else {
                alert("已加入购物车");
            }
        } else {
            const msg = data.message || "加入失败，请稍后再试";
            if (typeof qiiToast === "function") {
                if (msg.includes("库存")) {
                    qiiToast("💖 该规格库存已达上限");
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
// 🩷 让规格卡片点击后能更新 modal 内容（最关键）
// ===================================================

// 给 variant_box_front.php 注入事件（动态内容需要事件代理）
document.addEventListener("click", function(e){
    let card = e.target.closest(".variant-card");
    if(!card) return;

    // 不是弹窗内的 variant card → 无视
    if(!document.getElementById("variantModal").contains(card)) return;

    // 移除旧 active
    document.querySelectorAll("#variantModal .variant-card")
        .forEach(c => c.classList.remove("active"));

    // 设置 active
    card.classList.add("active");

    // 更新隐藏字段
    document.getElementById("selectedVariantId").value = card.dataset.vid;
    document.getElementById("selectedVariantName").value = card.dataset.vname;

    // 更新 modal 图片
    document.getElementById("modalImg").src = card.dataset.vimg;

    // 更新 modal Price
    document.getElementById("modalPrice").textContent =
        parseFloat(card.dataset.vprice).toFixed(2);

    // 更新库存
    document.getElementById("modalStock").textContent =
        "库存：" + card.dataset.vstock;
});


// ===================================================
// 🩷 自动选中第一个规格（第一次打开 modal 时）
// ===================================================
function autoSelectFirstCard(){
    let first = document.querySelector("#variantModal .variant-card");
    if(first){
        first.classList.add("active");

        document.getElementById("selectedVariantId").value = first.dataset.vid;
        document.getElementById("selectedVariantName").value = first.dataset.vname;
        document.getElementById("modalImg").src = first.dataset.vimg;
        document.getElementById("modalPrice").textContent =
            parseFloat(first.dataset.vprice).toFixed(2);
        document.getElementById("modalStock").textContent =
            "库存：" + first.dataset.vstock;
    }
}

// 每次 modal 载入规格完成后自动选中第一个
document.addEventListener("DOMContentLoaded", () => {
    // 在 openVariantModal 的 fetch load 完之后 0.1 秒运行
    setTimeout(autoSelectFirstCard, 150);
});
/* ==================================================
   🌸 Variant 分页逻辑（每页 5 条）
================================================== */
let variantList = [];
let variantPage = 1;
const variantPerPage = 5;

/* 在载入 variant_box_front.php 后触发 */
function setupVariantPagination() {
    const cards = document.querySelectorAll("#variantModal .variant-card");
    variantList = Array.from(cards);
    variantPage = 1;

    if (variantList.length <= variantPerPage) {
        document.getElementById("variantPagination").style.display = "none";
        return;
    }

    document.getElementById("variantPagination").style.display = "block";
    renderVariantPage();
}

function renderVariantPage() {
    let start = (variantPage - 1) * variantPerPage;
    let end = start + variantPerPage;

    variantList.forEach((c, i) => {
        c.style.display = (i >= start && i < end) ? "flex" : "none";
    });

    let totalPage = Math.ceil(variantList.length / variantPerPage);
    document.getElementById("variantPageInfo").textContent =
        variantPage + " / " + totalPage;

    document.getElementById("prevVariantPage").disabled = variantPage === 1;
    document.getElementById("nextVariantPage").disabled = variantPage === totalPage;
}

/* 按钮点击 */
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


