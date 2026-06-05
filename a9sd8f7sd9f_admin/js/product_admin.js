(() => {
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  function bindImagePreview(input) {
    input.addEventListener("change", () => {
      const file = input.files && input.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      const targetSelector = input.dataset.previewTarget;
      const target = targetSelector ? $(targetSelector) : input.closest("label")?.querySelector("img");
      if (target) target.src = url;
      const cardPreview = $("#cardPreviewImg");
      if (input.name === "image" && cardPreview) cardPreview.src = url;
    });
  }

  function refreshPreview() {
    const nameInput = $("[data-preview-name]");
    const categorySelect = $("[data-preview-category]");
    const priceInput = $("[data-preview-price]");
    const variantInput = $("[data-preview-variant]");

    const nameTarget = $("#cardPreviewName");
    const tagTarget = $("#cardPreviewTag");
    const priceTarget = $("#cardPreviewPrice");

    if (nameTarget && nameInput) {
      nameTarget.textContent = nameInput.value.trim() || "商品名称将显示在这里";
    }
    if (tagTarget && categorySelect) {
      const selected = categorySelect.options[categorySelect.selectedIndex];
      tagTarget.textContent = selected ? selected.textContent : "多种规格可选";
    }
    if (priceTarget && priceInput) {
      const value = parseFloat(priceInput.value || "0");
      priceTarget.textContent = `RM ${Number.isFinite(value) ? value.toFixed(2) : "0.00"}`;
    }
    if (tagTarget && variantInput && variantInput.value.trim()) {
      tagTarget.textContent = "多种规格可选";
    }
  }

  function reindexVariantFiles() {
    $$("#variantList .variant-row").forEach((row, index) => {
      const file = row.querySelector('input[type="file"]');
      if (file) file.name = `variant_image_${index}`;
      const img = row.querySelector("img");
      if (img && !img.id) img.id = `variantPreviewNew${index}`;
    });
  }

  function bindVariantRow(row) {
    const deleteButton = $("[data-delete-variant]", row);
    if (deleteButton) {
      deleteButton.addEventListener("click", () => {
        const rows = $$("#variantList .variant-row");
        if (rows.length <= 1) {
          row.querySelectorAll("input").forEach(input => {
            if (input.type !== "hidden" && input.type !== "file") input.value = "";
          });
          refreshPreview();
          return;
        }
        row.remove();
        reindexVariantFiles();
        refreshPreview();
      });
    }

    row.addEventListener("dragstart", event => {
      row.classList.add("dragging");
      event.dataTransfer.effectAllowed = "move";
    });

    row.addEventListener("dragend", () => {
      row.classList.remove("dragging");
      reindexVariantFiles();
    });

    row.querySelectorAll("input").forEach(input => input.addEventListener("input", refreshPreview));
    row.querySelectorAll("[data-image-input]").forEach(bindImagePreview);
  }

  function bindVariantList() {
    const list = $("#variantList");
    if (!list) return;

    list.addEventListener("dragover", event => {
      event.preventDefault();
      const dragging = $(".variant-row.dragging", list);
      if (!dragging) return;
      const rows = $$(".variant-row:not(.dragging)", list);
      const next = rows.find(row => event.clientY < row.getBoundingClientRect().top + row.offsetHeight / 2);
      if (next) list.insertBefore(dragging, next);
      else list.appendChild(dragging);
    });

    $$(".variant-row", list).forEach(bindVariantRow);
  }

  function bindAddVariant() {
    const button = $("[data-add-variant]");
    const template = $("#variantTemplate");
    const list = $("#variantList");
    if (!button || !template || !list) return;

    button.addEventListener("click", () => {
      const index = $$(".variant-row", list).length;
      const wrapper = document.createElement("div");
      wrapper.innerHTML = template.innerHTML.replace("__IMAGE_NAME__", `variant_image_${index}`);
      const row = wrapper.firstElementChild;
      list.appendChild(row);
      bindVariantRow(row);
      refreshPreview();
    });
  }

  function bindProductType() {
    const form = $("[data-product-form]");
    const select = $("[data-product-type]");
    const singleFields = $("[data-single-fields]");
    const variantSection = Array.from(document.querySelectorAll(".editor-card")).find(card => card.textContent.includes("Variant"));
    if (!form || !select) return;

    const sync = () => {
      const isVariant = select.value === "variant";
      form.dataset.productTypeValue = select.value;
      if (singleFields) singleFields.hidden = isVariant;
      if (variantSection) variantSection.classList.toggle("variant-section-hidden", !isVariant);
    };

    select.addEventListener("change", sync);
    sync();
  }

  function bindDrawer() {
    const openButton = $("[data-drawer-open]");
    const sidebar = $(".admin-sidebar");
    if (!openButton || !sidebar) return;
    openButton.addEventListener("click", () => {
      sidebar.classList.toggle("drawer-open");
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    $$("[data-image-input]").forEach(bindImagePreview);
    $$("[data-preview-name], [data-preview-category], [data-preview-price], [data-preview-variant]").forEach(input => {
      input.addEventListener("input", refreshPreview);
      input.addEventListener("change", refreshPreview);
    });
    bindVariantList();
    bindAddVariant();
    bindProductType();
    bindDrawer();
    refreshPreview();
  });
})();
