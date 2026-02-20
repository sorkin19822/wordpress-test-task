# WP Product Plugin

A WordPress plugin that integrates with the [FakeStore API](https://fakestoreapi.com) to display products via shortcodes, with AJAX support and Custom Post Type persistence.

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net)

---

## Features

- Fetches product data from [FakeStore API](https://fakestoreapi.com) (20 products, IDs 1–20)
- **Shortcode `[product_display]`** — renders a product card (ID from settings or inline attribute)
- **Shortcode `[random_product]`** — button that loads a random product via AJAX without page reload
- **Custom Post Type `wpp_product`** — saves every fetched product with price, category, rating, and featured image
- **Admin settings page** (Settings → Product Plugin) — configure default product ID and card style
- **Last-created timestamp** — records when the most recent product post was created
- Transient caching of API responses (1 hour)
- Responsive, mobile-friendly product cards with optional enhanced styles

---

## Requirements

| Requirement | Minimum |
|-------------|---------|
| WordPress   | 5.8     |
| PHP         | 8.0     |
| MySQL       | 5.7     |

---

## Installation

### Via WordPress Admin (recommended)

1. Download the latest `wp-product-plugin.zip` from the [Releases](../../releases) page.
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**.
3. Upload the ZIP, click **Install Now**, then **Activate**.

### Manual

1. Unzip the archive into `/wp-content/plugins/wp-product-plugin/`.
2. Activate the plugin from **Plugins** in the WordPress admin.

### Local development (Docker)

```bash
git clone <repository-url> wp-product-plugin
cd wp-product-plugin
docker-compose up -d
# WordPress → http://localhost:8080
# PHPMyAdmin → http://localhost:8081
```

---

## Usage

### 1. Configure the default product

**Settings → Product Plugin** → enter a Product ID (1–20) and save.

### 2. Add shortcodes to any page or post

**Display a specific product:**
```
[product_display]
```
Uses the Product ID set in the admin. You can also pass an ID inline:
```
[product_display id="7"]
```

**Random product with AJAX:**
```
[random_product]
```
Renders a "Load Random Product" button. On click, fetches a random product, creates a CPT post, and displays the card with a link to the post — all without a page reload.

---

## Project Structure

```
wp-product-plugin/
├── wp-product-plugin.php                        # Plugin entry point, constants, hooks
├── uninstall.php                                # Clean-up on plugin deletion
├── includes/
│   ├── class-wp-product.php                    # Product model (DTO)
│   ├── class-wp-product-renderer.php           # Product card HTML renderer
│   ├── class-wp-product-plugin.php             # Main class — wires dependencies
│   ├── class-wp-product-plugin-api.php         # FakeStore API client + cache
│   ├── class-wp-product-plugin-cpt.php         # Custom Post Type registration & CRUD
│   ├── class-wp-product-plugin-ajax.php        # AJAX handler (rate-limited)
│   ├── class-wp-product-plugin-activator.php   # Activation logic
│   └── class-wp-product-plugin-deactivator.php # Deactivation logic
├── admin/
│   └── class-wp-product-plugin-admin.php       # Settings page
├── public/
│   └── class-wp-product-plugin-shortcodes.php  # Shortcode registration
├── assets/
│   ├── css/
│   │   ├── public.css                          # Base styles
│   │   └── enhanced.css                        # Enhanced card styles (optional)
│   └── js/
│       └── public.js                           # AJAX button logic
├── languages/                                  # Translation files (.pot)
├── docker-compose.yml                          # Local development environment
└── .github/workflows/release.yml              # CI/CD — creates ZIP on tag push
```

---

## Architecture

The plugin follows the **Single Responsibility Principle** with strict separation of concerns:

| Class | Responsibility |
|-------|---------------|
| `WP_Product` | Typed Product DTO — single definition of product shape |
| `WP_Product_Renderer` | HTML rendering only — used by both Shortcodes and AJAX |
| `WP_Product_Plugin_API` | HTTP requests to FakeStore API + transient cache |
| `WP_Product_Plugin_CPT` | CPT registration and post CRUD |
| `WP_Product_Plugin_AJAX` | Request validation, rate limiting, JSON response |
| `WP_Product_Plugin_Shortcodes` | Shortcode registration and delegation to renderer |
| `WP_Product_Plugin_Admin` | Settings page and timestamp tracking |

The main class (`WP_Product_Plugin`) is a **Singleton** that wires all dependencies together. It bootstraps on the `plugins_loaded` hook.

---

## WordPress Hooks

**Action fired by this plugin:**

```php
// Fired after a new product CPT post is created.
// Hook into this to react to product creation without modifying the plugin.
do_action( 'wp_product_plugin_product_created', int $post_id, WP_Product $product );
```

**Example:**
```php
add_action( 'wp_product_plugin_product_created', function( $post_id, $product ) {
    // e.g. send a notification, log to analytics, etc.
}, 10, 2 );
```

---

## Security

| Measure | Where applied |
|---------|--------------|
| Nonce verification (`check_ajax_referer`) | AJAX endpoint |
| `current_user_can('manage_options')` + `wp_die()` | Admin settings page |
| `absint()`, `sanitize_text_field()`, `floatval()` | All data from API before DB write |
| `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` | All output in templates |
| URL scheme validation | Before `download_url()` on API image URLs (SSRF prevention) |
| Per-IP rate limiting (transient-based) | `nopriv` AJAX endpoint |
| `wp_delete_file()` | Temp file cleanup after image sideload |
| `ABSPATH` guard | Every PHP file |
| `WP_UNINSTALL_PLUGIN` guard | `uninstall.php` |
| Batched deletion (100/pass) | Uninstall routine |

---

## Releasing

1. Bump the version in `wp-product-plugin.php` and `CHANGELOG.md`.
2. Commit and push.
3. Push a tag:
   ```bash
   git tag -a v1.2.0 -m "Release v1.2.0"
   git push origin v1.2.0
   ```
4. GitHub Actions builds a ZIP archive and creates a GitHub Release automatically.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full version history.

---

## License

GPL v2 or later. See [LICENSE](LICENSE) for the full text.
