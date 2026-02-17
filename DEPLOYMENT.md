# Deployment Guide

This guide covers deploying WP Product Plugin to production or creating a release.

## Pre-Deployment Checklist

### Code Quality
- [ ] All PHP files pass syntax check
- [ ] WordPress Coding Standards followed
- [ ] No PHP errors or warnings
- [ ] JavaScript code tested in multiple browsers
- [ ] CSS validated and responsive

### Security
- [ ] All user inputs sanitized
- [ ] All outputs escaped
- [ ] Nonce verification on AJAX requests
- [ ] Capability checks on admin pages
- [ ] No SQL injection vulnerabilities
- [ ] Direct file access blocked (ABSPATH checks)

### Functionality
- [ ] Plugin activates without errors
- [ ] Plugin deactivates cleanly
- [ ] Uninstall script removes all data
- [ ] Both shortcodes work correctly
- [ ] AJAX functionality working
- [ ] CPT posts created successfully
- [ ] Images download and save properly
- [ ] Settings page functional
- [ ] Last created timestamp updates

### Documentation
- [ ] README.md complete
- [ ] CHANGELOG.md updated
- [ ] Inline code comments (PHPDoc)
- [ ] Usage examples provided
- [ ] TESTING.md guide available

### Performance
- [ ] API responses cached (transients)
- [ ] No memory leaks
- [ ] Assets load conditionally
- [ ] Images optimized
- [ ] Database queries optimized

## Creating a Release

### 1. Update Version Numbers

Update version in these files:

**wp-product-plugin.php:**
```php
* Version: 1.0.0
define( 'WP_PRODUCT_PLUGIN_VERSION', '1.0.0' );
```

**README.md:**
Update version badges and requirements if changed.

**CHANGELOG.md:**
Add new version section with changes.

### 2. Commit Changes

```bash
git add .
git commit -m "Prepare v1.0.0 release"
git push origin main
```

### 3. Create Git Tag

```bash
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

### 4. GitHub Actions Workflow

Once tag is pushed, GitHub Actions will automatically:
1. Create plugin ZIP file
2. Create GitHub Release
3. Upload ZIP as release asset

### 5. Verify Release

1. Go to GitHub → Releases
2. Verify ZIP file is attached
3. Download and test ZIP installation
4. Check release notes are correct

## Manual ZIP Creation

If you need to create a ZIP manually without CI/CD:

```bash
# Create temporary directory
mkdir -p wp-product-plugin-release

# Copy plugin files
rsync -av \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.gitignore' \
  --exclude='.idea' \
  --exclude='.vscode' \
  --exclude='node_modules' \
  --exclude='docker-compose.yml' \
  --exclude='temp' \
  --exclude='.DS_Store' \
  --exclude='*.md' \
  --exclude='LICENSE' \
  --exclude='TESTING.md' \
  --exclude='DEPLOYMENT.md' \
  ./ wp-product-plugin-release/

# Create ZIP
cd wp-product-plugin-release
zip -r ../wp-product-plugin.zip .
cd ..

# Cleanup
rm -rf wp-product-plugin-release

