# WP Product Plugin

A WordPress plugin that integrates with the FakeStore API to display products via shortcodes with AJAX support and Custom Post Type storage.

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)

## Features

- **FakeStore API Integration** - Fetches product data from [FakeStore API](https://fakestoreapi.com)
- **Two Shortcodes** - Display specific or random products
- **AJAX Support** - Load random products without page reload
- **Custom Post Type** - Save products as CPT with metadata
- **Automatic Image Download** - Downloads and sets featured images
- **Admin Settings Page** - Configure default product ID
- **Last Created Tracking** - Shows when last product was created
- **WordPress Best Practices** - Follows WordPress Plugin Handbook standards
- **Security First** - Nonce verification, sanitization, and escaping
- **Responsive Design** - Mobile-friendly product cards

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Installation

### From Release (Recommended)

1. Download the latest `wp-product-plugin.zip` from [Releases](https://github.com/sorkin19822/wordpress-test-task/releases)
2. Go to WordPress admin → **Plugins** → **Add New** → **Upload Plugin**
3. Choose the downloaded ZIP file
4. Click **Install Now**
5. Click **Activate**

### From Source (Development)

1. Clone this repository:
   ```bash
   git clone https://github.com/sorkin19822/wordpress-test-task.git
   ```

2. Start Docker environment:
   ```bash
   cd wp-product-plugin
   docker-compose up -d
   ```

3. Access WordPress at http://localhost:8080
4. Complete WordPress installation
5. Activate the plugin from WordPress admin

## Usage

### Configuration

1. Go to **Settings** → **Product Plugin**
2. Set the default **Product ID** (1-20)
3. View last product creation timestamp
4. See shortcode examples

### Shortcode #1: Display Product

Displays a specific product from FakeStore API.

**Basic usage** (uses product ID from settings):
```
[product_display]
```

**With custom product ID**:
```
[product_display id="5"]
```

### Shortcode #2: Random Product with AJAX

Displays a button that loads a random product via AJAX, creates a CPT post, and shows a link to it.

```
[random_product]
```

## Development

### Docker Environment

The plugin includes a Docker Compose setup for local development:

```bash
docker-compose up -d
```

Services:
- **WordPress**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081

### Project Structure

```
wp-product-plugin/
├── admin/
│   └── class-wp-product-plugin-admin.php    # Admin settings page
├── assets/
│   ├── css/
│   │   └── public.css                        # Frontend styles
│   └── js/
│       └── public.js                         # AJAX functionality
├── includes/
│   ├── class-wp-product-plugin.php           # Main plugin class
│   ├── class-wp-product-plugin-activator.php # Activation handler
│   ├── class-wp-product-plugin-deactivator.php # Deactivation handler
│   ├── class-wp-product-plugin-api.php       # FakeStore API wrapper
│   ├── class-wp-product-plugin-cpt.php       # Custom Post Type handler
│   └── class-wp-product-plugin-ajax.php      # AJAX handler
├── public/
│   └── class-wp-product-plugin-shortcodes.php # Shortcodes handler
├── .github/
│   └── workflows/
│       └── release.yml                        # CI/CD pipeline
├── docker-compose.yml                         # Docker environment
├── uninstall.php                              # Uninstall cleanup
└── wp-product-plugin.php                      # Main plugin file
```

### Code Standards

- Follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Uses WordPress core functions (wp_remote_get, not cURL)
- Implements proper security (nonces, sanitization, escaping)
- Object-oriented architecture with separation of concerns
- PHPDoc comments for all classes and methods

### Creating a Release

1. Update version in `wp-product-plugin.php`
2. Update `CHANGELOG.md`
3. Commit changes
4. Create and push a tag:
   ```bash
   git tag -a v1.0.0 -m "Release version 1.0.0"
   git push origin v1.0.0
   ```
5. GitHub Actions will automatically create a release with ZIP file

## API Reference

### FakeStore API

This plugin uses the [FakeStore API](https://fakestoreapi.com) which provides:
- 20 sample products
- Product details: title, price, description, category, image, rating
- Free and no authentication required

### WordPress Hooks

**Actions:**
- `wp_product_plugin_product_created` - Fired after a product CPT is created
- `wp_product_plugin_before_api_request` - Fired before API request
- `wp_product_plugin_after_api_request` - Fired after API request

**Filters:**
- `wp_product_plugin_product_data` - Modify product data before saving
- `wp_product_plugin_api_cache_time` - Modify API cache expiration time

## Security

- **Nonce Verification**: All AJAX requests use WordPress nonces
- **Input Sanitization**: All user inputs are sanitized
- **Output Escaping**: All outputs are properly escaped
- **Capability Checks**: Admin features require `manage_options` capability
- **Direct Access Prevention**: All PHP files check for `ABSPATH`

## Troubleshooting

### Product not loading

1. Check FakeStore API status: https://fakestoreapi.com/products/1
2. Verify product ID is between 1-20
3. Check WordPress debug log

### AJAX not working

1. Verify JavaScript console for errors
2. Check that jQuery is loaded
3. Clear browser cache
4. Ensure nonce is valid

### Images not downloading

1. Check WordPress upload directory permissions
2. Verify server can connect to external URLs
3. Check PHP memory limit

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

This plugin is licensed under the GPL v2 or later.

> This program is free software; you can redistribute it and/or modify
> it under the terms of the GNU General Public License as published by
> the Free Software Foundation; either version 2 of the License, or
> (at your option) any later version.
>
> This program is distributed in the hope that it will be useful,
> but WITHOUT ANY WARRANTY; without even the implied warranty of
> MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
> GNU General Public License for more details.

## Credits

- **FakeStore API**: https://fakestoreapi.com
- **WordPress Plugin Handbook**: https://developer.wordpress.org/plugins/
- **Icons**: WordPress Dashicons

## Support

For bug reports and feature requests, please use [GitHub Issues](https://github.com/sorkin19822/wordpress-test-task/issues).

## Author

Oleksandr - WordPress Developer test assignment.

---

**Note**: This plugin is for demonstration purposes and uses the FakeStore API which provides sample data only.
