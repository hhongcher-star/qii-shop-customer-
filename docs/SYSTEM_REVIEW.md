# Qii Shop System Review

Date: 2026-06-05
Status: High-priority fixes applied in code.

This document reviews the current PHP shop system after the file reorganization into `frontend`, `api`, `a9sd8f7sd9f_admin`, `archive`, `database`, and `docs`.

## Overall Summary

The system is workable for a small XAMPP-based shop: products, cart, checkout, receipt, admin login, product management, stock management, coupons, orders, and customer messages are all present. The code already uses PDO prepared statements in most database operations, admin pages use session login checks, and admin POST actions generally use CSRF protection.

The main concerns were business-logic correctness, production hardening, and security controls around public APIs, coupons, uploads, and archived files. The high-priority items below have now been addressed in the codebase, with remaining notes kept for future production hardening.

## Fixes Applied

- Added `app/bootstrap.php` for shared environment handling, frontend CSRF helpers, shipping rules, coupon validation, upload-safe helper functions, and order security column checks.
- Added frontend CSRF tokens to public pages and POST requests.
- Added CSRF checks to cart, checkout, order submit, coupon, address, and contact form POST flows.
- Reworked coupon handling so validation no longer increments usage.
- Reworked `save_coupon.php` so it stores only coupon code, not browser-supplied discount amounts.
- Reworked `submit_order.php` so it recalculates item prices, subtotal, shipping, discount, and stock from the database before saving the order.
- Coupon usage is now incremented once, after order creation succeeds.
- Added receipt token support for newly submitted orders.
- Added `receipt_token` and `coupon_code` to `database/local_schema.sql`.
- Added runtime column creation for `receipt_token` and `coupon_code` on existing local databases.
- Denied direct web access to `archive`, `app`, `database`, and `docs`.
- Disabled PHP execution in product upload directories.
- Tightened image upload validation and changed generated directory permission from `0777` to `0755`.
- Removed public `display_errors=1` from live frontend/API files.
- Standardized the old `awaiting_payment_gateway` write to `pending`.

## Current Structure

- `frontend/pages` - Public website pages.
- `frontend/includes` - Shared frontend header, footer, and SEO helpers.
- `frontend/components` - Shared UI components such as the variant modal.
- `api` - Public cart, checkout, coupon, search, and order-submission endpoints.
- `a9sd8f7sd9f_admin` - Admin dashboard and management pages.
- `archive` - Old/legacy files that should not be part of the live system.
- `database` - SQL schema files.
- `docs` - Project notes and review documents.

## High Priority Issues

### 1. Production error display is enabled in public endpoints

Files such as:

- `frontend/pages/shop.php`
- `api/search_suggest.php`
- `api/submit_order.php`

use `error_reporting(E_ALL)` and `ini_set('display_errors', 1)`.

This was addressed by removing page-level `display_errors=1` from public files and moving error display control into `app/bootstrap.php`.

Before going live, set `QII_APP_ENV` in `app/bootstrap.php` to `production`.

### 2. Coupon usage is counted too early and possibly twice

The current coupon flow has overlapping responsibilities:

- `api/validate_coupon.php` validates the coupon and immediately increments `used_count`.
- `api/use_coupon.php` increments `used_count` again.
- `frontend/pages/receipt.php` calls `validate_coupon.php`, then `save_coupon.php`, then `use_coupon.php`.

This was addressed. `validate_coupon.php` only validates, `save_coupon.php` stores coupon code, and `submit_order.php` records usage after the order is saved.

### 3. Discount amount can be manipulated through `save_coupon.php`

This was addressed. The browser no longer supplies the final discount amount.

### 4. Order totals rely heavily on session data

`api/checkout.php` calculates totals and saves them to `$_SESSION['pending_order']`. `api/submit_order.php` then uses the pending order values.

This was addressed. `submit_order.php` now reloads product/variant prices and stock before writing the order.

### 5. Public state-changing APIs have no CSRF protection

These endpoints modify session or data:

- `api/add_to_cart.php`
- `api/checkout.php`
- `api/submit_order.php`
- `api/save_coupon.php`
- `api/use_coupon.php`
- `api/save_address.php`

They are public shop APIs, so they do not need admin login, but they should still have basic CSRF protection or SameSite cookie assumptions documented. The admin side already has CSRF helpers; the frontend side does not.

This was addressed with `qii_frontend_csrf_token()` and `qii_verify_frontend_csrf()`.

## Medium Priority Issues

### 6. Admin upload directory is public

Product images are uploaded to `images/products`. The upload code checks MIME type and file size, and renames files, which is good. However, uploaded files are still publicly served.

Current risk is moderate because only image MIME types are allowed, but upload validation should be stricter.

This was addressed for product uploads.

### 7. Archive files are publicly reachable

The `archive` directory contains old PHP files. Even if they are legacy, a visitor may still be able to request them directly through the browser.

This was addressed with `archive/.htaccess`.

### 8. Admin folder name is obscure but not real security

The admin folder name `a9sd8f7sd9f_admin` helps reduce casual discovery, but security should not rely on obscurity.

What is already good:

