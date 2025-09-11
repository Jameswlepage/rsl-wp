# RSL Licensing for WordPress - Installation Guide

This guide will help you install and configure the RSL Licensing plugin for WordPress.

## System Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher (or MariaDB equivalent)
- Web server with rewrite capability (Apache/Nginx)

## Installation Methods

### Method 1: Manual Installation (Recommended)

1. **Download the Plugin**
   - Download the plugin files from the repository
   - Extract the ZIP file to get the `rsl-licensing` folder

2. **Upload to WordPress**
   ```bash
   # Via FTP/SFTP
   Upload the entire rsl-licensing folder to:
   /wp-content/plugins/
   
   # Via WordPress Admin
   - Go to Plugins > Add New > Upload Plugin
   - Choose the ZIP file and click "Install Now"
   ```

3. **Activate the Plugin**
   - Go to Plugins in WordPress admin
   - Find "RSL Licensing for WordPress"
   - Click "Activate"

### Method 2: WP-CLI Installation

```bash
# Navigate to WordPress directory
cd /path/to/wordpress

# Download and install plugin
wp plugin install rsl-licensing.zip --activate

# Or if installing from directory
wp plugin activate rsl-licensing
```

## Initial Configuration

### Step 1: Access Plugin Settings

After activation, navigate to:
**WordPress Admin > Settings > RSL Licensing**

### Step 2: Create Your First License

1. Go to **Settings > Add RSL License**
2. Fill in the required information:

   **Basic Information:**
   - **Name**: `Site Content License` (or your preferred name)
   - **Description**: Optional description of the license
   - **Content URL**: `/` (for entire site) or specific path like `/blog/`

   **Permissions:**
   - **Permitted Usage**: Select allowed uses (e.g., `search` for search engines)
   - **Prohibited Usage**: Select forbidden uses (e.g., `train-ai` to block AI training)

   **Payment & Terms:**
   - **Payment Type**: Choose from free, purchase, subscription, etc.
   - **Amount**: Set pricing if applicable
   - **Standard URL**: Link to standard license (e.g., Creative Commons)

3. Click **Create License**

### Step 3: Configure Global Settings

Back in **Settings > RSL Licensing**:

1. **Select Global License**: Choose the license you just created
2. **Enable Integration Methods**:
   - ✅ HTML head injection
   - ✅ HTTP Link headers
   - ✅ robots.txt integration
   - ✅ RSS feed enhancement
   - ✅ Media metadata embedding

3. Click **Save Changes**

## Verification Steps

### 1. Check HTML Integration

View your site's source code and look for:
```html
<!-- RSL Licensing Information -->
<script type="application/rsl+xml">
<rsl xmlns="https://rslstandard.org/rsl">
  <!-- License content -->
</rsl>
</script>
<!-- End RSL Licensing Information -->
```

### 2. Verify robots.txt

Visit `yoursite.com/robots.txt` and check for:
```
# RSL Licensing Directive
License: https://yoursite.com/?rsl_license=1
```

### 3. Test RSS Integration

Visit your RSS feed (`yoursite.com/feed/`) and look for `xmlns:rsl` namespace declaration.

### 4. Check RSL Feed

Visit `yoursite.com/?rsl_feed=1` to see your dedicated RSL license feed.

## Advanced Configuration

### Custom URL Patterns

RSL supports flexible URL patterns:

- `/` - Entire site
- `/blog/` - Specific directory
- `/images/*` - All files in images directory
- `*.pdf` - All PDF files
- `/api/*$` - API endpoints only

### Multiple Licenses

Create different licenses for different content:

1. **Blog Posts**: `/blog/` with attribution requirements
2. **Images**: `/wp-content/uploads/` with commercial licensing
3. **API**: `/wp-json/` with usage restrictions

### Per-Post Licensing

Override global licenses on specific posts:

1. Edit any post/page
2. Find the "RSL License" meta box in the sidebar
3. Select a different license or set custom content URL
4. Save the post

## Integration with License Servers

### RSL Collective Integration

