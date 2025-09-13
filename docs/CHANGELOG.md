# Changelog

All notable changes to the RSL Licensing for WordPress plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.2] - 2025-09-12

### Added
- **OAuth 2.0 Authentication System**
  - Client credentials flow for secure API access
  - JWT token generation and validation with Firebase library support
  - Automatic token revocation on refunds/cancellations
  - Rate limiting protection (30/min tokens, 100/min introspection, 20/min sessions)

- **Enhanced License Server**
  - RSL Open Licensing Protocol (OLP) endpoints
  - Session-based payment flows for complex licensing scenarios
  - Comprehensive error handling with standard OAuth 2.0 responses
  - Token introspection with revocation status checking

- **WooCommerce Integration**
  - Automatic product creation for paid licenses
  - Support for all RSL payment types through WooCommerce gateways
  - Order-based token generation and validation
  - Subscription support with WooCommerce Subscriptions

- **Security Improvements**
  - Enhanced REQUEST_URI sanitization using proper URL parsing
  - CORS origin validation instead of wildcard access
  - Consistent debug logging with rsl_log() function
  - Input validation and error handling improvements

- **Admin Enhancements**
  - WordPress help tabs across all admin pages
  - Improved form data handling to prevent duplicates
  - Dynamic currency symbol display
  - Enhanced error messaging and validation

### Changed
- Improved API error responses to follow OAuth 2.0 standards
- Enhanced token validation with proper expiration and audience checks
- Better rate limiting with informative HTTP headers

### Fixed
- Currency field corruption in admin forms
- Double URL encoding issues in frontend
- Form data duplication in admin interface
- Various security vulnerabilities identified by AI review

## [0.0.1] - 2025-09-11

### Added
- **Core RSL Implementation**
  - Full RSL 1.0 specification support
  - Complete XML generation with proper namespacing
  - Support for all RSL elements: content, license, permits, prohibits, payment, legal, schema, copyright, terms
  - URL pattern matching with wildcard support (* and $)

- **License Management System**
  - Database-backed license storage with full CRUD operations
  - Rich admin interface for creating and managing licenses
  - Support for multiple licenses per site
  - Global and per-post license assignment
  - License validation and error checking

- **Multiple Integration Methods**
  - HTML head injection with `<script type="application/rsl+xml">` tags
  - HTTP Link headers with proper rel="license" and type attributes
  - robots.txt integration with License directive and AI Preferences compatibility
  - RSS feed enhancement with RSL namespace and per-item licensing
  - Dedicated RSL license feeds at `/feed/rsl-licenses/`

- **Admin Interface**
  - Comprehensive settings page with global configuration
  - License list view with search, sort, and bulk actions
  - Advanced license editor with all RSL properties
  - Live XML preview and download functionality
  - Post/page meta boxes for individual license assignment
  - Media library integration with license column

- **Media File Support**
  - XMP metadata embedding for JPEG, PNG, TIFF, WebP images
  - PDF companion RSL files (.rsl.xml)
  - EPUB OPF metadata integration
  - Automatic processing on upload
  - Media library license assignment interface

- **License Server Integration**
  - RSL License Server protocol support
  - License authentication with Authorization: License headers
  - Proper 401 Unauthorized responses with WWW-Authenticate headers
  - REST API endpoints for programmatic access
  - .well-known/rsl/ discovery endpoint

- **Developer Features**
  - REST API with endpoints for licenses, validation, and discovery
  - WordPress rewrite rules for clean URLs
  - Proper query variable handling
  - Action and filter hooks for extensibility
  - Shortcode support for displaying license information

- **Permission System**
  - Usage type controls: all, train-ai, train-genai, ai-use, ai-summarize, search
  - User type restrictions: commercial, non-commercial, education, government, personal
  - Geographic limitations with ISO 3166-1 alpha-2 country codes
  - Both permits and prohibits elements with proper boolean logic

- **Payment & Compensation**
  - Multiple payment types: free, purchase, subscription, training, crawl, inference, attribution
  - Amount and currency specification with decimal precision
  - Standard license URL support (Creative Commons, GPL, etc.)
  - Custom license agreement URLs
  - Integration with RSL Collective and other license servers

- **Legal Framework**
  - Warranty declarations: ownership, authority, no-infringement, privacy-consent, no-malware
  - Disclaimer options: as-is, no-warranty, no-liability, no-indemnity
  - Copyright holder information with type and contact details
  - Schema.org CreativeWork integration

- **Security & Performance**
  - Input validation and sanitization throughout
  - SQL injection prevention with prepared statements
  - Proper nonce usage for form submissions
  - Capability checks for admin functions
  - Efficient database queries with appropriate indexing
  - Proper caching headers for static content

- **Standards Compliance**
  - Full RSL 1.0 specification compliance
  - Proper XML namespace usage (https://rslstandard.org/rsl)
  - Standard MIME types (application/rsl+xml)
  - RFC-compliant HTTP headers
  - robots.txt Robots Exclusion Protocol compatibility
  - RSS 2.0 module specification adherence

### Technical Details

- **Database Schema**
  - Single table design (`wp_rsl_licenses`) with 25+ fields
  - Proper MySQL data types and constraints
  - Automated table creation on activation
  - Support for WordPress multisite installations

- **File Structure**
  - Modular class-based architecture
  - Separation of concerns (admin, frontend, media, robots, RSS, server)
  - Template system for admin pages
  - Asset management for CSS/JS files

- **WordPress Integration**
  - Hook-based architecture following WordPress standards
  - Proper plugin activation/deactivation lifecycle
  - Internationalization ready (text domain: rsl-licensing)
  - WordPress coding standards compliance
  - Multisite network compatibility

### Browser Support
- Modern browsers supporting HTML5 and ES5+ JavaScript
- Graceful degradation for older browsers
- Mobile-responsive admin interface

### Server Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+ or MariaDB equivalent
- Apache/Nginx with rewrite support
- Optional: XMP libraries for advanced image metadata

---

## Development Notes

### Architecture Decisions
- **Single Table Storage**: Simplified schema for better performance and maintenance
- **Hook-Based Design**: Maximum compatibility with other WordPress plugins
- **XML Generation**: Pure PHP implementation without external dependencies
- **Caching Strategy**: Leverages WordPress caching with appropriate cache headers

### Future Considerations
- Content encryption support (planned for v1.1)
- Advanced license server features (payment processing, usage tracking)
- Additional media format support (video, audio metadata)
- Bulk import/export functionality
- Multi-language license support

### Known Limitations
- XMP embedding requires server-side image processing capabilities
- EPUB metadata modification requires ZIP manipulation libraries
- License server authentication is basic (token-based)
- No built-in payment processing (relies on external license servers)

---

**For detailed installation instructions, see [INSTALL.md](INSTALL.md)**
**For usage examples and API documentation, see [README.md](README.md)**
