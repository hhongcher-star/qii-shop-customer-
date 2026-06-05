# PHP File Guide

This guide explains the role of each PHP file in the project.

## Frontend Pages

- `frontend/pages/index.php` - Homepage for qii.shoppp.
- `frontend/pages/shop.php` - Product listing page. Supports category filtering and AJAX product-card loading.
- `frontend/pages/search.php` - Search results page for product name and SKU queries.
- `frontend/pages/contact.php` - Contact page. Saves customer messages into the `messages` table.
- `frontend/pages/receipt.php` - Order receipt page. Loads order details from session or database by `order_number`.
- `frontend/pages/sitemap.php` - Dynamic sitemap output.

## Frontend APIs

- `api/add_to_cart.php` - Cart API. Handles add, remove, clear, and cart fetch actions.
- `api/checkout.php` - Checkout API. Validates cart, region, stock, shipping, and pending order data.
- `api/submit_order.php` - Final order submission. Saves order, order items, customer address, and deducts stock.
- `api/save_address.php` - Older address update helper. Keep unless the checkout flow is fully retested without it.
- `api/validate_coupon.php` - Validates coupon code, date range, usage limit, minimum order, and discount.
- `api/save_coupon.php` - Stores the coupon code in session for the current order. Final discount is recalculated server-side.
- `api/use_coupon.php` - Legacy-compatible endpoint. Coupon usage is now recorded by `submit_order.php` after order creation succeeds.
- `api/search_suggest.php` - JSON search suggestion API.
- `api/variant_box_front.php` - Frontend variant selector HTML loaded by `frontend/components/variant_modal.php`.

## Frontend Components

- `frontend/components/variant_modal.php` - Product variant bottom-sheet component used by shop and search pages.

## Shared Includes

- `frontend/includes/header.php` - Shared frontend header and navigation.
- `frontend/includes/footer.php` - Shared frontend footer.
- `frontend/includes/seo.php` - SEO helper for title, meta tags, canonical URLs, Open Graph, Twitter cards, and JSON-LD.

## Admin Core

- `a9sd8f7sd9f_admin/config.php` - Database connection and shared admin configuration.
- `a9sd8f7sd9f_admin/auth.php` - Admin session, login guard, CSRF token helpers.
- `a9sd8f7sd9f_admin/login.php` - Admin login page.
- `a9sd8f7sd9f_admin/logout.php` - Admin logout handler.
- `a9sd8f7sd9f_admin/index.php` - Admin entry point.
- `a9sd8f7sd9f_admin/includes/admin_header.php` - Shared admin navigation/header.

## Admin Pages

- `a9sd8f7sd9f_admin/dashboard.php` - Sales, order, stock, coupon, and message dashboard.
- `a9sd8f7sd9f_admin/product.php` - Product list management.
- `a9sd8f7sd9f_admin/product_editor.php` - Add/edit product page, including images and variants.
- `a9sd8f7sd9f_admin/inventory.php` - Product and variant stock management.
- `a9sd8f7sd9f_admin/order.php` - Order management, filters, status updates, and bulk actions.
- `a9sd8f7sd9f_admin/discount_center.php` - Coupon management.
- `a9sd8f7sd9f_admin/reply.php` - Customer message management and reply handling.
- `a9sd8f7sd9f_admin/update_status.php` - Order status update helper that redirects back to `order.php`.

## Archived Legacy Files

- `archive/backups/index_backup_for_ai.php` - Old homepage backup.
- `archive/backups/search_fixed.tmp.php` - Old search page backup.
- `archive/legacy-admin/product.php` - Old root-level product admin page.
- `archive/legacy-admin/edit_product.php` - Old root-level product edit page.
- `archive/legacy-admin/variant_box.php` - Old root-level variant management page.
- `archive/legacy-admin/edit_variant.php` - Old single-variant edit page used by the archived variant manager.

## Deleted Cleanup Files

- `archive/backups/checkout_before.txt`
- `archive/backups/checkout_hex.txt`
- `archive/temp/shop_tmp.txt`
- `archive/temp/Temp_block.txt`
- `a9sd8f7sd9f_admin/test_db.php`
