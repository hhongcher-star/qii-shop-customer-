CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(100) NULL,
  name VARCHAR(255) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock INT NOT NULL DEFAULT 0,
  warning_level INT NOT NULL DEFAULT 5,
  category VARCHAR(80) NOT NULL DEFAULT 'phone',
  image_url VARCHAR(255) NULL,
  has_variant TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_products_category (category),
  KEY idx_products_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_key VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  emoji VARCHAR(20) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_admin_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_users (username, password_hash, status)
SELECT 'admin', '$2y$10$1DWMXD.Tox4JL6EESUICE.fx8JQ16de15xbBKtI84t1uKq2oKDUwm', 'active'
WHERE NOT EXISTS (SELECT 1 FROM admin_users WHERE username = 'admin');

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  email_verified_at DATETIME NULL,
  phone VARCHAR(80) NULL,
  password_hash VARCHAR(255) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  admin_notes TEXT NULL,
  admin_tags VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_customers_status (status),
  KEY idx_customers_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_favorites (
  customer_id INT NOT NULL,
  product_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id, product_id),
  KEY idx_favorites_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_remember_tokens (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  selector VARCHAR(64) NOT NULL UNIQUE,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_remember_customer (customer_id),
  KEY idx_remember_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_action_tokens (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  purpose VARCHAR(30) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_action_customer (customer_id),
  KEY idx_action_purpose (purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  group_name VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_groups_product (product_id),
  CONSTRAINT fk_groups_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  variant_name VARCHAR(160) NOT NULL,
  price DECIMAL(10,2) NULL,
  stock INT NOT NULL DEFAULT 0,
  image_url VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_variants_group (group_id),
  CONSTRAINT fk_variants_group FOREIGN KEY (group_id) REFERENCES product_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NULL,
  order_number VARCHAR(80) NOT NULL UNIQUE,
  receipt_token VARCHAR(128) NULL,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  shipping DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  coupon_code VARCHAR(80) NULL,
  grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  region VARCHAR(20) NULL,
  order_status VARCHAR(80) NOT NULL DEFAULT 'draft',
  addr_name VARCHAR(160) NULL,
  addr_phone VARCHAR(80) NULL,
  addr_address TEXT NULL,
  addr_postcode VARCHAR(20) NULL,
  addr_state VARCHAR(80) NULL,
  order_note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_orders_created (created_at),
  KEY idx_orders_status (order_status),
  KEY idx_orders_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  variant_name VARCHAR(160) NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  sku VARCHAR(100) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_order_items_order (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  min_order DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  start_date DATE NULL,
  end_date DATE NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  max_usage INT NULL,
  used_count INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NULL,
  message TEXT NOT NULL,
  reply TEXT NULL,
  replied_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (sku, name, price, stock, category, image_url, has_variant, sort_order, created_at)
SELECT 'A001', '手机链/挂包 蓝色小挂件', 6.99, 20, 'phone', 'images/10.png', 0, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'A001');

INSERT INTO products (sku, name, price, stock, category, image_url, has_variant, sort_order, created_at)
SELECT 'A002', '粉色可爱钥匙扣', 6.99, 20, 'phone', 'images/11.png', 0, 2, NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'A002');

INSERT INTO products (sku, name, price, stock, category, image_url, has_variant, sort_order, created_at)
SELECT 'A003', '蓝色小熊挂件', 7.99, 15, 'phone', 'images/12.png', 0, 3, NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'A003');

INSERT INTO products (sku, name, price, stock, category, image_url, has_variant, sort_order, created_at)
SELECT 'A004', '粉色甜心挂件', 5.99, 18, 'phone', 'images/13.png', 0, 4, NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'A004');

INSERT INTO products (sku, name, price, stock, category, image_url, has_variant, sort_order, created_at)
SELECT 'H001', '蝴蝶结发夹', 4.99, 12, 'hair', 'images/14.png', 0, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'H001');

INSERT INTO products (sku, name, price, stock, category, image_url, has_variant, sort_order, created_at)
SELECT 'S001', '可爱零食小包', 3.99, 30, 'snack', 'images/15.png', 0, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'S001');

INSERT INTO coupons (code, description, discount_amount, min_order, start_date, end_date, status, max_usage, used_count)
SELECT 'QII5', 'Local test coupon', 5.00, 30.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 'active', 100, 0
WHERE NOT EXISTS (SELECT 1 FROM coupons WHERE code = 'QII5');
