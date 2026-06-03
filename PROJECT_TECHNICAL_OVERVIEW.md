# Qii Shop Project Technical Overview

## 1. Project Type

This is a small PHP-based e-commerce website.

It is not a React, Vue, Laravel, WordPress, or Node.js project. The pages are mostly plain PHP files that directly output HTML, CSS, JavaScript, and database results.

## 2. Main Technologies Used

### Backend

- PHP
- PDO for MySQL database access
- PHP sessions for cart and temporary order data
- Apache `.htaccess` rewrite rules

### Database

- MySQL or MariaDB
- The project expects database tables for products, product variants, orders, order items, coupons, messages, and related shop data.

### Frontend

- HTML
- CSS
- JavaScript
- Some external CDN libraries, including:
  - Font Awesome
  - Chart.js
  - AOS animation library
  - Google Fonts

### Server

For local development, use one of these:

- XAMPP
- Laragon
- WAMP

Recommended for beginners: XAMPP or Laragon.

For hosting, the current project appears intended for a shared PHP hosting environment such as Hostinger.

## 3. What You Need To Run Locally

You need:

1. PHP
2. Apache
3. MySQL or MariaDB
4. phpMyAdmin or another MySQL database tool
5. A local database imported from Hostinger or created manually

The easiest local setup is:

- Install XAMPP
- Start Apache
- Start MySQL
- Put this project inside the XAMPP `htdocs` folder
- Create/import the database in phpMyAdmin
- Update the database connection in `a9sd8f7sd9f_admin/config.php`

## 4. Important Project Files

### Public shop pages

- `index.php`
- `shop.php`
- `product.php`
- `checkout.php`
- `receipt.php`
- `contact.php`
- `search.php`

### Cart and order APIs

- `add_to_cart.php`
- `submit_order.php`
- `save_address.php`
- `validate_coupon.php`
- `use_coupon.php`
- `save_coupon.php`

### Admin area

- `a9sd8f7sd9f_admin/dashboard.php`
- `a9sd8f7sd9f_admin/order.php`
- `a9sd8f7sd9f_admin/inventory.php`
- `a9sd8f7sd9f_admin/discount_center.php`
- `a9sd8f7sd9f_admin/config.php`

### Shared files

- `includes/header.php`
- `includes/footer.php`
- `css/shop.css`
- `css/qii-modal.css`
- `images/`

## 5. Database Connection

The database connection is currently inside:

```text
a9sd8f7sd9f_admin/config.php
```

For local development, the connection usually looks like this:

```php
$host = "127.0.0.1";
$db   = "qi_shop";
$user = "root";
$pass = "";
```

For Hostinger, the values are different and come from the Hostinger database panel.

Important: real Hostinger database passwords should not stay inside the project permanently.

## 6. Local First, Hosting Later

The correct order is:

1. Make the website run locally.
2. Create or import the local MySQL database.
3. Update `config.php` to connect to the local database.
4. Test shop pages, cart, checkout, admin pages, and image paths.
5. After everything works locally, prepare the Hostinger version.
6. Remove or replace any real Hostinger credentials from the project files.

Do not start by connecting to Hostinger if the local version is not working yet.

## 7. Security Items To Fix Before Real Use

Before using this project publicly, fix these:

- Move database credentials out of normal project files.
- Add real admin login protection.
- Turn off public error display.
- Add CSRF protection to admin forms.
- Avoid delete actions through GET links.
- Add safer image upload validation.
- Use database transactions for order creation and stock deduction.

## 8. Suggested Local Development Stack

Recommended stack:

- XAMPP or Laragon
- PHP 8.x
- MySQL or MariaDB
- Apache
- phpMyAdmin
- VS Code or another code editor

No Node.js is required unless you later decide to rebuild the frontend with a modern framework.

