# RSL Licensing for WordPress

Complete Really Simple Licensing (RSL) support for WordPress sites. Define machine-readable licensing terms for your content, enabling AI companies, crawlers, and other automated systems to properly license your digital assets.

## Features

### Core RSL Implementation
- **Full RSL 1.0 Specification Support** - Complete implementation of the official RSL standard
- **License Management System** - Create, edit, and manage multiple license configurations
- **Global and Per-Post Licensing** - Set site-wide licenses or override for specific content
- **Advanced Permission Controls** - Define permitted/prohibited usage, user types, and geographic restrictions

### Multiple Integration Methods
- **HTML Head Injection** - Automatically embed RSL licenses in page headers
- **HTTP Link Headers** - Add RSL license information to HTTP responses
- **robots.txt Integration** - Extend robots.txt with RSL License directives and AI Preferences compatibility
- **RSS Feed Enhancement** - Add RSL licensing to RSS feeds and create dedicated RSL feeds
- **Media File Metadata** - Embed RSL licenses directly in uploaded images, PDFs, and other media

### Professional Admin Interface
- **Intuitive License Builder** - Visual interface for creating complex licensing terms
- **Live XML Generation** - Preview and download RSL XML files
- **License Validation** - Built-in validation and error checking
- **Bulk Management** - Manage multiple licenses efficiently

### Developer Features
- **REST API Endpoints** - Programmatic access to license data
- **License Server Compatibility** - Integration with RSL License Servers
- **Authentication Support** - Handle license-based authentication
- **Shortcodes** - Display license information anywhere on your site

## Installation

1. Download the plugin files
2. Upload to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin
4. Navigate to Settings > RSL Licensing to configure

## Quick Start

### 1. Create Your First License

1. Go to **Settings > Add RSL License**
2. Fill in basic information:
   - **Name**: "Site Content License"
   - **Content URL**: "/" (for entire site)
   - **Payment Type**: Choose appropriate option
3. Configure permissions as needed
4. Save the license

### 2. Set Global License

1. Navigate to **Settings > RSL Licensing**
2. Select your license from the "Global License" dropdown
3. Enable desired integration methods
4. Save settings

### 3. Verify Implementation

- Check your site's source code for RSL `<script>` tags
- Visit `yoursite.com/robots.txt` to see RSL directives
- Access `yoursite.com/?rsl_feed=1` for your RSL feed

## WooCommerce Integration for Paid Licensing

For paid licensing (purchase, subscription, training fees), install WooCommerce:

### Quick WooCommerce Setup

1. **Install WooCommerce** plugin and complete setup wizard
2. **Create paid license** in Settings > Add RSL License:
   - Set **Payment Type**: "Purchase" or "Subscription"
   - Add **Amount**: e.g., 99.99
   - Set **Server URL**: `https://yoursite.com/wp-json/rsl-olp/v1`
3. **Configure payment gateway** (PayPal, Stripe, etc.) in WooCommerce
4. **Test the flow**: AI companies can now request licensing tokens and complete payments

The plugin automatically creates hidden WooCommerce products and handles the entire payment-to-token flow.

📖 **For detailed setup, pricing models, and business use cases, see [Payment Integration Guide](docs/PAYMENTS.md)**

## License Types & Use Cases

### Free Content
```xml
<rsl xmlns="https://rslstandard.org/rsl">
  <content url="/">
    <license>
      <payment type="free"/>
    </license>
  </content>
</rsl>
```

### AI Training Restrictions
```xml
<rsl xmlns="https://rslstandard.org/rsl">
  <content url="/">
    <license>
      <prohibits type="usage">train-ai,train-genai</prohibits>
      <payment type="free"/>
    </license>
  </content>
</rsl>
```

### Commercial Licensing
```xml
<rsl xmlns="https://rslstandard.org/rsl">
  <content url="/">
    <license>
      <permits type="usage">train-ai</permits>
      <payment type="subscription">
        <amount currency="USD">99.99</amount>
        <custom>https://example.com/licensing</custom>
      </payment>
      <server url="https://yoursite.com/wp-json/rsl-olp/v1"/>
    </license>
  </content>
</rsl>
```