- Admin pages call `require_admin()`.
- Login uses `password_verify()`.
- Admin POST actions generally use CSRF.

Recommended additions:

- Add login rate limiting.
- Add account lockout or delay after repeated failures.
- Enforce HTTPS in production.
- Consider IP allowlisting if this is only for internal use.

### 9. Receipt page exposes order lookup by order number

`frontend/pages/receipt.php` can load an order by `order_number`. If order numbers are guessable, someone could view another receipt.

Current order numbers include timestamp and random 3 digits, for example `QIIymdHis###`. This is not strong enough as a secret.

This was addressed for new orders with `receipt_token`.

### 10. Contact form has no spam protection

`frontend/pages/contact.php` inserts messages into the database with no rate limit, CAPTCHA, honeypot, or cooldown.

Recommended fix:

- Add a hidden honeypot field.
- Add session/IP cooldown.
- Limit message length.
- Optionally add CAPTCHA later.

## Logic And Data Consistency Issues

### 11. Coupon model needs one clear source of truth

Right now coupon state is split across:

- Browser-submitted discount.
- `$_SESSION['coupon_used']`.
- `$_SESSION['coupon_discount']`.
- `coupons.used_count`.
- Separate `validate_coupon.php` and `use_coupon.php`.

Recommended target model:

- Session stores `coupon_code` only.
- `submit_order.php` validates coupon and calculates discount.
- Order row stores `coupon_code` and `discount`.
- Coupon usage increments once after order creation succeeds.

### 12. Checkout and order status names are inconsistent

The code uses statuses such as:

- `pending`
- `awaiting_payment`
- `awaiting_payment_gateway`
- `paid`
- `shipped`
- `completed`
- `cancelled`
- `draft`

The direct `save_address.php` mismatch was fixed to write `pending`.

### 13. `save_address.php` may be redundant

The main order submission logic in `api/submit_order.php` already saves address fields. `api/save_address.php` appears to be an older helper.

Recommended fix:

- Search browser calls for `save_address.php`.
- If unused, archive it.
- If used, merge its behavior into `submit_order.php`.

### 14. Cart stores product names and prices in session

`api/add_to_cart.php` currently loads price from the database when adding to cart, which is good. But after that, the cart keeps price in session.

Risk:

- Product price changes after cart add are not reflected at final submit.

Recommended fix:

- Keep display price in session if needed.
- Recalculate final payable price from database during `submit_order.php`.

### 15. Duplicate helper functions exist across files

Functions like `qii_text()` and `qii_asset_path()` appear in several files.

Recommended fix:

- Add `includes/helpers.php` or `app/helpers.php`.
- Move shared functions there.
- This will reduce bugs when one copy is updated and another is forgotten.

## Security Positives

The system already has several good practices:

- PDO prepared statements are used broadly.
- Admin pages require login through `require_admin()`.
- Admin POST actions generally call `verify_csrf()`.
- Admin login uses `password_verify()`.
- Sessions regenerate on successful admin login.
- Product upload filenames are regenerated.
- Stock deduction in `submit_order.php` uses a transaction and checks `stock >= qty`.

These are solid foundations.

## Suggested Fix Order

### Phase 1: Immediate hardening

1. Turn off public error display in production.
2. Deny web access to `archive`.
3. Add `.htaccess` to uploaded-image directories to prevent PHP execution.
4. Remove or protect unused public helper endpoints.

### Phase 2: Coupon and order correctness

1. Remove coupon `used_count` update from `validate_coupon.php`.
2. Remove duplicate coupon counting from `use_coupon.php`, or retire that endpoint.
3. Store only coupon code in session.
4. Recalculate coupon discount inside `submit_order.php`.
5. Increment coupon usage once, inside the order transaction.

### Phase 3: Checkout reliability

1. Recalculate prices from database in `submit_order.php`.
2. Standardize order statuses.
3. Add a secure receipt token.
4. Add frontend CSRF for public POST APIs.

### Phase 4: Maintainability

1. Create shared helper files for repeated functions.
2. Create a central config for environment, upload limits, shipping rules, and statuses.
3. Add a short manual test checklist for cart, checkout, coupon, admin product edit, and order status update.

## Recommended Manual Test Checklist

After fixing the high-priority items, test:

- Add normal product to cart.
- Add variant product to cart.
- Add quantity above stock and confirm it is blocked.
- Checkout West Malaysia under and above free-shipping threshold.
- Checkout East Malaysia under and above free-shipping threshold.
- Apply valid coupon once.
- Apply expired coupon.
- Apply coupon below minimum order.
- Submit order and confirm stock decreases once.
- Refresh receipt page and confirm order still displays.
- Admin login/logout.
- Admin create product.
- Admin edit product.
- Admin upload product image.
- Admin update stock.
- Admin update order status.

## Final Assessment

The project is in a reasonable early production shape for a small shop, but it should not be treated as fully hardened yet. The largest business risk is coupon/order total correctness. The largest security risk is public exposure of debug errors and web-accessible legacy files. The admin foundation is stronger than the public checkout flow because it already has authentication and CSRF checks.

Fixing coupon handling, production error settings, archive access, upload execution rules, and final order recalculation would significantly improve the system.