# Verify ZIP contents
unzip -l wp-product-plugin.zip
```

## Installing on Production

### Method 1: WordPress Admin (Recommended)

1. Download `wp-product-plugin.zip` from GitHub Releases
2. Go to WordPress admin → **Plugins** → **Add New**
3. Click **Upload Plugin**
4. Choose downloaded ZIP file
5. Click **Install Now**
6. Click **Activate**
7. Go to **Settings** → **Product Plugin**
8. Configure Product ID
9. Test shortcodes on a page

### Method 2: FTP/SFTP Upload

1. Extract ZIP file locally
2. Upload `wp-product-plugin` folder to `/wp-content/plugins/`
3. Go to WordPress admin → **Plugins**
4. Find "WP Product Plugin"
5. Click **Activate**

### Method 3: WP-CLI

```bash
wp plugin install wp-product-plugin.zip --activate
wp plugin list
```

## Post-Deployment Verification

### 1. Activation Check
```bash
wp plugin list | grep wp-product-plugin
# Should show: active
```

### 2. Database Check
```bash
wp option get wp_product_plugin_settings
# Should return settings array
```

### 3. CPT Check
```bash
wp post-type list | grep product
# Should show product CPT
```

### 4. Frontend Test
1. Create test page with `[product_display]`
2. Create test page with `[random_product]`
3. Verify both work correctly

### 5. Admin Test
1. Access Settings → Product Plugin
2. Change product ID
3. Save settings
4. Verify changes persist

## Rollback Procedure

If issues occur after deployment:

### 1. Via WordPress Admin
1. Go to **Plugins**
2. Deactivate "WP Product Plugin"
3. Delete plugin
4. Install previous version

### 2. Via WP-CLI
```bash
wp plugin deactivate wp-product-plugin
wp plugin delete wp-product-plugin
wp plugin install wp-product-plugin --version=1.0.0 --activate
```

### 3. Database Cleanup (if needed)
```sql
DELETE FROM wp_options WHERE option_name = 'wp_product_plugin_settings';
DELETE FROM wp_posts WHERE post_type = 'product';
DELETE FROM wp_postmeta WHERE post_id IN (SELECT ID FROM wp_posts WHERE post_type = 'product');
```

## Monitoring

### After Deployment

**Check for errors:**
```bash
# WordPress debug log
tail -f /path/to/wp-content/debug.log

# PHP error log
tail -f /var/log/php/error.log

# Web server error log
tail -f /var/log/nginx/error.log
# or
tail -f /var/log/apache2/error.log
```

**Monitor API calls:**
- Check API response times
- Monitor transient cache hit rates
- Watch for API failures

**Database monitoring:**
- Check CPT post count growth
- Monitor database size
- Check for orphaned meta

## Production Environment Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **WordPress**: 5.0 or higher
- **MySQL**: 5.6 or higher
- **Memory**: 64MB+ recommended
- **Disk Space**: 10MB+ for plugin files

### PHP Extensions
- `json` - JSON encoding/decoding
- `curl` or `allow_url_fopen` - API requests
- `gd` or `imagick` - Image processing

### WordPress Configuration
```php
// wp-config.php
define( 'WP_DEBUG', false );
define( 'WP_MEMORY_LIMIT', '128M' );
```

### Server Configuration
- Write permissions on `/wp-content/uploads/`
- Ability to make external HTTP requests
- No firewall blocking fakestoreapi.com

## Troubleshooting Production Issues

### Issue: Plugin won't activate
**Solution:** Check PHP version and WordPress version compatibility

### Issue: Products not loading
**Solution:**
1. Check API connectivity: `curl https://fakestoreapi.com/products/1`
2. Verify transients cache
3. Clear cache and try again

### Issue: Images not downloading
**Solution:**
1. Check uploads directory permissions
2. Verify PHP `allow_url_fopen` or cURL enabled
3. Check available disk space

### Issue: AJAX not working
**Solution:**
1. Check JavaScript console for errors
2. Verify jQuery is loaded
3. Clear browser cache
4. Check nonce validity

## Support After Deployment

For production issues:
1. Check debug logs
2. Review error messages
3. Test in staging environment
4. Create GitHub issue if bug found
5. Roll back if critical

## Maintenance

### Regular Tasks
- [ ] Monitor plugin performance
- [ ] Check for WordPress core updates compatibility
- [ ] Update documentation as needed
- [ ] Review and respond to user issues
- [ ] Check API status periodically

### When to Update
- Security vulnerabilities discovered
- WordPress core major update
- PHP version changes
- New features requested
- Bug fixes needed

---

**Last Updated:** 2026-02-17
**Plugin Version:** 1.0.0
**WordPress Tested:** 6.4+
