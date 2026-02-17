# Quick Start Guide

Get up and running with WP Product Plugin in 5 minutes.

## For Developers (Testing Locally)

### 1. Start Docker Environment

```bash
cd wp-product-plugin
docker-compose up -d
```

### 2. Complete WordPress Setup

1. Open browser: http://localhost:8080
2. Select language → English
3. Fill in WordPress installation form:
   - Site Title: **Test Site**
   - Username: **admin**
   - Password: **(create strong password)**
   - Email: **your@email.com**
4. Click **Install WordPress**
5. Login with your credentials

### 3. Activate Plugin

1. Go to **Plugins** → **Installed Plugins**
2. Find **WP Product Plugin**
3. Click **Activate**

### 4. Configure Settings

1. Go to **Settings** → **Product Plugin**
2. Set **Product ID** to `1` (or any number 1-20)
3. Click **Save Settings**

### 5. Test Shortcode #1

1. Go to **Pages** → **Add New**
2. Title: **Product Test**
3. Add block → **Shortcode**
4. Enter: `[product_display]`
5. Click **Publish**
6. Click **View Page**
7. You should see a product card with image, title, price, etc.

### 6. Test Shortcode #2 (AJAX)

1. Create another new page
2. Title: **Random Product Test**
3. Add block → **Shortcode**
4. Enter: `[random_product]`
5. Click **Publish**
6. Click **View Page**
7. Click the **Load Random Product** button
8. A random product should load without page refresh
9. Click **View Product Post** link
10. You should see the CPT single page

### 7. Verify CPT Created

1. Go to WordPress admin
2. Click **Products** in the menu
3. You should see the product created by the random shortcode

### 8. Check Last Created Timestamp

1. Go to **Settings** → **Product Plugin**
2. Under "Information" section
3. You should see **Last Product Created** with timestamp

### 9. Done!

Your plugin is working! Check out the full documentation in README.md.

---

## For End Users (Production)

### 1. Download Plugin

1. Go to [GitHub Releases](https://github.com/sorkin19822/wordpress-test-task/releases)
2. Download **wp-product-plugin.zip**

### 2. Install Plugin

1. Login to WordPress admin
2. Go to **Plugins** → **Add New** → **Upload Plugin**
3. Click **Choose File**
4. Select **wp-product-plugin.zip**
5. Click **Install Now**
6. Click **Activate Plugin**

### 3. Configure

1. Go to **Settings** → **Product Plugin**
2. Choose a product ID (1-20)
3. Click **Save Settings**

### 4. Add to Page

**To display a specific product:**
1. Edit any page or post
2. Add a Shortcode block
3. Type: `[product_display]`

**To add random product button:**
1. Edit any page or post
2. Add a Shortcode block
3. Type: `[random_product]`

### 5. Customize Product ID

You can override the default product ID:

```
[product_display id="5"]
```

This will show product #5 instead of the default from settings.

---

## Common Use Cases

### Use Case 1: Display Product in Sidebar

1. Go to **Appearance** → **Widgets**
2. Add **Shortcode** widget to sidebar
3. Enter: `[product_display id="3"]`
4. Save

### Use Case 2: Multiple Products on Same Page

```
[product_display id="1"]
[product_display id="5"]
[product_display id="10"]
```

### Use Case 3: Product Archive Page

1. Create new page: **All Products**
2. Add multiple product displays
3. Or use the random product shortcode
4. Visitors can load different products by clicking the button

### Use Case 4: Homepage Featured Product

1. Edit homepage
2. Add: `[product_display id="8"]`
3. Update page
4. Product appears on homepage

---

## Troubleshooting

### Problem: Shortcode shows as text

**Solution:** Make sure you added the shortcode to a **Shortcode block**, not a paragraph.

### Problem: Product not loading

**Solution:**
1. Check Settings → Product Plugin
2. Verify product ID is between 1-20
3. Check internet connection (plugin needs to reach API)

### Problem: "Load Random Product" button doesn't work

**Solution:**
1. Clear browser cache
2. Check browser console for JavaScript errors (F12)
3. Deactivate other plugins to check for conflicts

### Problem: No images showing

**Solution:**
1. Check that your uploads folder has write permissions
2. Verify PHP can download remote files

---

## Next Steps

- Read full documentation: **README.md**
- Review testing guide: **TESTING.md**
- Check deployment guide: **DEPLOYMENT.md**
- View changelog: **CHANGELOG.md**

---

## Support

- **Issues**: https://github.com/sorkin19822/wordpress-test-task/issues
- **Documentation**: See README.md
- **FakeStore API**: https://fakestoreapi.com

---

## Screenshots

### Settings Page
Configure default product ID and view last created timestamp.

### Product Display
Shows product with image, title, price, category, rating, and description.

### Random Product Button
Click to load random products via AJAX without page reload.

### Custom Post Type
Products saved as CPT with all metadata and featured images.

---

**Happy product displaying! 🎉**