1. Sign up at [RSL Collective](https://rslcollective.org)
2. Get your API endpoint URL
3. In your license configuration:
   - Set **Server URL**: `https://rslcollective.org/api`
   - Configure payment terms
4. Save license

### Custom License Server

If you have your own RSL License Server:

1. Set **Server URL** to your server endpoint
2. Configure authentication as needed
3. Test with `.well-known/rsl/` endpoint

## Media File Processing

### Automatic Processing

New uploads are automatically processed if media metadata is enabled:

1. Upload images, PDFs, or EPUB files
2. Check Media Library for "RSL License" column
3. RSL metadata is embedded in supported formats

### Bulk Processing

For existing media files:

1. Go to Media Library
2. Edit individual files to assign licenses
3. Re-upload to trigger metadata embedding

### Supported Formats

- **Images**: JPEG, PNG, TIFF, WebP (XMP metadata)
- **Documents**: PDF (companion .rsl.xml files)
- **eBooks**: EPUB (OPF manifest integration)

## Troubleshooting Installation

### Plugin Not Activating

**Error: Missing Dependencies**
- Ensure PHP 7.4+ is installed
- Check WordPress version (5.0+ required)

**Error: Database Issues**
- Verify MySQL permissions
- Check wp-config.php database settings

### Settings Not Saving

**Permission Issues**
- Check file/folder permissions (755 for directories, 644 for files)
- Ensure WordPress can write to wp-content

**Plugin Conflicts**
- Deactivate other plugins temporarily
- Test with default WordPress theme

### License Not Appearing

**HTML Not Injected**
- Check if theme calls `wp_head()`
- Verify HTML injection is enabled
- Test with default theme

**robots.txt Not Updated**
- Ensure pretty permalinks are enabled
- Check .htaccess permissions
- Verify robots.txt integration is enabled

## Performance Optimization

### Caching Compatibility

RSL works with most caching plugins:

1. **WP Rocket**: No special configuration needed
2. **W3 Total Cache**: Exclude RSL query parameters
3. **LiteSpeed Cache**: Works out of the box

### Database Optimization

RSL creates minimal database load:
- One additional table for licenses
- Efficient queries with proper indexing
- Post meta used sparingly

## Security Considerations

### File Permissions

```bash
# Set proper permissions
chmod 755 wp-content/plugins/rsl-licensing/
chmod 644 wp-content/plugins/rsl-licensing/*.php
```

### Server Configuration

#### Apache (.htaccess)

The plugin will work with default WordPress .htaccess rules.

#### Nginx

Add to your server block:
```nginx
# RSL License Server endpoints
location ~ ^/rsl-license/([0-9]+)/?$ {
    try_files $uri $uri/ /index.php?rsl_license_id=$1;
}

location /.well-known/rsl/ {
    try_files $uri $uri/ /index.php?rsl_wellknown=1;
}
```

## Multisite Installation

### Network Activation

1. Upload plugin to `/wp-content/plugins/`
2. Go to Network Admin > Plugins
3. Click "Network Activate" for RSL Licensing

### Per-Site Configuration

Each site can have its own licenses:
- Individual license configurations
- Separate global settings
- Independent media processing

### Shared Licenses

To share licenses across sites:
1. Create licenses on main site
2. Export configuration
3. Import on other sites

## Migration from Other Systems

### From robots.txt Only

If you currently only use robots.txt:

1. Install RSL plugin
2. Create licenses matching your robots.txt rules
3. RSL will enhance robots.txt with License directive
4. Remove manual robots.txt entries

### From Custom Implementation

If you have custom license handling:

1. Export current license data
2. Create corresponding RSL licenses
3. Map URL patterns to RSL content URLs
4. Test thoroughly before removing old system

## Backup Considerations

### What to Backup

- RSL license database table (`wp_rsl_licenses`)
- Plugin settings (WordPress options)
- Media files with embedded metadata

### Backup Commands

```bash
# Database backup (MySQL)
mysqldump -u username -p database_name wp_rsl_licenses > rsl_licenses.sql

# WordPress export (includes settings)
wp db export --tables=wp_rsl_licenses,wp_options

# Media files backup
tar -czf media_backup.tar.gz wp-content/uploads/
```

## Post-Installation Checklist

- [ ] Plugin activated successfully
- [ ] First license created and configured
- [ ] Global license selected in settings
- [ ] Integration methods enabled
- [ ] HTML injection verified in source code
- [ ] robots.txt updated with RSL directive
- [ ] RSS feed includes RSL namespace
- [ ] Media metadata embedding tested
- [ ] License XML accessible via direct URL
- [ ] RSL feed accessible and valid
- [ ] No errors in WordPress admin or site frontend

## Getting Help

### Common Resources

- **Plugin Settings**: Check all configuration options
- **WordPress Debug**: Enable WP_DEBUG for error details
- **Server Logs**: Check Apache/Nginx error logs
- **Browser Console**: Look for JavaScript errors

### Support Channels

- **Documentation**: Read full README.md
- **GitHub Issues**: Report bugs and request features
- **WordPress Forums**: Community support
- **RSL Standard**: Official specification documentation

---

**Installation Complete!** Your WordPress site now supports the RSL standard for machine-readable content licensing.