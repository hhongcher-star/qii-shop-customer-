-- Run once on existing databases before deploying the updated product editor.
ALTER TABLE products
  ADD COLUMN brand VARCHAR(120) NULL AFTER category,
  ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'active' AFTER brand,
  DROP INDEX idx_products_sku,
  ADD UNIQUE INDEX uq_products_sku (sku);

ALTER TABLE product_variants
  ADD COLUMN sku VARCHAR(100) NULL AFTER variant_name,
  ADD UNIQUE INDEX uq_variants_sku (sku);