### Attribution Required
```xml
<rsl xmlns="https://rslstandard.org/rsl">
  <content url="/images/">
    <license>
      <permits type="usage">all</permits>
      <payment type="attribution">
        <standard>https://creativecommons.org/licenses/by/4.0/</standard>
      </payment>
    </license>
  </content>
</rsl>
```

## Usage Examples

### Shortcodes

Display license information anywhere:

```php
// Show license link
[rsl_license format="link" text="View License"]

// Display license details
[rsl_license format="info"]

// Show XML (for developers)
[rsl_license format="xml"]
```

### Template Functions

```php
// Check if content has RSL license
if (function_exists('rsl_has_license')) {
    if (rsl_has_license()) {
        echo 'This content is protected by RSL licensing';
    }
}

// Get license information
$license_info = rsl_get_license_info();
if ($license_info) {
    echo 'License: ' . $license_info['name'];
}
```

## Configuration Options

### Global Settings

- **Global License** - Apply a license site-wide
- **HTML Injection** - Embed licenses in page headers
- **HTTP Headers** - Add Link headers to responses
- **robots.txt Integration** - Extend robots.txt with RSL directives
- **RSS Enhancement** - Add RSL to feed items
- **Media Metadata** - Embed licenses in uploaded files

### License Properties

#### Content & Server
- **Content URL** - Path or URL pattern (supports wildcards)
- **Server URL** - Optional RSL License Server
- **Encryption** - Mark content as encrypted

#### Permissions
- **Usage Types** - `all`, `train-ai`, `train-genai`, `ai-use`, `ai-summarize`, `search`
- **User Types** - `commercial`, `non-commercial`, `education`, `government`, `personal`
- **Geographic** - Country/region codes (ISO 3166-1 alpha-2)

#### Payment & Compensation
- **Payment Types** - `free`, `purchase`, `subscription`, `training`, `crawl`, `inference`, `attribution`
- **Amount & Currency** - Pricing information
- **Standard Licenses** - Link to CC, GPL, etc.
- **Custom Terms** - Your licensing page

#### Legal Information
- **Warranties** - What you guarantee
- **Disclaimers** - Legal protections
- **Copyright** - Rights holder information

## Integration Methods

### HTML Head Injection

Automatically adds RSL licenses to your pages:

```html
<script type="application/rsl+xml">
<rsl xmlns="https://rslstandard.org/rsl">
  <content url="">
    <license>
      <payment type="free"/>
    </license>
  </content>
</rsl>
</script>
```

### HTTP Headers

Adds licensing information to HTTP responses:

```
Link: <https://example.com/?rsl_license=1>; rel="license"; type="application/rsl+xml"
```

### robots.txt Enhancement

```
User-agent: *
Allow: /

# RSL Licensing Directive
License: https://example.com/?rsl_license=1

# AI Content Usage Preferences
Content-Usage: train-ai=n
```

### RSS Feeds

Enhanced feeds with RSL namespace:

```xml
<rss xmlns:rsl="https://rslstandard.org/rsl" version="2.0">
  <channel>
    <item>
      <title>Article Title</title>
      <rsl:content url="https://example.com/article">
        <rsl:license>
          <rsl:payment type="free"/>
        </rsl:license>
      </rsl:content>
    </item>
  </channel>
</rss>
```

## Media File Support

### Supported Formats
- **Images** - JPEG, PNG, TIFF, WebP (XMP metadata)
- **Documents** - PDF (companion RSL files)
- **eBooks** - EPUB (OPF metadata)

### Implementation
RSL metadata is embedded using industry-standard methods:
- XMP for images
- Companion .rsl.xml files for documents
- OPF metadata for EPUB files

## API Reference

### REST Endpoints

- `GET /wp-json/rsl/v1/licenses` - List all licenses
- `GET /wp-json/rsl/v1/licenses/{id}` - Get specific license
- `POST /wp-json/rsl/v1/validate` - Validate content licensing

### License Server Endpoints

