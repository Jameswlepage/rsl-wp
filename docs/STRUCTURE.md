# RSL Licensing Plugin - File Structure

This document outlines the complete file structure for the RSL Licensing WordPress plugin, organized for WordPress.org repository submission.

## Root Level Files

```
rsl-wp.php           # Main plugin file with header and initialization
readme.txt                  # WordPress.org plugin readme (required)
README.md                   # GitHub documentation
INSTALL.md                  # Installation guide
CHANGELOG.md               # Version history
STRUCTURE.md               # This file
```

## WordPress.org Assets (/assets/)

These files are used by the WordPress.org plugin directory:

```
assets/
├── banner-772x250.png     # Plugin banner (low resolution)
├── banner-1544x500.png    # Plugin banner (high resolution/retina)
├── icon-128x128.png       # Plugin icon (small)
├── icon-256x256.png       # Plugin icon (large/retina)
├── screenshot-1.png       # Main settings page
├── screenshot-2.png       # License management
├── screenshot-3.png       # License editor
├── screenshot-4.png       # Post meta box
├── screenshot-5.png       # Media library integration
└── screenshot-6.png       # XML generation modal
```

## Core Functionality (/includes/)

Core plugin classes containing the main functionality:

```
includes/
├── class-rsl-license.php   # License CRUD operations and XML generation
├── class-rsl-admin.php     # WordPress admin interface integration
├── class-rsl-frontend.php  # Public-facing functionality and HTML injection
├── class-rsl-robots.php    # robots.txt integration and AI Preferences
├── class-rsl-rss.php       # RSS feed enhancement and RSL feeds
├── class-rsl-media.php     # Media file metadata embedding
└── class-rsl-server.php    # License server and REST API functionality
```

## Admin Interface (/admin/)

WordPress admin-specific files:

```
admin/
├── css/
│   └── admin.css          # Admin interface styling
├── js/
│   └── admin.js           # Admin JavaScript functionality
└── templates/
    ├── admin-settings.php  # Main settings page template
    ├── admin-licenses.php  # License management page template
    └── admin-add-license.php # License creation/editing form
```

## Public Assets (/public/)

Public-facing assets (currently empty, reserved for future frontend features):

```
public/
├── css/                   # Public CSS files (if needed)
└── js/                    # Public JavaScript files (if needed)
```

## WordPress.org Submission Structure

For WordPress.org repository submission, this plugin follows the standard structure:

### Required Files:
- ✅ `readme.txt` - WordPress.org plugin readme
- ✅ `{plugin-name}.php` - Main plugin file with proper header
- ✅ `assets/` directory with banners, icons, and screenshots

### Recommended Structure:
- ✅ `includes/` - Core plugin classes
- ✅ `admin/` - Admin-specific functionality
- ✅ `public/` - Public-facing functionality
- ✅ Proper file organization and naming conventions

### Asset Requirements Met:
- ✅ Plugin banners: 772x250px and 1544x500px (PNG format)
- ✅ Plugin icons: 128x128px and 256x256px (PNG format) 
- ✅ Screenshots: Up to 6 screenshots showing key features
- ✅ All assets follow WordPress.org naming conventions

## File Purpose Summary

| File/Directory | Purpose |
|----------------|---------|
| `rsl-wp.php` | Main plugin bootstrap and initialization |
| `readme.txt` | WordPress.org plugin information and installation guide |
| `includes/` | Core plugin functionality and business logic |
| `admin/` | WordPress admin interface integration |
| `assets/` | WordPress.org plugin directory assets |
| `public/` | Public-facing functionality (reserved for future use) |

## Class Architecture

The plugin uses a modular class-based architecture:

- **RSL_Licensing**: Main plugin class (bootstrap)
- **RSL_License**: License management and XML generation
- **RSL_Admin**: WordPress admin integration
- **RSL_Frontend**: Public functionality and HTML injection
- **RSL_Robots**: robots.txt enhancement
- **RSL_RSS**: RSS feed integration
- **RSL_Media**: Media file metadata handling
- **RSL_Server**: License server and API functionality

## WordPress Integration Points

- **Hooks**: Uses proper WordPress action and filter hooks
- **Database**: Single custom table with WordPress prefix
- **Admin**: Integrates with WordPress admin using standard APIs
- **REST API**: Extends WordPress REST API with custom endpoints
- **Rewrite Rules**: Adds custom URL patterns for RSL endpoints
- **Media**: Integrates with WordPress Media Library
- **Post Meta**: Uses standard WordPress post meta for per-post licenses

This structure ensures compatibility with WordPress.org submission requirements while maintaining clean, maintainable code organization.