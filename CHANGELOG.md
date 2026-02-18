# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

## Release Notes

### Version 1.0.0 - Initial Release

This is the first stable release of WP Product Plugin, created as a test assignment for a WordPress Developer position.

**Key Features:**
- FakeStore API integration with 20 sample products
- Two shortcodes for different use cases
- AJAX-powered random product loading
- Custom Post Type with full metadata
- Admin interface for configuration
- Automatic release creation via GitHub Actions

**Requirements:**
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

**Installation:**
Download the ZIP file from GitHub Releases and install via WordPress admin.

---

## Future Roadmap (Potential Features)

### Version 1.1.0 (Released)
- [x] CPT slug renamed to `wpp_product` (WooCommerce compatibility)
- [x] Safe uninstall — only `wpp_product` posts are removed
- [x] SQL hardened with `$wpdb->prepare()` in cache clearing
- [x] AJAX dependency injection — eliminated duplicate shortcodes instance

### Version 1.2.0 (Planned)
- [ ] Product categories taxonomy
- [ ] Search and filter products in admin
- [ ] Bulk import from API
- [ ] Custom product template override
- [ ] Widget for random product display

### Version 1.3.0 (Planned)
- [ ] Integration with WooCommerce
- [ ] Product comparison feature
- [ ] Wishlist functionality
- [ ] Product reviews system
- [ ] Multi-language support (WPML compatibility)

### Version 2.0.0 (Planned — roadmap)
- [ ] Support for multiple API sources
- [ ] Advanced caching strategies
- [ ] REST API endpoints
- [ ] GraphQL support
- [ ] Performance optimizations
- [ ] Unit and integration tests

---

[1.1.0]: https://github.com/sorkin19822/wordpress-test-task/releases/tag/v1.1.0
[1.0.0]: https://github.com/sorkin19822/wordpress-test-task/releases/tag/v1.0.0
