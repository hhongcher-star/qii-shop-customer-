<?php
$productCardAos = $productCardAos ?? '';
?>
<div class="product-card" data-product-id="<?= (int)$p['id'] ?>"<?= $productCardAos !== '' ? ' data-aos="' . htmlspecialchars($productCardAos, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
  <?php if (!empty($p['brand'])): ?>
    <div class="brand-badge"><?= htmlspecialchars(qii_text($p['brand'])) ?></div>
  <?php endif; ?>
  <?php if ($p['stock'] <= 0): ?>
    <div class="soldout-tag">SOLD OUT</div>
  <?php endif; ?>
  <img src="<?= htmlspecialchars(qii_asset_path($p['image_url'] ?? '')) ?>" alt="<?= htmlspecialchars(qii_text($p['name'])) ?>">
  <div class="product-info">
    <h4><?= htmlspecialchars(qii_text($p['name'])) ?></h4>
    <div class="price">RM <?= number_format($p['price'], 2) ?></div>
  </div>
  <?php if ($p['stock'] > 0): ?>
    <button onclick='openVariantModal(<?= qii_product_payload($p) ?>)' class="choose-btn" aria-label="Add to cart"></button>
  <?php else: ?>
    <button class="add-btn" disabled>Sold out</button>
  <?php endif; ?>
</div>
