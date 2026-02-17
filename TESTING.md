# Testing Guide

This document outlines the testing procedures for WP Product Plugin.

## Pre-Testing Setup

### 1. Start Docker Environment

```bash
docker-compose up -d
```

### 2. Access WordPress

- **WordPress**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081 (user: `root`, password: `rootpassword`)

### 3. Complete WordPress Installation

1. Visit http://localhost:8080
2. Select language
3. Fill in site information:
   - Site Title: "Test Site"
   - Username: `admin`
   - Password: (choose a strong password)
   - Email: your@email.com
4. Click "Install WordPress"

### 4. Activate Plugin

1. Go to **Plugins** → **Installed Plugins**
2. Find "WP Product Plugin"
3. Click "Activate"

## Test Cases

### Test 1: Plugin Activation

**Steps:**
1. Activate the plugin
2. Check for any PHP errors
3. Verify CPT "Products" appears in admin menu
4. Check Settings → Product Plugin menu exists

**Expected Results:**
- ✅ No errors during activation
- ✅ Products menu item appears
- ✅ Settings page accessible
- ✅ Default settings created (product_id = 1)

---

### Test 2: Settings Page

**Steps:**
1. Go to **Settings** → **Product Plugin**
2. Verify default Product ID is 1
3. Change Product ID to 5
4. Click "Save Settings"
5. Try invalid ID (e.g., 25)
6. Try invalid ID (e.g., 0)

**Expected Results:**
- ✅ Settings page loads without errors
- ✅ Product ID 5 saves successfully
- ✅ Invalid IDs show error message
- ✅ Last Created shows "No products created yet"
- ✅ Shortcode examples displayed

---

### Test 3: Shortcode #1 - Product Display

**Steps:**
1. Create new page: **Pages** → **Add New**
2. Title: "Test Product Display"
3. Add shortcode: `[product_display]`
4. Publish page
5. View page on frontend

**Expected Results:**
- ✅ Product loads from FakeStore API
- ✅ Product image displays
- ✅ Product title shows
- ✅ Price displays correctly
- ✅ Category shows
- ✅ Rating displays
- ✅ Description shows
- ✅ No PHP errors in debug log

**Test 3b: Custom Product ID**

**Steps:**
1. Edit the same page
2. Change shortcode to: `[product_display id="10"]`
3. Update and view page

**Expected Results:**
- ✅ Different product loads (ID 10)
- ✅ All product details display correctly

---

### Test 4: Shortcode #2 - Random Product (AJAX)

**Steps:**
1. Create new page: **Pages** → **Add New**
2. Title: "Test Random Product"
3. Add shortcode: `[random_product]`
4. Publish page
5. View page on frontend
6. Click "Load Random Product" button
7. Click button multiple times

**Expected Results:**
- ✅ Button appears on page
- ✅ Clicking button shows loading state
- ✅ Random product loads via AJAX
- ✅ Product card displays with all details
- ✅ "View Product Post" link appears
- ✅ Clicking multiple times loads different products
- ✅ No page reload occurs
- ✅ No JavaScript errors in console

---

### Test 5: Custom Post Type Creation

**Steps:**
1. Go to the Random Product page from Test 4
2. Click "Load Random Product" button
3. Note the product title
4. Go to WordPress admin → **Products**
5. Find the product in CPT list
6. Click to view/edit the product

**Expected Results:**
- ✅ CPT post created with product title
- ✅ Product description in post content
- ✅ Featured image set correctly
- ✅ Custom fields saved:
  - `_product_api_id`
  - `_product_price`
  - `_product_category`
  - `_product_rating_rate`
  - `_product_rating_count`

---

### Test 6: Duplicate Prevention

**Steps:**
1. Note a product's API ID from Test 5
2. Go to Random Product page
3. Click button until you get the same product again
4. Check Products CPT in admin

**Expected Results:**
- ✅ No duplicate posts created
- ✅ Same post ID returned for duplicate product
- ✅ Only one CPT entry for each unique API ID

---

### Test 7: Last Created Timestamp

**Steps:**
1. Create a product via Random Product shortcode
2. Note the current time
3. Go to **Settings** → **Product Plugin**
4. Check "Last Product Created" value

**Expected Results:**
- ✅ Timestamp shows recent time
- ✅ Time ago displays (e.g., "2 minutes ago")
- ✅ Timestamp updates after creating new product

---

### Test 8: Product Post Link

**Steps:**
1. Load random product on frontend
2. Click "View Product Post" link
3. Verify single product page loads

**Expected Results:**
- ✅ Link directs to single product page
- ✅ Product title displays
- ✅ Product content shows
- ✅ Featured image appears
- ✅ URL structure is clean

---

### Test 9: API Error Handling

**Steps:**
1. Edit `includes/class-wp-product-plugin-api.php`
2. Temporarily change API URL to invalid URL
3. Try loading product with shortcode
4. Restore correct URL

