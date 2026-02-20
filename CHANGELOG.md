# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-02-20

### Architecture (major refactoring)
- **Added `WP_Product` model class** (`includes/class-wp-product.php`): typed DTO with `from_api_array()` factory; all classes now receive a typed model instead of raw arrays — eliminates fragile `isset()` guards spread across four files
- **Added `WP_Product_Renderer` class** (`includes/class-wp-product-renderer.php`): dedicated card renderer used by both Shortcodes and AJAX — removes the wrong coupling where AJAX called into the Shortcodes class to render HTML
- **Refactored `WP_Product_Plugin_API`**: returns `WP_Product` model instead of raw array; added `MIN_PRODUCT_ID`/`MAX_PRODUCT_ID` constants (single source of truth for the 1–20 range); replaced raw SQL in `clear_cache()` with a `delete_transient()` loop (cache-layer-agnostic)
- **Refactored `WP_Product_Plugin_CPT`**: accepts `WP_Product` model; removed `update_last_created_timestamp()` (settings concern moved to Admin via `do_action('wp_product_plugin_product_created')`); added `no_found_rows => true` and `fields => ids` to duplicate-check query; renamed `get_product_by_api_id()` to `get_post_id_by_api_id()` to reflect the return type
- **Refactored `WP_Product_Plugin_AJAX`**: replaced `WP_Product_Plugin_Shortcodes` dependency with `WP_Product_Renderer`; added explicit `wp_die()` after every `wp_send_json_error()`; added per-IP rate limiting (20 req/60 s)
- **Refactored `WP_Product_Plugin_Shortcodes`**: `render_product_card()` is now `private` (internal concern); constructor takes `WP_Product_Renderer` as second dependency
- **Refactored main class `WP_Product_Plugin`**: all instance properties changed from `protected` to `private` (Singleton must not be subclassed); initialization moved from constructor into `run()` to separate object construction from plugin execution; plugin bootstrapped on `plugins_loaded` hook (proper deferred init)
- **Added `record_product_created_timestamp()` to `WP_Product_Plugin_Admin`**: listens to `wp_product_plugin_product_created` action — keeps settings concerns out of the CPT class
- **Admin `sanitize_settings()`**: now references `WP_Product_Plugin_API::MIN_PRODUCT_ID` / `MAX_PRODUCT_ID` constants instead of hardcoded literals

### Security
- **SSRF protection** (`class-wp-product-plugin-cpt.php`): URL scheme validated (`http`/`https` only) before `download_url()` is called on API image URLs
- **XSS hardening** (`assets/js/public.js`): error messages now inserted via `.text()` instead of HTML string concatenation; added per-container loading guard to prevent concurrent requests
- **`esc_html()`** applied to AJAX error messages server-side before JSON encoding
- **`wp_die()`** added explicitly after every `wp_send_json_error()` call (belt-and-suspenders, safe on WP < 5.5)
- **Rate limiting** on the `nopriv` AJAX endpoint (transient-based, per hashed IP)
- **`wp_delete_file()`** replaces `@unlink` — no error suppression, respects WP filesystem abstraction
- **`ABSPATH` guard** in `wp-product-plugin.php` (was `WPINC` — now consistent with all other files)
- **`render_settings_page()`** calls `wp_die()` on capability failure instead of silently returning

### Bug Fixes
- `current_time('timestamp')` (deprecated since WP 5.3) replaced with `time()` in `human_time_diff()` call
- `Requires PHP: 8.0` added to plugin header (union types `T|U` require PHP 8.0+; previously missing)
- Uninstall now batches deletions in groups of 100 to prevent memory exhaustion on large sites
- Uninstall uses a local constant instead of attempting to bootstrap the plugin

### Code Quality
- Added `languages/` directory (Text Domain and Domain Path were declared but the directory was missing)
- Fixed `temp/` `.gitignore` pattern from `/temp` (root-only) to `temp/` (any depth)

---

## [1.1.0] - 2026-02-18

### Fixed
- **CPT slug conflict with WooCommerce**: renamed post type slug from `product` to `wpp_product` to avoid conflicts when WooCommerce is installed simultaneously (`includes/class-wp-product-plugin-cpt.php`)
- **Unsafe uninstall**: `uninstall.php` was deleting all posts with slug `product`, which could destroy WooCommerce products; updated to use `wpp_product`
- **SQL without `$wpdb->prepare()`**: the bulk transient DELETE query in `clear_cache()` now uses `$wpdb->prepare()` with `$wpdb->esc_like()`, complying with WordPress Coding Standards (`includes/class-wp-product-plugin-api.php`)
- **Duplicate `WP_Product_Plugin_Shortcodes` instance in AJAX**: `handle_get_random_product()` was instantiating a second shortcodes object; the existing instance from the main class is now injected via the AJAX constructor (`includes/class-wp-product-plugin-ajax.php`, `includes/class-wp-product-plugin.php`)

---

## [1.0.0] - 2026-02-17

### Added
- Initial release of WP Product Plugin
- Integration with FakeStore API
- Custom Post Type "Product" for storing products
- Shortcode `[product_display]` to show specific products
- Shortcode `[random_product]` with AJAX functionality
- Admin settings page under Settings → Product Plugin
- Product ID configuration (1-20 validation)
- Last product creation timestamp display
- Automatic product image download and featured image setting
- Product metadata storage (price, category, rating)
- AJAX handler with nonce verification
- WordPress transient caching for API responses (1 hour)
- Responsive product card design
- Loading state for AJAX requests
- Error handling for API failures
- Product deduplication by API ID
- Link to CPT post from random product shortcode
- Docker Compose environment for development
- GitHub Actions CI/CD pipeline for releases
- Comprehensive PHPDoc documentation
- Security features (nonce, sanitization, escaping)
- Uninstall cleanup script

### Security
- Nonce verification for all AJAX requests
- Input sanitization using WordPress functions
- Output escaping for all displayed data
- Capability checks for admin settings
- Direct file access prevention

### Technical
- Object-oriented architecture
- Separation of concerns (API, CPT, Admin, AJAX, Shortcodes)
- Singleton pattern for main class
- WordPress coding standards compliance
- Uses `wp_remote_get()` instead of cURL
- Returns `WP_Error` for error handling
- Follows WordPress Plugin Handbook best practices

---

[1.2.0]: ../../releases/tag/v1.2.0
[1.1.0]: ../../releases/tag/v1.1.0
[1.0.0]: ../../releases/tag/v1.0.0