- `/.well-known/rsl/` - RSL server discovery
- `/rsl-license/{id}/` - Individual license XML
- `/feed/rsl-licenses/` - RSL license feed

## Advanced Features

### License Server Integration

Connect to RSL License Servers for:
- License validation and authentication
- Payment processing
- Content encryption/decryption
- Usage tracking and reporting

### Authentication Support

Enforce license requirements:
```
Authorization: License <token>
```

Returns 401 Unauthorized for unlicensed access with proper WWW-Authenticate headers.

### Bulk Operations

- Import/export license configurations
- Apply licenses to existing content
- Batch media processing

## Troubleshooting

### Common Issues

**License not appearing in HTML**
- Check if HTML injection is enabled in settings
- Verify license is active and properly configured
- Ensure global license is selected or post has specific license

**robots.txt not updated**
- Confirm robots.txt integration is enabled
- Check WordPress permalink settings
- Verify global license is configured

**RSS feeds missing RSL data**
- Enable RSS feed integration in settings
- Check that posts have applicable licenses
- Verify feed URLs are working

### Debug Mode

Add to wp-config.php for debugging:
```php
define('RSL_DEBUG', true);
```

This enables additional logging and validation checks.

## RSL 1.0 Specification Compliance

This plugin implements the complete RSL 1.0 draft specification:

### Elements
- `<rsl>`: Root emitted with xmlns `https://rslstandard.org/rsl`
- `<content>`: Supports absolute URLs and server-relative paths per RFC 9309 (wildcards `*` and `$` supported)
- `<license>`: Complete implementation with all sub-elements
- `<permits>` / `<prohibits>`: Usage (`all`, `train-ai`, `train-genai`, `ai-use`, `ai-summarize`, `search`), user types, and geographic restrictions
- `<payment>`: All types supported: `free`, `purchase`, `subscription`, `training`, `crawl`, `inference`, `attribution`, `royalty`
- `<legal>`: Warranties and disclaimers with controlled vocabulary
- `<schema>`: Schema.org CreativeWork integration
- `<copyright>`: Person/organization with contact information
- `<terms>`: Additional legal terms URL

### Integration Methods
- **HTML head**: `<script type="application/rsl+xml">`
- **HTTP Link header**: Standards-compliant Link headers
- **robots.txt**: License directive + AI Preferences compatibility
- **RSS**: RSL namespace module with per-item licensing
- **Media files**: XMP sidecar and companion file embedding

### Compliance & Best Practices
- Full RSL 1.0 specification implementation
- Proper XML namespace usage
- Standard MIME types and headers
- Compatible with RSL License Servers

### Performance Optimization
- Efficient database queries
- Proper caching headers
- Minimal front-end impact
- Optimized media processing

### Security Considerations
- Input validation and sanitization
- Proper nonce usage
- Capability checks
- SQL injection prevention

## Frequently Asked Questions

### Is this compatible with existing copyright notices?
Yes! RSL complements traditional copyright notices by making them machine-readable. You can include copyright holder information in your RSL licenses.

### Can I use this with Creative Commons licenses?
Absolutely! RSL supports standard license references. You can link to CC licenses using the standard URL field.

### Does this work with CDNs?
Yes, RSL licenses are embedded in HTML and HTTP headers, so they work with most CDN configurations.

### Can I license different parts of my site differently?
Yes! You can create multiple licenses with different URL patterns, or override the global license on specific posts/pages.

### Is this compatible with caching plugins?
Yes, RSL works with caching plugins. The licensing information is embedded during page generation.

## Contributing

We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Follow WordPress coding standards
4. Test thoroughly
5. Submit a pull request

## Support

- **Documentation**: [RSL Standard Website](https://rslstandard.org)
- **Issues**: Report bugs via GitHub Issues
- **Community**: Join RSL discussions

## License

This plugin is licensed under GPL v2 or later, allowing you to freely use, modify, and redistribute it.

## Changelog

### 1.0.0
- Initial release
- Full RSL 1.0 specification support
- WordPress admin interface
- Multiple integration methods
- Media file support
- License server compatibility

---

**Made for the open web and fair AI training practices.**