**Expected Results:**
- ✅ Error message displays (not white screen)
- ✅ Error is user-friendly
- ✅ No PHP fatal errors

---

### Test 10: Security Tests

**Test 10a: Nonce Verification**

**Steps:**
1. Open browser DevTools → Network tab
2. Load Random Product page
3. Click "Load Random Product"
4. Check AJAX request parameters
5. Try repeating request with old nonce

**Expected Results:**
- ✅ Nonce parameter present in AJAX request
- ✅ Old nonce rejected (security error)

**Test 10b: Direct File Access**

**Steps:**
1. Try accessing: http://localhost:8080/wp-content/plugins/wp-product-plugin/includes/class-wp-product-plugin-api.php

**Expected Results:**
- ✅ Access denied / blank page (not PHP code visible)

**Test 10c: Capability Check**

**Steps:**
1. Create new user with Subscriber role
2. Login as that user
3. Try accessing Settings → Product Plugin

**Expected Results:**
- ✅ Settings page not accessible for non-admin users

---

### Test 11: Responsive Design

**Steps:**
1. View product display page
2. Resize browser to mobile width (375px)
3. Check tablet width (768px)
4. Check desktop (1200px)

**Expected Results:**
- ✅ Product cards responsive on all screen sizes
- ✅ Images scale properly
- ✅ Text readable on mobile
- ✅ Button accessible on mobile

---

### Test 12: Caching

**Steps:**
1. Load a product (e.g., ID 5)
2. Check database for transient:
   ```sql
   SELECT * FROM wp_options WHERE option_name LIKE '%product_5%';
   ```
3. Load same product again
4. Verify faster loading (cached)

**Expected Results:**
- ✅ Transient created after first load
- ✅ Subsequent loads use cached data
- ✅ Cache expires after 1 hour

---

### Test 13: Plugin Deactivation

**Steps:**
1. Go to **Plugins**
2. Deactivate "WP Product Plugin"
3. Check Products CPT accessibility
4. Reactivate plugin

**Expected Results:**
- ✅ Deactivates without errors
- ✅ CPT posts still exist but hidden
- ✅ Reactivation restores functionality

---

### Test 14: Plugin Uninstallation

**Steps:**
1. Deactivate plugin
2. Click "Delete"
3. Confirm deletion
4. Check database for:
   - Options (should be deleted)
   - CPT posts (should be deleted)

**Expected Results:**
- ✅ Plugin files deleted
- ✅ Settings option deleted
- ✅ All CPT posts deleted
- ✅ Transients cleared
- ✅ Clean uninstall

---

### Test 15: Multiple Shortcodes on Same Page

**Steps:**
1. Create new page
2. Add both shortcodes:
   ```
   [product_display id="1"]
   [product_display id="5"]
   [random_product]
   ```
3. View page

**Expected Results:**
- ✅ All shortcodes work simultaneously
- ✅ No JavaScript conflicts
- ✅ AJAX works for random product
- ✅ Static products display correctly

---

### Test 16: Cross-Browser Testing

**Browsers to Test:**
- Chrome/Chromium
- Firefox
- Safari (if available)
- Edge

**Expected Results:**
- ✅ All functionality works in all browsers
- ✅ AJAX works correctly
- ✅ Styling consistent
- ✅ No console errors

---

## Performance Tests

### Test P1: Page Load Time

**Steps:**
1. Use browser DevTools → Performance
2. Record page load with product shortcode
3. Check total time and API request time

**Expected Results:**
- ✅ Page loads in < 2 seconds
- ✅ API request completes in < 1 second
- ✅ No render-blocking resources

### Test P2: Memory Usage

**Steps:**
1. Check PHP memory before activation
2. Activate plugin
3. Create 10 products
4. Check memory usage

**Expected Results:**
- ✅ Memory usage reasonable (< 50MB increase)
- ✅ No memory leaks

---

## Automated Testing Checklist

- [ ] PHP Syntax Check: `find . -name "*.php" -exec php -l {} \;`
- [ ] WordPress Coding Standards (if PHPCS installed)
- [ ] JavaScript Linting (if ESLint installed)
- [ ] CSS Validation

---

## Known Issues / Edge Cases

1. **FakeStore API Limits**: API has 20 products, IDs beyond 20 return errors (handled)
2. **Image Download**: Requires write permissions to uploads folder
3. **API Availability**: Plugin depends on external API availability

---

## Test Environment Cleanup

After testing:

```bash
# Stop containers
docker-compose down

# Remove volumes (complete cleanup)
docker-compose down -v

# Remove test data
docker volume rm wp-product-plugin_db_data wp-product-plugin_wp_data
```

---

## Test Sign-Off

**Tester:** _________________
**Date:** _________________
**Version Tested:** _________________
**Result:** ✅ PASS / ❌ FAIL
**Notes:** _________________
