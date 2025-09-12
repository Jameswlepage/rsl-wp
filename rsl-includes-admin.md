This file is a merged representation of a subset of the codebase, containing specifically included files, combined into a single document by Repomix.

<file_summary>
This section contains a summary of this file.

<purpose>
This file contains a packed representation of a subset of the repository's contents that is considered the most important context.
It is designed to be easily consumable by AI systems for analysis, code review,
or other automated processes.
</purpose>

<file_format>
The content is organized as follows:
1. This summary section
2. Repository information
3. Directory structure
4. Repository files (if enabled)
5. Multiple file entries, each consisting of:
  - File path as an attribute
  - Full contents of the file
</file_format>

<usage_guidelines>
- This file should be treated as read-only. Any changes should be made to the
  original repository files, not this packed version.
- When processing this file, use the file path to distinguish
  between different files in the repository.
- Be aware that this file may contain sensitive information. Handle it with
  the same level of security as you would the original repository.
</usage_guidelines>

<notes>
- Some files may have been excluded based on .gitignore rules and Repomix's configuration
- Binary files are not included in this packed representation. Please refer to the Repository Structure section for a complete list of file paths, including binary files
- Only files matching these patterns are included: includes/**, admin/**
- Files matching patterns in .gitignore are excluded
- Files matching default ignore patterns are excluded
- Files are sorted by Git change count (files with more changes are at the bottom)
</notes>

</file_summary>

<directory_structure>
admin/
  css/
    admin.css
  js/
    admin.js
    gutenberg.js
  templates/
    admin-add-license.php
    admin-dashboard.php
    admin-licenses.php
    admin-settings.php
includes/
  interfaces/
    interface-rsl-payment-processor.php
  processors/
    class-rsl-woocommerce-processor.php
  class-rsl-abilities.php
  class-rsl-admin.php
  class-rsl-frontend.php
  class-rsl-license.php
  class-rsl-media.php
  class-rsl-payment-registry.php
  class-rsl-robots.php
  class-rsl-rss.php
  class-rsl-server.php
  class-rsl-session-manager.php
</directory_structure>

<files>
This section contains the contents of the repository's files.

<file path="admin/js/gutenberg.js">
(function() {
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { SelectControl, TextControl, ToggleControl, PanelRow } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment } = wp.element;

    function RSLLicensePanel() {
        const { editPost } = useDispatch('core/editor');
        
        const { 
            rslLicenseId, 
            rslOverrideUrl,
            globalLicenseId,
            postType
        } = useSelect((select) => {
            const { getEditedPostAttribute } = select('core/editor');
            const { getPostType } = select('core');
            
            return {
                rslLicenseId: getEditedPostAttribute('meta')?._rsl_license_id || '',
                rslOverrideUrl: getEditedPostAttribute('meta')?._rsl_override_content_url || '',
                globalLicenseId: rslGutenberg?.globalLicenseId || 0,
                postType: getPostType(getEditedPostAttribute('type'))
            };
        });

        // Check if RSL is supported for this post type
        const supportedPostTypes = ['post', 'page'];
        if (!supportedPostTypes.includes(postType?.slug)) {
            return null;
        }

        const onLicenseChange = (value) => {
            editPost({
                meta: {
                    _rsl_license_id: value === '' ? 0 : parseInt(value)
                }
            });
        };

        const onOverrideUrlChange = (value) => {
            editPost({
                meta: {
                    _rsl_override_content_url: value
                }
            });
        };

        // Prepare license options
        const licenseOptions = [
            { 
                label: globalLicenseId > 0 
                    ? __('Use Global License (Default)', 'rsl-licensing')
                    : __('No License', 'rsl-licensing'), 
                value: '' 
            }
        ];

        if (rslGutenberg?.licenses) {
            rslGutenberg.licenses.forEach(license => {
                licenseOptions.push({
                    label: license.name + ' (' + license.payment_type + ')',
                    value: license.id.toString()
                });
            });
        }

        return el(
            PluginDocumentSettingPanel,
            {
                name: 'rsl-license-panel',
                title: __('RSL License', 'rsl-licensing'),
                className: 'rsl-license-panel'
            },
            el(
                Fragment,
                null,
                el(
                    PanelRow,
                    null,
                    el(SelectControl, {
                        label: __('License', 'rsl-licensing'),
                        value: rslLicenseId.toString(),
                        options: licenseOptions,
                        onChange: onLicenseChange,
                        help: __('Select an RSL license for this content. Individual licenses override the global site license.', 'rsl-licensing')
                    })
                ),
                el(
                    PanelRow,
                    null,
                    el(TextControl, {
                        label: __('Override Content URL', 'rsl-licensing'),
                        value: rslOverrideUrl,
                        onChange: onOverrideUrlChange,
                        placeholder: __('Leave empty to use post URL', 'rsl-licensing'),
                        help: __('Override the content URL for this specific post/page. Useful for syndicated or cross-posted content.', 'rsl-licensing')
                    })
                ),
                globalLicenseId > 0 && el(
                    'div',
                    { 
                        style: { 
                            marginTop: '12px',
                            padding: '8px 12px', 
                            background: '#f0f6fc', 
                            border: '1px solid #c3c4c7',
                            borderRadius: '4px',
                            fontSize: '13px'
                        }
                    },
                    el('strong', null, __('Global License Active:', 'rsl-licensing')),
                    el('br'),
                    __('This site has a global RSL license configured. Leave the license selection empty to use the global license, or select a specific license to override it for this content.', 'rsl-licensing')
                )
            )
        );
    }

    registerPlugin('rsl-license-panel', {
        render: RSLLicensePanel,
        icon: 'clipboard'
    });
})();
</file>

<file path="includes/interfaces/interface-rsl-payment-processor.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment Processor Interface
 * 
 * Defines the contract for all payment processors in the RSL system.
 * WooCommerce is the primary processor, but this allows for extensibility.
 */
interface RSL_Payment_Processor_Interface {
    
    /**
     * Get processor unique identifier
     * @return string
     */
    public function get_id();
    
    /**
     * Get processor display name
     * @return string
     */
    public function get_name();
    
    /**
     * Check if this processor is available/configured
     * @return bool
     */
    public function is_available();
    
    /**
     * Get supported payment types for this processor
     * @return array
     */
    public function get_supported_payment_types();
    
    /**
     * Check if processor supports a specific payment type
     * @param string $payment_type
     * @return bool
     */
    public function supports_payment_type($payment_type);
    
    /**
     * Create a checkout session for payment
     * @param array $license License data
     * @param string $client Client identifier
     * @param string $session_id Session ID for tracking
     * @param array $options Additional options
     * @return array|WP_Error Session data or error
     */
    public function create_checkout_session($license, $client, $session_id, $options = []);
    
    /**
     * Validate a payment proof
     * @param array $license License data
     * @param string $session_id Session ID
     * @param array $proof_data Payment proof data
     * @return bool|WP_Error True if valid, error otherwise
     */
    public function validate_payment_proof($license, $session_id, $proof_data);
    
    /**
     * Generate signed payment confirmation
     * @param array $license License data
     * @param string $session_id Session ID
     * @param array $payment_data Payment completion data
     * @return string|WP_Error Signed confirmation or error
     */
    public function generate_payment_proof($license, $session_id, $payment_data);
    
    /**
     * Get configuration fields for admin
     * @return array
     */
    public function get_config_fields();
    
    /**
     * Validate processor configuration
     * @param array $config Configuration data
     * @return bool|WP_Error True if valid, error otherwise
     */
    public function validate_config($config);
}
</file>

<file path="includes/class-rsl-abilities.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_Abilities {
    
    private $license_handler;
    private $server;
    
    public function __construct() {
        $this->license_handler = new RSL_License();
        $this->server = new RSL_Server();
        
        add_action('abilities_api_init', array($this, 'register_abilities'));
    }
    
    public function register_abilities() {
        $this->register_admin_abilities();
        $this->register_public_abilities();
        $this->register_server_abilities();
    }
    
    private function register_admin_abilities() {
        // License Management
        wp_register_ability('rsl-licensing/create-license', array(
            'label' => __('Create RSL License', 'rsl-licensing'),
            'description' => __('Creates a new Really Simple Licensing (RSL) license with specified terms, permissions, and payment configuration. Supports all RSL 1.0 specification elements including usage restrictions, geographic limitations, and payment types.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'name' => array(
                        'type' => 'string',
                        'description' => 'Human-readable name for the license',
                        'minLength' => 1
                    ),
                    'description' => array(
                        'type' => 'string',
                        'description' => 'Detailed description of the license terms'
                    ),
                    'content_url' => array(
                        'type' => 'string',
                        'description' => 'URL pattern this license applies to (supports wildcards)',
                        'minLength' => 1
                    ),
                    'payment_type' => array(
                        'type' => 'string',
                        'enum' => array('free', 'purchase', 'subscription', 'training', 'crawl', 'inference', 'attribution'),
                        'default' => 'free',
                        'description' => 'Type of payment or compensation required'
                    ),
                    'amount' => array(
                        'type' => 'number',
                        'minimum' => 0,
                        'description' => 'License fee amount (if applicable)'
                    ),
                    'currency' => array(
                        'type' => 'string',
                        'pattern' => '^[A-Z]{3}$',
                        'default' => 'USD',
                        'description' => 'Currency code (ISO 4217)'
                    ),
                    'permits_usage' => array(
                        'type' => 'string',
                        'description' => 'Comma-separated usage types allowed (all, train-ai, search, etc.)'
                    ),
                    'prohibits_usage' => array(
                        'type' => 'string',
                        'description' => 'Comma-separated usage types prohibited'
                    ),
                    'permits_user' => array(
                        'type' => 'string',
                        'description' => 'User types allowed (commercial, non-commercial, education, etc.)'
                    ),
                    'prohibits_user' => array(
                        'type' => 'string',
                        'description' => 'User types prohibited'
                    ),
                    'permits_geo' => array(
                        'type' => 'string',
                        'description' => 'Geographic regions allowed (ISO 3166-1 alpha-2 codes)'
                    ),
                    'prohibits_geo' => array(
                        'type' => 'string',
                        'description' => 'Geographic regions prohibited'
                    ),
                    'copyright_holder' => array(
                        'type' => 'string',
                        'description' => 'Name of copyright holder'
                    ),
                    'contact_email' => array(
                        'type' => 'string',
                        'format' => 'email',
                        'description' => 'Contact email for licensing inquiries'
                    ),
                    'server_url' => array(
                        'type' => 'string',
                        'format' => 'uri',
                        'description' => 'Optional RSL License Server URL for authentication'
                    )
                ),
                'required' => array('name', 'content_url'),
                'additionalProperties' => false
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'license_id' => array(
                        'type' => 'integer',
                        'description' => 'Unique ID of the created license'
                    ),
                    'success' => array(
                        'type' => 'boolean',
                        'description' => 'Whether the license was successfully created'
                    ),
                    'xml_url' => array(
                        'type' => 'string',
                        'format' => 'uri',
                        'description' => 'URL to access the license XML'
                    )
                )
            ),
            'execute_callback' => array($this, 'create_license'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        wp_register_ability('rsl-licensing/update-license', array(
            'label' => __('Update RSL License', 'rsl-licensing'),
            'description' => __('Updates an existing RSL license configuration. Modifies license terms, permissions, payment settings, and other properties while maintaining license history and validation.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'license_id' => array(
                        'type' => 'integer',
                        'description' => 'ID of the license to update',
                        'minimum' => 1
                    ),
                    'name' => array(
                        'type' => 'string',
                        'description' => 'Updated license name'
                    ),
                    'description' => array(
                        'type' => 'string',
                        'description' => 'Updated license description'
                    ),
                    'payment_type' => array(
                        'type' => 'string',
                        'enum' => array('free', 'purchase', 'subscription', 'training', 'crawl', 'inference', 'attribution'),
                        'description' => 'Updated payment type'
                    ),
                    'amount' => array(
                        'type' => 'number',
                        'minimum' => 0,
                        'description' => 'Updated license fee'
                    ),
                    'active' => array(
                        'type' => 'boolean',
                        'description' => 'Whether the license is active'
                    )
                ),
                'required' => array('license_id'),
                'additionalProperties' => true
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'success' => array(
                        'type' => 'boolean',
                        'description' => 'Whether the update was successful'
                    ),
                    'updated_fields' => array(
                        'type' => 'array',
                        'items' => array('type' => 'string'),
                        'description' => 'List of fields that were updated'
                    )
                )
            ),
            'execute_callback' => array($this, 'update_license'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        wp_register_ability('rsl-licensing/delete-license', array(
            'label' => __('Delete RSL License', 'rsl-licensing'),
            'description' => __('Permanently removes an RSL license from the system. This action cannot be undone and will affect any content currently using this license.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'license_id' => array(
                        'type' => 'integer',
                        'description' => 'ID of the license to delete',
                        'minimum' => 1
                    ),
                    'confirm' => array(
                        'type' => 'boolean',
                        'description' => 'Confirmation that deletion is intended',
                        'const' => true
                    )
                ),
                'required' => array('license_id', 'confirm'),
                'additionalProperties' => false
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'success' => array(
                        'type' => 'boolean',
                        'description' => 'Whether the license was successfully deleted'
                    ),
                    'license_name' => array(
                        'type' => 'string',
                        'description' => 'Name of the deleted license for confirmation'
                    )
                )
            ),
            'execute_callback' => array($this, 'delete_license'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        wp_register_ability('rsl-licensing/list-licenses', array(
            'label' => __('List RSL Licenses', 'rsl-licensing'),
            'description' => __('Retrieves all RSL licenses with optional filtering by status, payment type, or search terms. Returns comprehensive license data for administration and management.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'active_only' => array(
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Return only active licenses'
                    ),
                    'payment_type' => array(
                        'type' => 'string',
                        'enum' => array('free', 'purchase', 'subscription', 'training', 'crawl', 'inference', 'attribution'),
                        'description' => 'Filter by payment type'
                    ),
                    'search' => array(
                        'type' => 'string',
                        'description' => 'Search term to filter license names/descriptions'
                    )
                ),
                'additionalProperties' => false
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'licenses' => array(
                        'type' => 'array',
                        'items' => array(
                            'type' => 'object',
                            'properties' => array(
                                'id' => array('type' => 'integer'),
                                'name' => array('type' => 'string'),
                                'payment_type' => array('type' => 'string'),
                                'active' => array('type' => 'boolean'),
                                'created_at' => array('type' => 'string', 'format' => 'date-time')
                            )
                        ),
                        'description' => 'Array of license objects'
                    ),
                    'total' => array(
                        'type' => 'integer',
                        'description' => 'Total number of licenses matching criteria'
                    )
                )
            ),
            'execute_callback' => array($this, 'list_licenses'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        wp_register_ability('rsl-licensing/update-settings', array(
            'label' => __('Update RSL Settings', 'rsl-licensing'),
            'description' => __('Configures global RSL plugin settings including default license, integration methods (HTML injection, HTTP headers, robots.txt), and namespace configuration.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'global_license_id' => array(
                        'type' => 'integer',
                        'minimum' => 0,
                        'description' => 'ID of license to use site-wide (0 for none)'
                    ),
                    'enable_html_injection' => array(
                        'type' => 'boolean',
                        'description' => 'Embed RSL licenses in HTML head'
                    ),
                    'enable_http_headers' => array(
                        'type' => 'boolean',
                        'description' => 'Add RSL Link headers to HTTP responses'
                    ),
                    'enable_robots_txt' => array(
                        'type' => 'boolean',
                        'description' => 'Include RSL directives in robots.txt'
                    ),
                    'enable_rss_feed' => array(
                        'type' => 'boolean',
                        'description' => 'Add RSL licensing to RSS feeds'
                    ),
                    'enable_media_metadata' => array(
                        'type' => 'boolean',
                        'description' => 'Embed RSL licenses in media file metadata'
                    )
                ),
                'additionalProperties' => false
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'success' => array(
                        'type' => 'boolean',
                        'description' => 'Whether settings were successfully updated'
                    ),
                    'updated_settings' => array(
                        'type' => 'array',
                        'items' => array('type' => 'string'),
                        'description' => 'List of settings that were changed'
                    )
                )
            ),
            'execute_callback' => array($this, 'update_settings'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }
    
    private function register_public_abilities() {
        wp_register_ability('rsl-licensing/get-content-license', array(
            'label' => __('Get Content License', 'rsl-licensing'),
            'description' => __('Retrieves RSL license information for specific content URL. Returns applicable license terms, permissions, payment requirements, and XML data for automated systems and AI agents.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'content_url' => array(
                        'type' => 'string',
                        'description' => 'URL or path to check for license coverage',
                        'minLength' => 1
                    ),
                    'format' => array(
                        'type' => 'string',
                        'enum' => array('json', 'xml'),
                        'default' => 'json',
                        'description' => 'Response format (JSON metadata or XML)'
                    )
                ),
                'required' => array('content_url'),
                'additionalProperties' => false
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'has_license' => array(
                        'type' => 'boolean',
                        'description' => 'Whether content has applicable license'
                    ),
                    'license' => array(
                        'type' => 'object',
                        'properties' => array(
                            'name' => array('type' => 'string'),
                            'payment_type' => array('type' => 'string'),
                            'permits_usage' => array('type' => 'string'),
                            'prohibits_usage' => array('type' => 'string'),
                            'xml_url' => array('type' => 'string', 'format' => 'uri')
                        ),
                        'description' => 'License details if applicable'
                    )
                )
            ),
            'execute_callback' => array($this, 'get_content_license'),
            'permission_callback' => '__return_true'
        ));
        
        wp_register_ability('rsl-licensing/validate-content', array(
            'label' => __('Validate Content Licensing', 'rsl-licensing'),
            'description' => __('Validates whether content usage complies with RSL licensing terms. Checks usage type, user category, and geographic restrictions against license permissions.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'content_url' => array(
                        'type' => 'string',
                        'description' => 'URL of content to validate',
                        'minLength' => 1
                    ),
                    'usage_type' => array(
                        'type' => 'string',
                        'enum' => array('all', 'train-ai', 'train-genai', 'ai-use', 'ai-summarize', 'search'),
                        'description' => 'Intended usage of the content'
                    ),
                    'user_type' => array(
                        'type' => 'string',
                        'enum' => array('commercial', 'non-commercial', 'education', 'government', 'personal'),
                        'description' => 'Category of user requesting access'
                    ),
                    'geo_location' => array(
                        'type' => 'string',
                        'pattern' => '^[A-Z]{2}$',
                        'description' => 'ISO 3166-1 alpha-2 country code'
                    )
                ),
                'required' => array('content_url'),
                'additionalProperties' => false
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'valid' => array(
                        'type' => 'boolean',
                        'description' => 'Whether the requested usage is permitted'
                    ),
                    'license_required' => array(
                        'type' => 'boolean',
                        'description' => 'Whether payment/licensing is required'
                    ),
                    'restrictions' => array(
                        'type' => 'array',
                        'items' => array('type' => 'string'),
                        'description' => 'List of applicable restrictions'
                    ),
                    'license_url' => array(
                        'type' => 'string',
                        'format' => 'uri',
                        'description' => 'URL for license acquisition if needed'
                    )
                )
            ),
            'execute_callback' => array($this, 'validate_content'),
            'permission_callback' => '__return_true'
        ));
        
        wp_register_ability('rsl-licensing/get-license-xml', array(
            'label' => __('Get License XML', 'rsl-licensing'),
            'description' => __('Generates RSL 1.0 compliant XML for a specific license. Returns machine-readable licensing data suitable for automated systems, crawlers, and AI agents.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'license_id' => array(
                        'type' => 'integer',
                        'description' => 'ID of the license to generate XML for',
                        'minimum' => 1
                    )
                ),
                'required' => array('license_id'),
                'additionalProperties' => false
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'xml' => array(
                        'type' => 'string',
                        'description' => 'RSL XML content'
                    ),
                    'license_name' => array(
                        'type' => 'string',
                        'description' => 'Human-readable license name'
                    ),
                    'xml_url' => array(
                        'type' => 'string',
                        'format' => 'uri',
                        'description' => 'Direct URL to access this XML'
                    )
                )
            ),
            'execute_callback' => array($this, 'get_license_xml'),
            'permission_callback' => '__return_true'
        ));
    }
    
    private function register_server_abilities() {
        wp_register_ability('rsl-licensing/issue-token', array(
            'label' => __('Issue License Token', 'rsl-licensing'),
            'description' => __('Issues authentication tokens for paid RSL licenses via Open Licensing Protocol (OLP). Handles payment verification and creates JWT tokens for authorized content access.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'license_id' => array(
                        'type' => 'integer',
                        'description' => 'ID of the license to issue token for',
                        'minimum' => 1
                    ),
                    'client' => array(
                        'type' => 'string',
                        'description' => 'Client identifier requesting the token',
                        'minLength' => 1
                    ),
                    'create_checkout' => array(
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Whether to create WooCommerce checkout for payment'
                    ),
                    'wc_order_key' => array(
                        'type' => 'string',
                        'description' => 'WooCommerce order key for payment verification'
                    )
                ),
                'required' => array('license_id', 'client'),
                'additionalProperties' => false
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'access_token' => array(
                        'type' => 'string',
                        'description' => 'JWT access token for content access'
                    ),
                    'token_type' => array(
                        'type' => 'string',
                        'const' => 'Bearer',
                        'description' => 'Token type (always Bearer)'
                    ),
                    'expires_in' => array(
                        'type' => 'integer',
                        'description' => 'Token expiration time in seconds'
                    ),
                    'checkout_url' => array(
                        'type' => 'string',
                        'format' => 'uri',
                        'description' => 'Payment checkout URL if payment required'
                    )
                )
            ),
            'execute_callback' => array($this, 'issue_token'),
            'permission_callback' => '__return_true'
        ));
        
        wp_register_ability('rsl-licensing/introspect-token', array(
            'label' => __('Introspect License Token', 'rsl-licensing'),
            'description' => __('Validates and introspects RSL license tokens per RFC 7662. Verifies token authenticity, expiration, and associated permissions for secure content access control.', 'rsl-licensing'),
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'token' => array(
                        'type' => 'string',
                        'description' => 'JWT token to validate',
                        'minLength' => 1
                    )
                ),
                'required' => array('token'),
                'additionalProperties' => false
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'active' => array(
                        'type' => 'boolean',
                        'description' => 'Whether the token is valid and active'
                    ),
                    'client_id' => array(
                        'type' => 'string',
                        'description' => 'Client identifier associated with token'
                    ),
                    'license_id' => array(
                        'type' => 'integer',
                        'description' => 'License ID this token grants access to'
                    ),
                    'exp' => array(
                        'type' => 'integer',
                        'description' => 'Token expiration timestamp'
                    ),
                    'scope' => array(
                        'type' => 'string',
                        'description' => 'Token scope and permissions'
                    )
                )
            ),
            'execute_callback' => array($this, 'introspect_token'),
            'permission_callback' => '__return_true'
        ));
    }
    
    // === Execution Methods ===
    
    public function create_license($input) {
        $license_id = $this->license_handler->create_license($input);
        
        if (!$license_id) {
            return new \WP_Error('creation_failed', __('Failed to create license', 'rsl-licensing'));
        }
        
        return array(
            'license_id' => $license_id,
            'success' => true,
            'xml_url' => home_url("/?rsl_license={$license_id}")
        );
    }
    
    public function update_license($input) {
        $license_id = $input['license_id'];
        unset($input['license_id']);
        
        $result = $this->license_handler->update_license($license_id, $input);
        
        if (!$result) {
            return new \WP_Error('update_failed', __('Failed to update license', 'rsl-licensing'));
        }
        
        return array(
            'success' => true,
            'updated_fields' => array_keys($input)
        );
    }
    
    public function delete_license($input) {
        $license = $this->license_handler->get_license($input['license_id']);
        
        if (!$license) {
            return new \WP_Error('not_found', __('License not found', 'rsl-licensing'));
        }
        
        $result = $this->license_handler->delete_license($input['license_id']);
        
        if (!$result) {
            return new \WP_Error('deletion_failed', __('Failed to delete license', 'rsl-licensing'));
        }
        
        return array(
            'success' => true,
            'license_name' => $license['name']
        );
    }
    
    public function list_licenses($input) {
        $args = array();
        
        if (!empty($input['active_only'])) {
            $args['active'] = 1;
        }
        
        if (!empty($input['payment_type'])) {
            $args['payment_type'] = $input['payment_type'];
        }
        
        $licenses = $this->license_handler->get_licenses($args);
        
        if (!empty($input['search'])) {
            $search = strtolower($input['search']);
            $licenses = array_filter($licenses, function($license) use ($search) {
                return strpos(strtolower($license['name']), $search) !== false ||
                       strpos(strtolower($license['description']), $search) !== false;
            });
        }
        
        return array(
            'licenses' => array_map(function($license) {
                return array(
                    'id' => intval($license['id']),
                    'name' => $license['name'],
                    'payment_type' => $license['payment_type'],
                    'active' => (bool)$license['active'],
                    'created_at' => $license['created_at']
                );
            }, $licenses),
            'total' => count($licenses)
        );
    }
    
    public function update_settings($input) {
        $updated = array();
        
        foreach ($input as $key => $value) {
            $option_key = 'rsl_' . $key;
            
            if (update_option($option_key, $value)) {
                $updated[] = $key;
            }
        }
        
        return array(
            'success' => !empty($updated),
            'updated_settings' => $updated
        );
    }
    
    public function get_content_license($input) {
        // Use existing REST validation logic
        $request = new \WP_REST_Request('POST');
        $request->set_body_params(array('content_url' => $input['content_url']));
        
        $response = $this->server->rest_validate_license($request);
        
        if (is_wp_error($response)) {
            return array(
                'has_license' => false,
                'license' => null
            );
        }
        
        $data = $response->get_data();
        $license = !empty($data['licenses']) ? $data['licenses'][0] : null;
        
        return array(
            'has_license' => $data['valid'],
            'license' => $license ? array(
                'name' => $license['name'],
                'payment_type' => $license['payment_type'],
                'permits_usage' => $license['permits_usage'],
                'prohibits_usage' => $license['prohibits_usage'],
                'xml_url' => $license['xml_url']
            ) : null
        );
    }
    
    public function validate_content($input) {
        $content_license = $this->get_content_license(array('content_url' => $input['content_url']));
        
        if (!$content_license['has_license']) {
            return array(
                'valid' => true, // No license means no restrictions
                'license_required' => false,
                'restrictions' => array(),
                'license_url' => null
            );
        }
        
        $license = $content_license['license'];
        $restrictions = array();
        $valid = true;
        
        // Check usage restrictions
        if (!empty($input['usage_type']) && !empty($license['prohibits_usage'])) {
            $prohibited = explode(',', $license['prohibits_usage']);
            if (in_array($input['usage_type'], $prohibited) || in_array('all', $prohibited)) {
                $valid = false;
                $restrictions[] = "Usage type '{$input['usage_type']}' is prohibited";
            }
        }
        
        return array(
            'valid' => $valid,
            'license_required' => $license['payment_type'] !== 'free',
            'restrictions' => $restrictions,
            'license_url' => $valid ? null : $license['xml_url']
        );
    }
    
    public function get_license_xml($input) {
        $license = $this->license_handler->get_license($input['license_id']);
        
        if (!$license || !$license['active']) {
            return new \WP_Error('not_found', __('License not found or inactive', 'rsl-licensing'));
        }
        
        $xml = $this->license_handler->generate_rsl_xml($license);
        
        return array(
            'xml' => $xml,
            'license_name' => $license['name'],
            'xml_url' => home_url("/?rsl_license={$license['id']}")
        );
    }
    
    public function issue_token($input) {
        // Delegate to existing OLP implementation
        $request = new \WP_REST_Request('POST');
        $request->set_body_params($input);
        
        $response = $this->server->olp_issue_token($request);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response->get_data();
    }
    
    public function introspect_token($input) {
        // Delegate to existing OLP implementation
        $request = new \WP_REST_Request('POST');
        $request->set_body_params($input);
        
        $response = $this->server->olp_introspect($request);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response->get_data();
    }
}
</file>

<file path="includes/class-rsl-payment-registry.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment Processor Registry
 * 
 * Manages all available payment processors and their capabilities.
 */
class RSL_Payment_Registry {
    
    private static $instance = null;
    private $processors = [];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_processors();
    }
    
    /**
     * Load and register available payment processors
     */
    private function load_processors() {
        // Load WooCommerce processor (primary)
        if (class_exists('WooCommerce')) {
            require_once RSL_PLUGIN_PATH . 'includes/processors/class-rsl-woocommerce-processor.php';
            $this->register_processor(new RSL_WooCommerce_Processor());
        }
        
        // Allow third-party processors to register
        do_action('rsl_register_payment_processors', $this);
    }
    
    /**
     * Register a payment processor
     * @param RSL_Payment_Processor_Interface $processor
     * @return bool
     */
    public function register_processor(RSL_Payment_Processor_Interface $processor) {
        if (!$processor->is_available()) {
            return false;
        }
        
        $this->processors[$processor->get_id()] = $processor;
        return true;
    }
    
    /**
     * Get all registered processors
     * @return array
     */
    public function get_processors() {
        return $this->processors;
    }
    
    /**
     * Get available processors
     * @return array
     */
    public function get_available_processors() {
        return array_filter($this->processors, function($processor) {
            return $processor->is_available();
        });
    }
    
    /**
     * Get processor by ID
     * @param string $processor_id
     * @return RSL_Payment_Processor_Interface|null
     */
    public function get_processor($processor_id) {
        return isset($this->processors[$processor_id]) ? $this->processors[$processor_id] : null;
    }
    
    /**
     * Get the best processor for a license
     * @param array $license
     * @return RSL_Payment_Processor_Interface|null
     */
    public function get_processor_for_license($license) {
        $payment_type = $license['payment_type'];
        $amount = floatval($license['amount']);
        
        // For free licenses, no processor needed
        if ($amount === 0.0) {
            return null;
        }
        
        // Look for processors that support this payment type
        foreach ($this->processors as $processor) {
            if ($processor->supports_payment_type($payment_type)) {
                return $processor;
            }
        }
        
        return null;
    }
    
    /**
     * Check if any processor can handle paid licenses
     * @return bool
     */
    public function has_payment_capability() {
        foreach ($this->processors as $processor) {
            $supported_types = $processor->get_supported_payment_types();
            if (!empty($supported_types)) {
                return true;
            }
        }
        return false;
    }
}
</file>

<file path="includes/class-rsl-robots.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_Robots {
    
    public function __construct() {
        add_filter('robots_txt', array($this, 'add_rsl_to_robots'), 10, 2);
        add_action('template_redirect', array($this, 'handle_robots_txt'));
    }
    
    public function add_rsl_to_robots($output, $public) {
        if (!get_option('rsl_enable_robots_txt', 1)) {
            return $output;
        }
        
        // Only add RSL directive if site is public
        if ('1' != $public) {
            return $output;
        }
        
        $global_license_id = get_option('rsl_global_license_id', 0);
        
        if ($global_license_id > 0) {
            $license_handler = new RSL_License();
            $license_data = $license_handler->get_license($global_license_id);
            
            if ($license_data && $license_data['active']) {
                $license_xml_url = $this->get_license_xml_url($global_license_id);
                $output .= "\n# RSL Licensing Directive\n";
                $output .= "License: " . $license_xml_url . "\n";
                
                // Add AI Preferences compatibility
                $ai_preferences = $this->get_ai_preferences_from_license($license_data);
                if (!empty($ai_preferences)) {
                    $output .= "\n# AI Content Usage Preferences\n";
                    $output .= "Content-Usage: " . $ai_preferences . "\n";
                }
                
                $output .= "\n";
            }
        }
        
        // Add RSL feed URL
        $rsl_feed_url = $this->get_rsl_feed_url();
        $output .= "# RSL License Feed\n";
        $output .= "# " . $rsl_feed_url . "\n";
        
        return $output;
    }
    
    public function handle_robots_txt() {
        // Only handle if this is a robots.txt request and we have custom handling enabled
        if (!is_robots() || !get_option('rsl_enable_robots_txt', 1)) {
            return;
        }
        
        // WordPress will handle the basic robots.txt generation
        // Our filter will add the RSL directives
    }
    
    private function get_license_xml_url($license_id) {
        return add_query_arg('rsl_license', $license_id, home_url());
    }
    
    private function get_rsl_feed_url() {
        return add_query_arg('rsl_feed', '1', home_url());
    }
    
    private function get_ai_preferences_from_license($license_data) {
        $preferences = array();
        
        // Convert RSL permits/prohibits to AI Preferences format
        $prohibited_usage = !empty($license_data['prohibits_usage']) ? 
            explode(',', $license_data['prohibits_usage']) : array();
        
        $permitted_usage = !empty($license_data['permits_usage']) ? 
            explode(',', $license_data['permits_usage']) : array();
        
        // Default AI preference mappings
        $ai_preference_map = array(
            'train-ai' => 'train-ai',
            'train-genai' => 'train-ai', // Map to generic train-ai
            'ai-use' => 'ai-use',
            'ai-summarize' => 'ai-summarize',
            'search' => 'search'
        );
        
        // If specific usage is prohibited, set to 'n'
        foreach ($prohibited_usage as $usage) {
            $usage = trim($usage);
            if (isset($ai_preference_map[$usage])) {
                $preferences[] = $ai_preference_map[$usage] . '=n';
            }
        }
        
        // If 'all' is prohibited, set all to 'n'
        if (in_array('all', $prohibited_usage)) {
            $preferences = array('train-ai=n', 'ai-use=n', 'ai-summarize=n', 'search=n');
        }
        
        // If only specific usage is permitted (and others are implicitly prohibited)
        if (!empty($permitted_usage) && !in_array('all', $permitted_usage)) {
            $all_usage_types = array_keys($ai_preference_map);
            $implicitly_prohibited = array_diff($all_usage_types, $permitted_usage);
            
            foreach ($implicitly_prohibited as $usage) {
                if (isset($ai_preference_map[$usage])) {
                    $pref = $ai_preference_map[$usage] . '=n';
                    if (!in_array($pref, $preferences)) {
                        $preferences[] = $pref;
                    }
                }
            }
        }
        
        return implode(', ', array_unique($preferences));
    }
    
    public function generate_robots_txt_with_rsl() {
        $output = "User-agent: *\n";
        $output .= "Allow: /\n";
        
        // Add standard WordPress disallows
        $output .= "Disallow: /wp-admin/\n";
        $output .= "Allow: /wp-admin/admin-ajax.php\n";
        
        // Add RSL licensing information
        $rsl_output = $this->add_rsl_to_robots('', '1');
        $output .= $rsl_output;
        
        return $output;
    }
    
    public function get_current_robots_txt() {
        // Get the current robots.txt content
        $robots_url = home_url('robots.txt');
        
        $response = wp_remote_get($robots_url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'RSL-Robots-Checker/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            return $this->generate_robots_txt_with_rsl();
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    public function validate_robots_txt() {
        $robots_content = $this->get_current_robots_txt();
        
        $validation = array(
            'has_license_directive' => false,
            'has_content_usage' => false,
            'license_url' => '',
            'ai_preferences' => '',
            'issues' => array()
        );
        
        // Check for License directive
        if (preg_match('/^License:\s*(.+)$/m', $robots_content, $matches)) {
            $validation['has_license_directive'] = true;
            $validation['license_url'] = trim($matches[1]);
            
            // Validate license URL
            if (!filter_var($validation['license_url'], FILTER_VALIDATE_URL)) {
                $validation['issues'][] = __('License URL is not valid', 'rsl-licensing');
            }
        } else {
            $validation['issues'][] = __('No License directive found', 'rsl-licensing');
        }
        
        // Check for Content-Usage directive
        if (preg_match('/^Content-Usage:\s*(.+)$/m', $robots_content, $matches)) {
            $validation['has_content_usage'] = true;
            $validation['ai_preferences'] = trim($matches[1]);
        }
        
        return $validation;
    }
}
</file>

<file path="includes/class-rsl-rss.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_RSS {
    
    private $license_handler;
    
    public function __construct() {
        $this->license_handler = new RSL_License();
        
        add_action('rss_head', array($this, 'add_rsl_namespace'));
        add_action('rss2_head', array($this, 'add_rsl_namespace'));
        add_action('rss_item', array($this, 'add_rsl_to_rss_item'));
        add_action('rss2_item', array($this, 'add_rsl_to_rss_item'));
        
        // Add custom RSS feed for RSL licenses
        add_action('init', array($this, 'add_rsl_feed'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    public function add_rsl_namespace() {
        if (!get_option('rsl_enable_rss_feed', 1)) {
            return;
        }
        
        echo 'xmlns:rsl="https://rslstandard.org/rsl"' . "\n";
    }
    
    public function add_rsl_to_rss_item() {
        if (!get_option('rsl_enable_rss_feed', 1)) {
            return;
        }
        
        global $post;
        
        if (!$post) {
            return;
        }
        
        $license_data = $this->get_post_license($post);
        
        if (!$license_data) {
            return;
        }
        
        // Generate RSL content for RSS item
        $this->output_rss_rsl_content($license_data, $post);
    }
    
    public function add_rsl_feed() {
        add_feed('rsl-licenses', array($this, 'rsl_feed_template'));
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'rsl_feed';
        return $vars;
    }
    
    public function rsl_feed_template() {
        $licenses = $this->license_handler->get_licenses(array('active' => 1));
        
        header('Content-Type: application/rss+xml; charset=UTF-8');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        ?>
        <rss xmlns:rsl="https://rslstandard.org/rsl" version="2.0">
            <channel>
                <title><?php echo esc_html(get_bloginfo('name')); ?> - RSL Licenses</title>
                <link><?php echo esc_url(home_url()); ?></link>
                <description><?php echo esc_html(get_bloginfo('description')); ?></description>
                <language><?php echo esc_html(get_bloginfo('language')); ?></language>
                <lastBuildDate><?php echo date('r'); ?></lastBuildDate>
                <generator>RSL Licensing for WordPress v<?php echo RSL_PLUGIN_VERSION; ?></generator>
                
                <?php foreach ($licenses as $license) : ?>
                <item>
                    <title><?php echo esc_html($license['name']); ?></title>
                    <link><?php echo esc_url($this->get_license_xml_url($license['id'])); ?></link>
                    <description><?php echo esc_html($license['description'] ?: 'RSL License Configuration'); ?></description>
                    <guid><?php echo esc_url($this->get_license_xml_url($license['id'])); ?></guid>
                    <?php if (!empty($license['updated_at'])) : ?>
                    <pubDate><?php echo date('r', strtotime($license['updated_at'])); ?></pubDate>
                    <?php endif; ?>
                    
                    <?php $this->output_rss_rsl_content($license); ?>
                </item>
                <?php endforeach; ?>
                
            </channel>
        </rss>
        <?php
        exit;
    }
    
    private function get_post_license($post) {
        // Check for post-specific license
        $post_license_id = get_post_meta($post->ID, '_rsl_license_id', true);
        if ($post_license_id) {
            $license_data = $this->license_handler->get_license($post_license_id);
            if ($license_data && $license_data['active']) {
                return $this->prepare_post_license_data($license_data, $post);
            }
        }
        
        // Fall back to global license
        $global_license_id = get_option('rsl_global_license_id', 0);
        if ($global_license_id > 0) {
            $license_data = $this->license_handler->get_license($global_license_id);
            if ($license_data && $license_data['active']) {
                return $this->prepare_post_license_data($license_data, $post);
            }
        }
        
        return null;
    }
    
    private function prepare_post_license_data($license_data, $post = null) {
        if ($post) {
            // Override content URL for specific post
            $override_url = get_post_meta($post->ID, '_rsl_override_content_url', true);
            if (!empty($override_url)) {
                $license_data['content_url'] = $override_url;
            } else {
                $license_data['content_url'] = get_permalink($post->ID);
            }
        }
        
        return $license_data;
    }
    
    private function output_rss_rsl_content($license_data, $post = null) {
        // Generate content URL
        $content_url = $license_data['content_url'];
        
        if (empty($content_url) && $post) {
            $content_url = get_permalink($post->ID);
        } else if (empty($content_url)) {
            $content_url = home_url('/');
        }
        
        echo "\n    <rsl:content url=\"" . esc_attr($content_url) . "\"";
        
        if (!empty($license_data['server_url'])) {
            echo " server=\"" . esc_attr($license_data['server_url']) . "\"";
        }
        
        if (!empty($license_data['encrypted']) && $license_data['encrypted'] == 1) {
            echo " encrypted=\"true\"";
        }
        
        if (!empty($license_data['lastmod'])) {
            echo " lastmod=\"" . esc_attr(date('c', strtotime($license_data['lastmod']))) . "\"";
        }
        
        echo ">\n";
        
        // Schema information
        if (!empty($license_data['schema_url'])) {
            echo "      <rsl:schema>" . esc_html($license_data['schema_url']) . "</rsl:schema>\n";
        }
        
        // Copyright information
        if (!empty($license_data['copyright_holder'])) {
            echo "      <rsl:copyright";
            if (!empty($license_data['copyright_type'])) {
                echo " type=\"" . esc_attr($license_data['copyright_type']) . "\"";
            }
            if (!empty($license_data['contact_email'])) {
                echo " contactEmail=\"" . esc_attr($license_data['contact_email']) . "\"";
            }
            if (!empty($license_data['contact_url'])) {
                echo " contactUrl=\"" . esc_attr($license_data['contact_url']) . "\"";
            }
            echo ">" . esc_html($license_data['copyright_holder']) . "</rsl:copyright>\n";
        }
        
        // Terms URL
        if (!empty($license_data['terms_url'])) {
            echo "      <rsl:terms>" . esc_html($license_data['terms_url']) . "</rsl:terms>\n";
        }
        
        // License information
        echo "      <rsl:license>\n";
        
        // Permits
        if (!empty($license_data['permits_usage'])) {
            echo "        <rsl:permits type=\"usage\">" . esc_html($license_data['permits_usage']) . "</rsl:permits>\n";
        }
        
        if (!empty($license_data['permits_user'])) {
            echo "        <rsl:permits type=\"user\">" . esc_html($license_data['permits_user']) . "</rsl:permits>\n";
        }
        
        if (!empty($license_data['permits_geo'])) {
            echo "        <rsl:permits type=\"geo\">" . esc_html($license_data['permits_geo']) . "</rsl:permits>\n";
        }
        
        // Prohibits
        if (!empty($license_data['prohibits_usage'])) {
            echo "        <rsl:prohibits type=\"usage\">" . esc_html($license_data['prohibits_usage']) . "</rsl:prohibits>\n";
        }
        
        if (!empty($license_data['prohibits_user'])) {
            echo "        <rsl:prohibits type=\"user\">" . esc_html($license_data['prohibits_user']) . "</rsl:prohibits>\n";
        }
        
        if (!empty($license_data['prohibits_geo'])) {
            echo "        <rsl:prohibits type=\"geo\">" . esc_html($license_data['prohibits_geo']) . "</rsl:prohibits>\n";
        }
        
        // Payment information
        if (!empty($license_data['payment_type']) && $license_data['payment_type'] !== 'free') {
            echo "        <rsl:payment type=\"" . esc_attr($license_data['payment_type']) . "\">\n";
            
            if (!empty($license_data['standard_url'])) {
                echo "          <rsl:standard>" . esc_html($license_data['standard_url']) . "</rsl:standard>\n";
            }
            
            if (!empty($license_data['custom_url'])) {
                echo "          <rsl:custom>" . esc_html($license_data['custom_url']) . "</rsl:custom>\n";
            }
            
            if (!empty($license_data['amount']) && $license_data['amount'] > 0) {
                echo "          <rsl:amount currency=\"" . esc_attr($license_data['currency']) . "\">" . 
                     esc_html($license_data['amount']) . "</rsl:amount>\n";
            }
            
            echo "        </rsl:payment>\n";
        } else {
            echo "        <rsl:payment type=\"free\"/>\n";
        }
        
        // Legal information
        if (!empty($license_data['warranty'])) {
            echo "        <rsl:legal type=\"warranty\">" . esc_html($license_data['warranty']) . "</rsl:legal>\n";
        }
        
        if (!empty($license_data['disclaimer'])) {
            echo "        <rsl:legal type=\"disclaimer\">" . esc_html($license_data['disclaimer']) . "</rsl:legal>\n";
        }
        
        echo "      </rsl:license>\n";
        echo "    </rsl:content>\n";
    }
    
    private function get_license_xml_url($license_id) {
        return add_query_arg('rsl_license', $license_id, home_url());
    }
    
    public function get_rsl_feed_url() {
        return home_url('feed/rsl-licenses/');
    }
    
    public function enhance_existing_feeds() {
        // This method can be called to enhance existing RSS feeds with RSL data
        // It's automatically hooked into WordPress RSS generation
        
        if (!get_option('rsl_enable_rss_feed', 1)) {
            return;
        }
        
        // Add RSL metadata to standard WordPress feeds
        add_action('rss_head', function() {
            echo "<!-- Enhanced with RSL Licensing -->\n";
        });
        
        add_action('rss2_head', function() {
            echo "<!-- Enhanced with RSL Licensing -->\n";
            
            // Add global license information to feed header if available
            $global_license_id = get_option('rsl_global_license_id', 0);
            if ($global_license_id > 0) {
                $license_data = $this->license_handler->get_license($global_license_id);
                if ($license_data && $license_data['active']) {
                    $xml_url = $this->get_license_xml_url($global_license_id);
                    echo "<license>" . esc_url($xml_url) . "</license>\n";
                }
            }
        });
    }
}
</file>

<file path="includes/class-rsl-session-manager.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Session Manager
 * 
 * Manages payment sessions using MCP-inspired patterns.
 * Sessions are stored server-side and polled by AI agents.
 */
class RSL_Session_Manager {
    
    private static $instance = null;
    private $session_ttl = 3600; // 1 hour default
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Clean up expired sessions periodically
        add_action('wp_scheduled_delete', [$this, 'cleanup_expired_sessions']);
    }
    
    /**
     * Create a new payment session
     * @param array $license License data
     * @param string $client Client identifier
     * @param array $options Additional options
     * @return array Session data
     */
    public function create_session($license, $client, $options = []) {
        $session_id = wp_generate_uuid4();
        $now = time();
        
        $session_data = [
            'id' => $session_id,
            'license_id' => $license['id'],
            'client' => $client,
            'status' => 'created',
            'created_at' => $now,
            'updated_at' => $now,
            'expires_at' => $now + $this->session_ttl,
            'checkout_url' => null,
            'proof' => null,
            'processor_id' => null,
            'processor_data' => [],
            'options' => $options
        ];
        
        // Store session server-side (MCP pattern)
        update_option('rsl_session_' . $session_id, $session_data, false);
        
        return [
            'session_id' => $session_id,
            'status' => 'created',
            'polling_url' => rest_url('rsl-olp/v1/session/' . $session_id),
            'expires_at' => gmdate('c', $session_data['expires_at'])
        ];
    }
    
    /**
     * Get session by ID
     * @param string $session_id
     * @return array|null
     */
    public function get_session($session_id) {
        $session = get_option('rsl_session_' . $session_id);
        
        if (!$session) {
            return null;
        }
        
        // Check if expired
        if (time() > $session['expires_at']) {
            $this->delete_session($session_id);
            return null;
        }
        
        return $session;
    }
    
    /**
     * Update session data
     * @param string $session_id
     * @param array $updates
     * @return bool
     */
    public function update_session($session_id, $updates) {
        $session = $this->get_session($session_id);
        if (!$session) {
            return false;
        }
        
        $session = array_merge($session, $updates);
        $session['updated_at'] = time();
        
        update_option('rsl_session_' . $session_id, $session, false);
        return true;
    }
    
    /**
     * Update session status
     * @param string $session_id
     * @param string $status
     * @param array $data Additional data to store
     * @return bool
     */
    public function update_session_status($session_id, $status, $data = []) {
        $updates = array_merge(['status' => $status], $data);
        return $this->update_session($session_id, $updates);
    }
    
    /**
     * Set checkout URL for session
     * @param string $session_id
     * @param string $checkout_url
     * @param string $processor_id
     * @return bool
     */
    public function set_checkout_url($session_id, $checkout_url, $processor_id) {
        return $this->update_session($session_id, [
            'status' => 'awaiting_payment',
            'checkout_url' => $checkout_url,
            'processor_id' => $processor_id
        ]);
    }
    
    /**
     * Store payment proof for session
     * @param string $session_id
     * @param string $proof Signed payment confirmation
     * @return bool
     */
    public function store_payment_proof($session_id, $proof) {
        return $this->update_session($session_id, [
            'status' => 'proof_ready',
            'proof' => $proof
        ]);
    }
    
    /**
     * Mark session as completed
     * @param string $session_id
     * @return bool
     */
    public function complete_session($session_id) {
        return $this->update_session_status($session_id, 'completed');
    }
    
    /**
     * Delete session
     * @param string $session_id
     * @return bool
     */
    public function delete_session($session_id) {
        return delete_option('rsl_session_' . $session_id);
    }
    
    /**
     * Get session status for API response
     * @param string $session_id
     * @return array|WP_Error
     */
    public function get_session_status($session_id) {
        $session = $this->get_session($session_id);
        
        if (!$session) {
            return new WP_Error('session_not_found', 'Session not found or expired', ['status' => 404]);
        }
        
        $response = [
            'session_id' => $session_id,
            'status' => $session['status'],
            'created_at' => gmdate('c', $session['created_at']),
            'expires_at' => gmdate('c', $session['expires_at'])
        ];
        
        // Add status-specific data
        switch ($session['status']) {
            case 'created':
                $response['message'] = 'Session created, preparing checkout';
                break;
                
            case 'awaiting_payment':
                $response['checkout_url'] = $session['checkout_url'];
                $response['message'] = 'Payment required';
                break;
                
            case 'proof_ready':
                $response['signed_proof'] = $session['proof'];
                $response['message'] = 'Payment confirmed, use signed_proof to get token';
                break;
                
            case 'completed':
                $response['message'] = 'Session completed successfully';
                break;
                
            case 'failed':
                $response['message'] = 'Payment failed';
                break;
        }
        
        return $response;
    }
    
    /**
     * Clean up expired sessions
     */
    public function cleanup_expired_sessions() {
        global $wpdb;
        
        $expired_time = time() - $this->session_ttl;
        
        // Find expired sessions
        $expired_sessions = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'rsl_session_%' 
             AND option_value LIKE '%expires_at%' 
             AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(option_value, '\"expires_at\":', -1), ',', 1) AS UNSIGNED) < %d",
            $expired_time
        ));
        
        // Delete expired sessions
        foreach ($expired_sessions as $option_name) {
            delete_option($option_name);
        }
        
        if (!empty($expired_sessions)) {
            error_log(sprintf('RSL: Cleaned up %d expired sessions', count($expired_sessions)));
        }
    }
}
</file>

<file path="includes/processors/class-rsl-woocommerce-processor.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Payment Processor
 * 
 * Handles all payment processing through WooCommerce.
 * WooCommerce manages all payment gateways (Stripe, PayPal, etc.)
 */
class RSL_WooCommerce_Processor implements RSL_Payment_Processor_Interface {
    
    public function get_id() {
        return 'woocommerce';
    }
    
    public function get_name() {
        return 'WooCommerce';
    }
    
    public function is_available() {
        return class_exists('WooCommerce');
    }
    
    public function get_supported_payment_types() {
        // WooCommerce can handle ALL RSL payment types as purchases or subscriptions
        $types = [
            'purchase',     // One-time payment
            'crawl',        // Pay-per-crawl (one-time payment)
            'training',     // AI training fee (one-time payment)
            'inference',    // AI inference fee (one-time payment)
            'attribution'   // Paid attribution (one-time payment)
        ];
        
        // Add subscription-based payment types if WC Subscriptions is active
        if (class_exists('WC_Subscriptions') || function_exists('wcs_get_subscriptions')) {
            $types[] = 'subscription';  // Recurring subscription
        }
        
        return $types;
    }
    
    public function supports_payment_type($payment_type) {
        return in_array($payment_type, $this->get_supported_payment_types());
    }
    
    public function create_checkout_session($license, $client, $session_id, $options = []) {
        try {
            // Create or get WooCommerce product for this license
            $product_id = $this->ensure_product_for_license($license);
            if (is_wp_error($product_id)) {
                return $product_id;
            }
            
            // Create checkout URL
            $checkout_url = wc_get_checkout_url();
            $checkout_url = add_query_arg([
                'add-to-cart' => $product_id,
                'rsl_session_id' => $session_id,
                'rsl_client' => urlencode($client),
                'rsl_license_id' => $license['id']
            ], $checkout_url);
            
            return [
                'checkout_url' => esc_url_raw($checkout_url),
                'product_id' => $product_id,
                'processor_data' => [
                    'product_id' => $product_id,
                    'payment_type' => $license['payment_type']
                ]
            ];
            
        } catch (Exception $e) {
            return new WP_Error('checkout_creation_failed', $e->getMessage());
        }
    }
    
    public function validate_payment_proof($license, $session_id, $proof_data) {
        if (empty($proof_data['wc_order_id'])) {
            return new WP_Error('missing_order_id', 'WooCommerce order ID required');
        }
        
        $order_id = intval($proof_data['wc_order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('order_not_found', 'Order not found');
        }
        
        // Verify order is paid
        if (!$order->is_paid()) {
            return new WP_Error('order_not_paid', 'Order not paid');
        }
        
        // Verify order metadata matches session
        $order_session_id = $order->get_meta('rsl_session_id');
        if ($order_session_id !== $session_id) {
            return new WP_Error('session_mismatch', 'Order session does not match');
        }
        
        // Verify license matches
        $order_license_id = $order->get_meta('rsl_license_id');
        if (intval($order_license_id) !== intval($license['id'])) {
            return new WP_Error('license_mismatch', 'Order license does not match');
        }
        
        // Verify order is recent (prevent replay attacks)
        $order_date = $order->get_date_created();
        $hours_since_order = (time() - $order_date->getTimestamp()) / 3600;
        if ($hours_since_order > 24) {
            return new WP_Error('order_expired', 'Order too old for token generation');
        }
        
        return true;
    }
    
    public function generate_payment_proof($license, $session_id, $payment_data) {
        $order_id = intval($payment_data['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->is_paid()) {
            return new WP_Error('invalid_order', 'Invalid or unpaid order');
        }
        
        // Create signed proof
        $proof_payload = [
            'iss' => home_url(),
            'aud' => $license['id'],
            'sub' => 'woocommerce_payment_proof',
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour expiry
            'jti' => wp_generate_uuid4(),
            
            // Payment details
            'session_id' => $session_id,
            'order_id' => $order_id,
            'license_id' => $license['id'],
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'payment_method' => $order->get_payment_method(),
            'processor' => 'woocommerce'
        ];
        
        try {
            return $this->sign_jwt_payload($proof_payload);
        } catch (Exception $e) {
            return new WP_Error('proof_generation_failed', $e->getMessage());
        }
    }
    
    public function get_config_fields() {
        return [
            'wc_product_visibility' => [
                'type' => 'select',
                'label' => __('License Product Visibility', 'rsl-licensing'),
                'options' => [
                    'hidden' => __('Hidden (default)', 'rsl-licensing'),
                    'catalog' => __('Visible in catalog', 'rsl-licensing'),
                    'search' => __('Visible in search', 'rsl-licensing')
                ],
                'default' => 'hidden',
                'description' => __('How should auto-created license products appear in your store?', 'rsl-licensing')
            ]
        ];
    }
    
    public function validate_config($config) {
        // WooCommerce processor doesn't need additional config validation
        // WC handles its own payment gateway configuration
        return true;
    }
    
    /**
     * Ensure a WooCommerce product exists for this license
     * @param array $license
     * @return int|WP_Error Product ID or error
     */
    private function ensure_product_for_license($license) {
        // Check if product already exists
        $existing_query = new WP_Query([
            'post_type' => 'product',
            'meta_key' => '_rsl_license_id',
            'meta_value' => $license['id'],
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        
        if ($existing_query->have_posts()) {
            return $existing_query->posts[0];
        }
        
        // Create new product
        $product_data = [
            'post_title' => sprintf(__('RSL License: %s', 'rsl-licensing'), $license['name']),
            'post_content' => sprintf(__('Digital content license for %s', 'rsl-licensing'), $license['content_url']),
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_input' => [
                '_rsl_license_id' => $license['id'],
                '_virtual' => 'yes',
                '_downloadable' => 'no',
                '_price' => $license['amount'],
                '_regular_price' => $license['amount'],
                '_manage_stock' => 'no',
                '_stock_status' => 'instock',
                '_visibility' => 'hidden', // Hide from catalog by default
                '_featured' => 'no'
            ]
        ];
        
        $product_id = wp_insert_post($product_data);
        
        if (is_wp_error($product_id)) {
            return $product_id;
        }
        
        // Set product type
        wp_set_object_terms($product_id, 'simple', 'product_type');
        
        // Set up subscription if needed
        if ($license['payment_type'] === 'subscription' && (class_exists('WC_Subscriptions') || function_exists('wcs_get_subscriptions'))) {
            update_post_meta($product_id, '_subscription_price', $license['amount']);
            update_post_meta($product_id, '_subscription_period', 'month');
            update_post_meta($product_id, '_subscription_period_interval', '1');
            wp_set_object_terms($product_id, 'subscription', 'product_type');
        }
        
        return $product_id;
    }
    
    /**
     * Sign JWT payload
     * @param array $payload
     * @return string
     */
    private function sign_jwt_payload($payload) {
        // Use same JWT signing as main server
        if (class_exists('Firebase\JWT\JWT')) {
            return Firebase\JWT\JWT::encode($payload, $this->get_jwt_secret(), 'HS256');
        }
        
        // Fallback implementation
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload_json = json_encode($payload);
        
        $base64_header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64_payload = rtrim(strtr(base64_encode($payload_json), '+/', '-_'), '=');
        
        $signature = hash_hmac('sha256', $base64_header . '.' . $base64_payload, $this->get_jwt_secret(), true);
        $base64_signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        
        return $base64_header . '.' . $base64_payload . '.' . $base64_signature;
    }
    
    /**
     * Get JWT secret for signing
     * @return string
     */
    private function get_jwt_secret() {
        $secret = get_option('rsl_jwt_secret');
        if (!$secret) {
            $secret = wp_generate_password(64, true, true);
            update_option('rsl_jwt_secret', $secret, false);
        }
        return $secret;
    }
}
</file>

<file path="includes/class-rsl-media.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_Media {
    
    private $license_handler;
    
    public function __construct() {
        $this->license_handler = new RSL_License();
        
        add_filter('wp_generate_attachment_metadata', array($this, 'add_rsl_to_media_metadata'), 10, 2);
        add_action('add_attachment', array($this, 'process_uploaded_media'));
        add_filter('attachment_fields_to_edit', array($this, 'add_rsl_attachment_fields'), 10, 2);
        add_filter('attachment_fields_to_save', array($this, 'save_rsl_attachment_fields'), 10, 2);
        
        // Add media library column
        add_filter('manage_media_columns', array($this, 'add_media_column'));
        add_action('manage_media_custom_column', array($this, 'show_media_column'), 10, 2);
    }
    
    public function add_rsl_to_media_metadata($metadata, $attachment_id) {
        if (!get_option('rsl_enable_media_metadata', 1)) {
            return $metadata;
        }
        
        $license_data = $this->get_media_license($attachment_id);
        
        if (!$license_data) {
            return $metadata;
        }
        
        // Add RSL information to metadata
        $metadata['rsl_license'] = array(
            'license_id' => $license_data['id'],
            'license_name' => $license_data['name'],
            'payment_type' => $license_data['payment_type'],
            'xml_url' => $this->get_license_xml_url($license_data['id'])
        );
        
        return $metadata;
    }
    
    public function process_uploaded_media($attachment_id) {
        if (!get_option('rsl_enable_media_metadata', 1)) {
            return;
        }
        
        $file_path = get_attached_file($attachment_id);
        $file_type = wp_check_filetype($file_path);
        
        // Process based on file type
        switch ($file_type['type']) {
            case 'image/jpeg':
            case 'image/tiff':
            case 'image/png':
            case 'image/webp':
                $this->embed_rsl_in_image($attachment_id, $file_path);
                break;
                
            case 'application/pdf':
                $this->embed_rsl_in_pdf($attachment_id, $file_path);
                break;
                
            case 'application/epub+zip':
                $this->embed_rsl_in_epub($attachment_id, $file_path);
                break;
        }
    }
    
    private function embed_rsl_in_image($attachment_id, $file_path) {
        $license_data = $this->get_media_license($attachment_id);
        
        if (!$license_data) {
            return;
        }
        
        // Generate XMP metadata with RSL information
        $xmp_data = $this->generate_xmp_rsl($license_data, $attachment_id);
        
        // For JPEG images, we can embed XMP data
        if (function_exists('iptcembed')) {
            $this->embed_xmp_in_jpeg($file_path, $xmp_data);
        }
        
        // Store RSL metadata in WordPress
        update_post_meta($attachment_id, '_rsl_embedded', 1);
        update_post_meta($attachment_id, '_rsl_xmp_data', $xmp_data);
    }
    
    private function embed_rsl_in_pdf($attachment_id, $file_path) {
        $license_data = $this->get_media_license($attachment_id);
        
        if (!$license_data) {
            return;
        }
        
        // For PDFs, we store the RSL metadata and can generate a companion RSL file
        $rsl_xml = $this->license_handler->generate_rsl_xml($license_data);
        
        // Store metadata
        update_post_meta($attachment_id, '_rsl_embedded', 1);
        update_post_meta($attachment_id, '_rsl_xml_data', $rsl_xml);
        
        // Create companion RSL file with security validation
        $rsl_file_path = str_replace('.pdf', '.rsl.xml', $file_path);
        $this->safe_write_rsl_file($rsl_file_path, $rsl_xml);
    }
    
    private function embed_rsl_in_epub($attachment_id, $file_path) {
        $license_data = $this->get_media_license($attachment_id);
        
        if (!$license_data) {
            return;
        }
        
        // For EPUB files, we would need to modify the OPF manifest
        // This is more complex and would require ZIP manipulation
        
        // For now, store metadata and create companion RSL file
        $rsl_xml = $this->license_handler->generate_rsl_xml($license_data);
        
        update_post_meta($attachment_id, '_rsl_embedded', 1);
        update_post_meta($attachment_id, '_rsl_xml_data', $rsl_xml);
        
        $rsl_file_path = str_replace('.epub', '.rsl.xml', $file_path);
        $this->safe_write_rsl_file($rsl_file_path, $rsl_xml);
    }
    
    private function generate_xmp_rsl($license_data, $attachment_id) {
        $file_url = wp_get_attachment_url($attachment_id);
        
        // Override content URL for this specific file
        $license_data['content_url'] = $file_url;
        
        $xmp = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xmp .= '<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="RSL/1.0">' . "\n";
        $xmp .= '  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"' . "\n";
        $xmp .= '           xmlns:rsl="https://rslstandard.org/rsl">' . "\n";
        $xmp .= '    <rdf:Description rdf:about="">' . "\n";
        
        // Generate RSL XML without standalone declaration
        $rsl_xml = $this->license_handler->generate_rsl_xml($license_data, array(
            'standalone' => false
        ));
        
        // Indent RSL XML for XMP embedding
        $rsl_lines = explode("\n", $rsl_xml);
        foreach ($rsl_lines as $line) {
            if (trim($line)) {
                $xmp .= '      ' . $line . "\n";
            }
        }
        
        $xmp .= '    </rdf:Description>' . "\n";
        $xmp .= '  </rdf:RDF>' . "\n";
        $xmp .= '</x:xmpmeta>' . "\n";
        
        return $xmp;
    }
    
    private function embed_xmp_in_jpeg($file_path, $xmp_data) {
        // This is a simplified example - real XMP embedding would require
        // more sophisticated JPEG manipulation
        
        if (!function_exists('exif_read_data')) {
            return false;
        }
        
        // Read current image data with error handling
        $image_data = @file_get_contents($file_path);
        if ($image_data === false) {
            error_log('RSL: Failed to read image file: ' . $file_path);
            return false;
        }
        
        // For demonstration, we'll create a sidecar XMP file
        // In a production environment, you'd use a proper XMP library
        $xmp_file = str_replace('.jpg', '.xmp', $file_path);
        $xmp_file = str_replace('.jpeg', '.xmp', $xmp_file);
        
        $this->safe_write_rsl_file($xmp_file, $xmp_data);
        
        return true;
    }
    
    private function get_media_license($attachment_id) {
        // Check for attachment-specific license
        $attachment_license_id = get_post_meta($attachment_id, '_rsl_license_id', true);
        if ($attachment_license_id) {
            $license_data = $this->license_handler->get_license($attachment_license_id);
            if ($license_data && $license_data['active']) {
                return $license_data;
            }
        }
        
        // Fall back to global license
        $global_license_id = get_option('rsl_global_license_id', 0);
        if ($global_license_id > 0) {
            $license_data = $this->license_handler->get_license($global_license_id);
            if ($license_data && $license_data['active']) {
                return $license_data;
            }
        }
        
        return null;
    }
    
    public function add_rsl_attachment_fields($form_fields, $post) {
        $licenses = $this->license_handler->get_licenses();
        $selected_license = get_post_meta($post->ID, '_rsl_license_id', true);
        
        $options = '<option value="">' . __('Use Global License', 'rsl-licensing') . '</option>';
        foreach ($licenses as $license) {
            $selected = selected($selected_license, $license['id'], false);
            $options .= '<option value="' . esc_attr($license['id']) . '" ' . $selected . '>';
            $options .= esc_html($license['name']);
            $options .= '</option>';
        }
        
        $form_fields['rsl_license'] = array(
            'label' => __('RSL License', 'rsl-licensing'),
            'input' => 'html',
            'html' => '<select name="attachments[' . $post->ID . '][rsl_license_id]">' . $options . '</select>',
            'helps' => __('Select RSL license for this media file.', 'rsl-licensing')
        );
        
        // Show current RSL status
        $is_embedded = get_post_meta($post->ID, '_rsl_embedded', true);
        if ($is_embedded) {
            $form_fields['rsl_status'] = array(
                'label' => __('RSL Status', 'rsl-licensing'),
                'input' => 'html',
                'html' => '<span style="color: green;">' . __('RSL metadata embedded', 'rsl-licensing') . '</span>',
                'helps' => __('This file contains embedded RSL licensing information.', 'rsl-licensing')
            );
        }
        
        return $form_fields;
    }
    
    public function save_rsl_attachment_fields($post, $attachment) {
        if (isset($attachment['rsl_license_id'])) {
            $license_id = intval($attachment['rsl_license_id']);
            if ($license_id > 0) {
                update_post_meta($post['ID'], '_rsl_license_id', $license_id);
            } else {
                delete_post_meta($post['ID'], '_rsl_license_id');
            }
            
            // Re-process the file to embed new license
            if (get_option('rsl_enable_media_metadata', 1)) {
                $this->process_uploaded_media($post['ID']);
            }
        }
        
        return $post;
    }
    
    public function add_media_column($columns) {
        $columns['rsl_license'] = __('RSL License', 'rsl-licensing');
        return $columns;
    }
    
    public function show_media_column($column_name, $post_id) {
        if ($column_name === 'rsl_license') {
            $license_id = get_post_meta($post_id, '_rsl_license_id', true);
            $is_embedded = get_post_meta($post_id, '_rsl_embedded', true);
            
            if ($license_id) {
                $license_data = $this->license_handler->get_license($license_id);
                if ($license_data) {
                    echo '<strong>' . esc_html($license_data['name']) . '</strong>';
                    if ($is_embedded) {
                        echo '<br><span style="color: green; font-size: 11px;">✓ ' . __('Embedded', 'rsl-licensing') . '</span>';
                    }
                    return;
                }
            }
            
            // Check for global license
            $global_license_id = get_option('rsl_global_license_id', 0);
            if ($global_license_id > 0) {
                $license_data = $this->license_handler->get_license($global_license_id);
                if ($license_data) {
                    echo '<em>' . esc_html($license_data['name']) . ' (Global)</em>';
                    if ($is_embedded) {
                        echo '<br><span style="color: green; font-size: 11px;">✓ ' . __('Embedded', 'rsl-licensing') . '</span>';
                    }
                    return;
                }
            }
            
            echo '<span style="color: #666;">' . __('No license', 'rsl-licensing') . '</span>';
        }
    }
    
    private function get_license_xml_url($license_id) {
        return add_query_arg('rsl_license', $license_id, home_url());
    }
    
    public function get_media_rsl_info($attachment_id) {
        $info = array(
            'has_license' => false,
            'license_data' => null,
            'is_embedded' => false,
            'xmp_data' => null,
            'xml_data' => null
        );
        
        $license_data = $this->get_media_license($attachment_id);
        if ($license_data) {
            $info['has_license'] = true;
            $info['license_data'] = $license_data;
        }
        
        $info['is_embedded'] = (bool) get_post_meta($attachment_id, '_rsl_embedded', true);
        $info['xmp_data'] = get_post_meta($attachment_id, '_rsl_xmp_data', true);
        $info['xml_data'] = get_post_meta($attachment_id, '_rsl_xml_data', true);
        
        return $info;
    }
    
    public function generate_media_rsl_xml($attachment_id) {
        $license_data = $this->get_media_license($attachment_id);
        
        if (!$license_data) {
            return null;
        }
        
        // Override content URL to point to the media file
        $license_data['content_url'] = wp_get_attachment_url($attachment_id);
        
        return $this->license_handler->generate_rsl_xml($license_data);
    }
    
    private function safe_write_rsl_file($file_path, $content) {
        // Validate file path to prevent directory traversal
        $file_path = realpath(dirname($file_path)) . '/' . basename($file_path);
        
        // Ensure we're writing within WordPress upload directory
        $upload_dir = wp_upload_dir();
        $upload_path = realpath($upload_dir['path']);
        $target_dir = realpath(dirname($file_path));
        
        if (!$upload_path || !$target_dir || strpos($target_dir, $upload_path) !== 0) {
            error_log('RSL: Attempted to write file outside upload directory: ' . $file_path);
            return false;
        }
        
        // Check if directory is writable
        if (!is_writable(dirname($file_path))) {
            error_log('RSL: Cannot write to directory: ' . dirname($file_path));
            return false;
        }
        
        // Validate file extension
        $allowed_extensions = array('.xml', '.xmp', '.rsl.xml');
        $file_extension = '';
        foreach ($allowed_extensions as $ext) {
            if (substr($file_path, -strlen($ext)) === $ext) {
                $file_extension = $ext;
                break;
            }
        }
        
        if (empty($file_extension)) {
            error_log('RSL: Invalid file extension for RSL file: ' . $file_path);
            return false;
        }
        
        // Attempt to write file
        $bytes_written = @file_put_contents($file_path, $content);
        
        if ($bytes_written === false) {
            error_log('RSL: Failed to write RSL file: ' . $file_path);
            return false;
        }
        
        return true;
    }
}
</file>

<file path="includes/class-rsl-frontend.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_Frontend {
    
    private $license_handler;
    
    public function __construct() {
        $this->license_handler = new RSL_License();
        
        add_action('wp_head', array($this, 'inject_rsl_html'));
        add_action('send_headers', array($this, 'add_rsl_headers'));
        add_action('template_redirect', array($this, 'handle_rsl_xml_requests'));
        
        add_shortcode('rsl_license', array($this, 'license_shortcode'));
    }
    
    public function inject_rsl_html() {
        if (!get_option('rsl_enable_html_injection', 1)) {
            return;
        }
        
        $license_data = $this->get_current_page_license();
        
        if (!$license_data) {
            return;
        }
        
        $this->output_embedded_rsl($license_data);
    }
    
    public function add_rsl_headers() {
        if (!get_option('rsl_enable_http_headers', 1)) {
            return;
        }
        
        $license_data = $this->get_current_page_license();
        
        if (!$license_data) {
            return;
        }
        
        $xml_url = $this->get_license_xml_url($license_data['id']);
        
        if ($xml_url) {
            header('Link: <' . $xml_url . '>; rel="license"; type="application/rsl+xml"');
        }
    }
    
    public function handle_rsl_xml_requests() {
        global $wp_query;
        
        if (isset($_GET['rsl_license']) && is_numeric($_GET['rsl_license'])) {
            $license_id = intval($_GET['rsl_license']);
            $license_data = $this->license_handler->get_license($license_id);
            
            if ($license_data && $license_data['active']) {
                header('Content-Type: application/rsl+xml; charset=UTF-8');
                header('Cache-Control: public, max-age=3600');
                
                echo $this->license_handler->generate_rsl_xml($license_data);
                exit;
            }
        }
        
        if (isset($_GET['rsl_feed'])) {
            $this->output_rsl_feed();
            exit;
        }
    }
    
    public function get_current_page_license() {
        global $post;
        
        // Check for post-specific license first
        if (is_singular() && $post) {
            $post_license_id = get_post_meta($post->ID, '_rsl_license_id', true);
            if ($post_license_id) {
                $license_data = $this->license_handler->get_license($post_license_id);
                if ($license_data && $license_data['active']) {
                    return $this->prepare_license_data($license_data, $post);
                }
            }
        }
        
        // Fall back to global license
        $global_license_id = get_option('rsl_global_license_id', 0);
        if ($global_license_id > 0) {
            $license_data = $this->license_handler->get_license($global_license_id);
            if ($license_data && $license_data['active']) {
                return $this->prepare_license_data($license_data);
            }
        }
        
        return null;
    }
    
    private function prepare_license_data($license_data, $post = null) {
        // Override content URL if specified for post
        if ($post) {
            $override_url = get_post_meta($post->ID, '_rsl_override_content_url', true);
            if (!empty($override_url)) {
                $license_data['content_url'] = $override_url;
            } else {
                // Use current page URL if content_url is empty or "/"
                if (empty($license_data['content_url']) || $license_data['content_url'] === '/') {
                    $license_data['content_url'] = get_permalink($post->ID);
                }
            }
        } else {
            // For non-post pages, use current URL if content_url is empty
            if (empty($license_data['content_url'])) {
                $license_data['content_url'] = home_url(esc_url_raw($_SERVER['REQUEST_URI']));
            } else if ($license_data['content_url'] === '/') {
                $license_data['content_url'] = home_url('/');
            }
        }
        
        return $license_data;
    }
    
    private function output_embedded_rsl($license_data) {
        $namespace = get_option('rsl_default_namespace', 'https://rslstandard.org/rsl');
        
        echo "\n<!-- RSL Licensing Information -->\n";
        echo '<script type="application/rsl+xml">' . "\n";
        
        $xml = $this->license_handler->generate_rsl_xml($license_data, array(
            'namespace' => $namespace,
            'standalone' => false
        ));
        
        echo $xml . "\n";
        echo '</script>' . "\n";
        echo "<!-- End RSL Licensing Information -->\n\n";
    }
    
    private function get_license_xml_url($license_id) {
        $base_url = trailingslashit(home_url());
        return add_query_arg('rsl_license', $license_id, $base_url);
    }
    
    private function output_rsl_feed() {
        $licenses = $this->license_handler->get_licenses(array('active' => 1));
        
        header('Content-Type: application/rss+xml; charset=UTF-8');
        header('Cache-Control: public, max-age=1800');
        
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss xmlns:rsl="https://rslstandard.org/rsl" version="2.0">' . "\n";
        echo '  <channel>' . "\n";
        echo '    <title>' . esc_html(get_bloginfo('name')) . ' - RSL Licenses</title>' . "\n";
        echo '    <link>' . esc_url(home_url()) . '</link>' . "\n";
        echo '    <description>' . esc_html(get_bloginfo('description')) . '</description>' . "\n";
        echo '    <language>' . esc_html(get_bloginfo('language')) . '</language>' . "\n";
        echo '    <lastBuildDate>' . date('r') . '</lastBuildDate>' . "\n";
        
        foreach ($licenses as $license) {
            echo '    <item>' . "\n";
            echo '      <title>' . esc_html($license['name']) . '</title>' . "\n";
            echo '      <link>' . esc_url($this->get_license_xml_url($license['id'])) . '</link>' . "\n";
            echo '      <description>' . esc_html($license['description'] ?: 'RSL License') . '</description>' . "\n";
            echo '      <guid>' . esc_url($this->get_license_xml_url($license['id'])) . '</guid>' . "\n";
            
            if (!empty($license['updated_at'])) {
                echo '      <pubDate>' . date('r', strtotime($license['updated_at'])) . '</pubDate>' . "\n";
            }
            
            // Add RSL content as RSS extension
            $rsl_xml = $this->license_handler->generate_rsl_xml($license, array(
                'namespace' => 'https://rslstandard.org/rsl',
                'standalone' => false
            ));
            
            // Remove the opening rsl tag and add proper namespace prefixes
            $rsl_xml = str_replace('<rsl xmlns="https://rslstandard.org/rsl">', '', $rsl_xml);
            $rsl_xml = str_replace('</rsl>', '', $rsl_xml);
            $rsl_xml = preg_replace('/<(\/?)(content|license|permits|prohibits|payment|standard|custom|amount|legal|schema|copyright|terms)/', '<$1rsl:$2', $rsl_xml);
            
            echo $rsl_xml;
            echo '    </item>' . "\n";
        }
        
        echo '  </channel>' . "\n";
        echo '</rss>' . "\n";
    }
    
    public function license_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'format' => 'link', // link, xml, info
            'text' => __('View License', 'rsl-licensing')
        ), $atts, 'rsl_license');
        
        $license_id = intval($atts['id']);
        
        if ($license_id <= 0) {
            // Use current page license
            $license_data = $this->get_current_page_license();
            if (!$license_data) {
                return '';
            }
            $license_id = $license_data['id'];
        } else {
            $license_data = $this->license_handler->get_license($license_id);
        }
        
        if (!$license_data || !$license_data['active']) {
            return '';
        }
        
        switch ($atts['format']) {
            case 'xml':
                return '<pre><code>' . esc_html($this->license_handler->generate_rsl_xml($license_data)) . '</code></pre>';
                
            case 'info':
                $output = '<div class="rsl-license-info">';
                $output .= '<h4>' . esc_html($license_data['name']) . '</h4>';
                
                if (!empty($license_data['description'])) {
                    $output .= '<p>' . esc_html($license_data['description']) . '</p>';
                }
                
                $output .= '<ul>';
                $output .= '<li><strong>' . __('Payment Type:', 'rsl-licensing') . '</strong> ' . 
                          esc_html(ucfirst(str_replace('-', ' ', $license_data['payment_type']))) . '</li>';
                
                if (!empty($license_data['permits_usage'])) {
                    $output .= '<li><strong>' . __('Permitted Usage:', 'rsl-licensing') . '</strong> ' . 
                              esc_html($license_data['permits_usage']) . '</li>';
                }
                
                if (!empty($license_data['copyright_holder'])) {
                    $output .= '<li><strong>' . __('Copyright:', 'rsl-licensing') . '</strong> ' . 
                              esc_html($license_data['copyright_holder']) . '</li>';
                }
                
                $output .= '</ul>';
                $output .= '</div>';
                
                return $output;
                
            case 'link':
            default:
                $xml_url = $this->get_license_xml_url($license_id);
                return '<a href="' . esc_url($xml_url) . '" class="rsl-license-link" target="_blank">' . 
                       esc_html($atts['text']) . '</a>';
        }
    }
    
    public function get_rsl_feed_url() {
        return add_query_arg('rsl_feed', '1', home_url());
    }
}
</file>

<file path="admin/css/admin.css">
/* RSL Licensing Admin Styles */

/* Core RSL Status Indicators */
.rsl-enabled {
    color: #00a32a;
    font-weight: 600;
}

.rsl-disabled {
    color: #d63638;
    font-weight: 600;
}

/* Dashboard Layout - Native WordPress Grid */
.rsl-dashboard-wrap {
    display: block;
    width: 100%;
    max-width: none;
    margin-top: 20px;
}

.rsl-dashboard-wrap .postbox-container.rsl-full-width {
    width: 100%;
    margin-bottom: 20px;
}

/* Dashboard Rows */
.rsl-dashboard-row {
    display: block;
    width: 100%;
    margin-bottom: 20px;
    overflow: hidden;
}

.rsl-dashboard-row .postbox-container {
    float: left;
    margin-bottom: 20px;
    min-height: 1px;
    box-sizing: border-box;
}

.rsl-dashboard-row .postbox-container.rsl-half-width {
    width: calc(50% - 10px);
    margin-right: 20px;
}

.rsl-dashboard-row .postbox-container.rsl-half-width:last-child {
    margin-right: 0;
}

/* Clearfix for dashboard rows */
.rsl-dashboard-row::after {
    content: "";
    display: table;
    clear: both;
}

/* WordPress Native Postbox Styling */
.rsl-dashboard-wrap .postbox {
    background: #fff;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    min-width: 255px;
    position: relative;
}

.rsl-dashboard-wrap .postbox .postbox-header {
    border-bottom: 1px solid #c3c4c7;
    position: relative;
}

.rsl-dashboard-wrap .postbox .postbox-header .hndle {
    border: none;
    box-shadow: none;
    color: #1d2327;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
    margin: 0;
    padding: 12px;
    position: relative;
}

.rsl-dashboard-wrap .postbox .inside {
    margin: 12px;
    position: relative;
}

/* At a Glance with Quick Actions Layout */
.rsl-at-glance-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.rsl-stats-section {
    flex: 1;
}

.rsl-quick-actions-section {
    flex-shrink: 0;
}

/* At a Glance Widget Styling */
.rsl-dashboard-wrap .main ul {
    list-style: none;
    margin: 0;
    overflow: hidden;
    padding: 0;
}

.rsl-dashboard-wrap .main ul li {
    float: left;
    font-size: 14px;
    margin: 0 15px 0 0;
    line-height: 1.4;
    list-style: none;
    white-space: nowrap;
    width: auto;
}

.rsl-dashboard-wrap .main ul li:after {
    content: "|";
    color: #c3c4c7;
    font-weight: 400;
    margin-left: 15px;
}

.rsl-dashboard-wrap .main ul li:last-child:after {
    content: "";
}

.rsl-dashboard-wrap .main ul li strong {
    color: #1d2327;
    font-weight: 600;
    margin-right: 5px;
}

.rsl-dashboard-wrap .main ul li span:last-child {
    color: #646970;
}

/* Quick Actions Inline */
.rsl-actions-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.rsl-actions-inline .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}

/* Action Buttons */
.rsl-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 8px;
    min-width: 180px;
}

.rsl-actions .button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Table Styling for Integration Status */
.rsl-dashboard-wrap .widefat {
    border: 1px solid #c3c4c7;
    box-shadow: none;
}

.rsl-dashboard-wrap .widefat td,
.rsl-dashboard-wrap .widefat th {
    border: none;
    padding: 8px 12px;
}

.rsl-dashboard-wrap .widefat tbody tr:nth-child(odd) {
    background-color: #f6f7f7;
}

/* Payment Type Tags */
.rsl-payment-tag {
    background: #f0f0f1;
    border-radius: 3px;
    color: #50575e;
    display: inline-block;
    font-size: 11px;
    font-weight: 500;
    padding: 3px 8px;
    text-transform: uppercase;
}

.rsl-payment-type {
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    padding: 2px 8px;
    text-transform: uppercase;
}

.rsl-payment-free {
    background-color: #d1ecf1;
    color: #0c5460;
}

.rsl-payment-purchase,
.rsl-payment-subscription,
.rsl-payment-training,
.rsl-payment-crawl,
.rsl-payment-inference {
    background-color: #fff3cd;
    color: #856404;
}

.rsl-payment-attribution {
    background-color: #d4edda;
    color: #155724;
}

/* Status Indicators */
.rsl-status-active {
    color: #00a32a;
    font-weight: 600;
}

.rsl-status-inactive {
    color: #d63638;
    font-weight: 600;
}

/* Recent License Items */
.rsl-recent-license {
    border-bottom: 1px solid #f0f0f1;
    padding: 8px 0;
}

.rsl-recent-license:last-child {
    border-bottom: none;
}

/* Modal Styles */
.rsl-modal {
    background-color: rgba(0,0,0,0.5);
    height: 100%;
    left: 0;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 100000;
}

.rsl-modal-content {
    background-color: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.3);
    margin: 5% auto;
    max-width: 800px;
    padding: 0;
    width: 80%;
}

.rsl-modal-header {
    align-items: center;
    background-color: #f6f7f7;
    border-bottom: 1px solid #c3c4c7;
    display: flex;
    justify-content: space-between;
    padding: 12px 20px;
}

.rsl-modal-header h3 {
    color: #1d2327;
    font-size: 14px;
    font-weight: 600;
    margin: 0;
}

.rsl-modal-close {
    color: #646970;
    cursor: pointer;
    font-size: 20px;
    font-weight: 400;
    line-height: 1;
    text-decoration: none;
}

.rsl-modal-close:hover,
.rsl-modal-close:focus {
    color: #d63638;
}

.rsl-modal-body {
    padding: 20px;
}

.rsl-modal-body textarea {
    background-color: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.07);
    color: #2c3338;
    font-family: Consolas, Monaco, monospace;
    font-size: 12px;
    line-height: 1.4;
    padding: 10px;
    resize: vertical;
    width: 100%;
}

/* Form Styling */
.form-table th {
    width: 200px;
    vertical-align: top;
}

.form-table .description {
    color: #646970;
    font-style: normal;
    margin-top: 5px;
}

.rsl-multiselect {
    height: 120px !important;
    width: 100% !important;
    max-width: 400px;
}

/* License Info Cards */
.rsl-license-info {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin: 10px 0;
    padding: 15px;
}

.rsl-license-info h4 {
    color: #1d2327;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 10px;
    margin-top: 0;
}

.rsl-license-info ul {
    margin: 10px 0;
    padding-left: 20px;
}

.rsl-license-info li {
    margin-bottom: 5px;
}

.rsl-license-link {
    background-color: #2271b1;
    border-radius: 3px;
    color: #fff;
    display: inline-block;
    font-size: 13px;
    padding: 6px 12px;
    text-decoration: none;
}

.rsl-license-link:hover,
.rsl-license-link:focus {
    background-color: #135e96;
    color: #fff;
}

/* WordPress List Table Enhancements */
.wp-list-table .rsl-payment-type {
    margin-left: 8px;
}

/* Admin Page Icons */
.rsl-admin-icon {
    height: 32px;
    margin-right: 10px;
    vertical-align: middle;
    width: auto;
    max-width: 120px;
    object-fit: contain;
}

/* License Content URL */
.rsl-license-content-url {
    color: #646970;
    font-size: 12px;
    margin-top: 4px;
}

/* View All Links */
.rsl-view-all-link {
    margin-top: 16px;
}

/* Button Spacing */
.rsl-button-gap {
    margin-left: 8px;
}

/* Header Links */
.rsl-header-link {
    color: #2271b1;
    font-size: 13px;
    font-weight: 400;
    text-decoration: none;
    margin-left: 10px;
}

.rsl-header-link:hover,
.rsl-header-link:focus {
    color: #135e96;
    text-decoration: underline;
}

/* Page Headers - Fix margin bottom */
.wrap h1.wp-heading-inline {
    margin-bottom: 20px;
}

/* Utility Classes */
.rsl-hidden {
    display: none !important;
}

/* Responsive Design */
@media screen and (max-width: 782px) {
    .rsl-dashboard-row .postbox-container {
        float: none;
        margin-right: 0;
        width: 100% !important;
    }
    
    .rsl-dashboard-row .postbox-container.rsl-half-width {
        width: 100% !important;
        margin-right: 0;
    }
    
    .rsl-modal-content {
        margin: 10% auto;
        width: 95%;
    }
    
    .rsl-multiselect {
        max-width: none;
    }
    
    .form-table th,
    .form-table td {
        display: block;
        width: auto;
    }
    
    .form-table th {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .rsl-actions .button {
        margin-bottom: 12px;
        min-width: auto;
        width: 100%;
    }
    
    .rsl-dashboard-wrap .main ul li {
        float: none;
        display: block;
        margin-bottom: 8px;
        width: auto;
    }
    
    .rsl-dashboard-wrap .main ul li:after {
        display: none;
    }
    
    .rsl-at-glance-wrapper {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .rsl-actions-inline {
        width: 100%;
    }
    
    .rsl-actions-inline .button {
        flex: 1;
        justify-content: center;
        min-width: auto;
    }
}

@media screen and (max-width: 600px) {
    .rsl-dashboard-wrap .postbox .inside {
        margin: 8px;
    }
    
    .rsl-dashboard-wrap .postbox .postbox-header .hndle {
        padding: 8px 12px;
    }
}
</file>

<file path="admin/templates/admin-settings.php">
<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <img src="<?php echo RSL_PLUGIN_URL . 'admin/images/rsl-logo.png'; ?>" 
             alt="RSL" class="rsl-admin-icon">
        <?php _e('RSL Licensing Settings', 'rsl-licensing'); ?>
    </h1>
    <hr class="wp-header-end">
    
    <?php
    // Display admin notices
    if (function_exists('settings_errors')) {
        settings_errors();
    }
    do_action('admin_notices');
    ?>
    
    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully.', 'rsl-licensing'); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('rsl_settings');
        do_settings_sections('rsl_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rsl_global_license_id"><?php _e('Global License', 'rsl-licensing'); ?></label>
                </th>
                <td>
                    <select name="rsl_global_license_id" id="rsl_global_license_id">
                        <option value="0"><?php _e('No global license', 'rsl-licensing'); ?></option>
                        <?php foreach ($licenses as $license) : ?>
                            <option value="<?php echo esc_attr($license['id']); ?>" 
                                    <?php selected($global_license_id, $license['id']); ?>>
                                <?php echo esc_html($license['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Select a license to apply site-wide. Individual posts/pages can override this.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('HTML Injection', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_html_injection" value="1" 
                               <?php checked(get_option('rsl_enable_html_injection', 1)); ?>>
                        <?php _e('Enable HTML head injection', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Automatically inject RSL license information into HTML head section.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('HTTP Headers', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_http_headers" value="1" 
                               <?php checked(get_option('rsl_enable_http_headers', 1)); ?>>
                        <?php _e('Enable HTTP Link headers', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Add RSL license information to HTTP response headers.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('robots.txt Integration', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_robots_txt" value="1" 
                               <?php checked(get_option('rsl_enable_robots_txt', 1)); ?>>
                        <?php _e('Enable robots.txt license directive', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Add License directive to robots.txt file.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('RSS Feed Integration', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_rss_feed" value="1" 
                               <?php checked(get_option('rsl_enable_rss_feed', 1)); ?>>
                        <?php _e('Enable RSS feed RSL integration', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Add RSL licensing information to RSS feeds.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Media Metadata', 'rsl-licensing'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rsl_enable_media_metadata" value="1" 
                               <?php checked(get_option('rsl_enable_media_metadata', 1)); ?>>
                        <?php _e('Enable media file metadata embedding', 'rsl-licensing'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Embed RSL license information in uploaded media files.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="rsl_default_namespace"><?php _e('RSL Namespace', 'rsl-licensing'); ?></label>
                </th>
                <td>
                    <input type="url" name="rsl_default_namespace" id="rsl_default_namespace" 
                           value="<?php echo esc_attr(get_option('rsl_default_namespace', 'https://rslstandard.org/rsl')); ?>" 
                           class="regular-text" />
                    <p class="description">
                        <?php _e('RSL XML namespace URI. Use default unless you have a custom implementation.', 'rsl-licensing'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
</file>

<file path="includes/class-rsl-license.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_License {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rsl_licenses';
    }
    
    public function create_license($data) {
        global $wpdb;
        
        $defaults = array(
            'name' => '',
            'description' => '',
            'content_url' => '',
            'server_url' => '',
            'encrypted' => 0,
            'lastmod' => current_time('mysql'),
            'permits_usage' => '',
            'permits_user' => '',
            'permits_geo' => '',
            'prohibits_usage' => '',
            'prohibits_user' => '',
            'prohibits_geo' => '',
            'payment_type' => 'free',
            'standard_url' => '',
            'custom_url' => '',
            'amount' => 0,
            'currency' => 'USD',
            'warranty' => '',
            'disclaimer' => '',
            'schema_url' => '',
            'copyright_holder' => '',
            'copyright_type' => '',
            'contact_email' => '',
            'contact_url' => '',
            'terms_url' => '',
            'active' => 1
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['name']) || empty($data['content_url'])) {
            error_log('RSL: Cannot create license - name and content_url are required');
            return false;
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s',
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'
            )
        );
        
        if ($result === false) {
            error_log('RSL: Database error creating license: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    public function get_license($id) {
        global $wpdb;
        
        $id = intval($id);
        if ($id <= 0) {
            return null;
        }
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        if ($wpdb->last_error) {
            error_log('RSL: Database error getting license: ' . $wpdb->last_error);
            return null;
        }
        
        return $result;
    }
    
    public function get_licenses($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'active' => 1,
            'limit' => -1,
            'offset' => 0,
            'orderby' => 'name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Validate and sanitize orderby to prevent SQL injection
        $allowed_orderby = array('id', 'name', 'created_at', 'updated_at', 'lastmod', 'payment_type');
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'name';
        }
        
        // Validate order parameter
        $args['order'] = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        // Validate numeric parameters
        $args['limit'] = max(-1, intval($args['limit']));
        $args['offset'] = max(0, intval($args['offset']));
        
        $where = array();
        $values = array();
        
        if ($args['active'] !== null) {
            $where[] = "active = %d";
            $values[] = intval($args['active']);
        }
        
        $sql = "SELECT * FROM {$this->table_name}";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY `{$args['orderby']}` {$args['order']}";
        
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d";
            $values[] = $args['limit'];
            
            if ($args['offset'] > 0) {
                $sql .= " OFFSET %d";
                $values[] = $args['offset'];
            }
        }
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Add error handling
        if ($wpdb->last_error) {
            error_log('RSL License Query Error: ' . $wpdb->last_error);
            return array();
        }
        
        return $results ? $results : array();
    }
    
    public function update_license($id, $data) {
        global $wpdb;
        
        $id = intval($id);
        if ($id <= 0) {
            error_log('RSL: Invalid license ID for update: ' . $id);
            return false;
        }
        
        $data['updated_at'] = current_time('mysql');
        
        $format = array(
            '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 
            '%d', '%s', '%s'
        );
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            error_log('RSL: Database error updating license ID ' . $id . ': ' . $wpdb->last_error);
            return false;
        }
        
        return $result !== false;
    }
    
    public function delete_license($id) {
        global $wpdb;
        
        $id = intval($id);
        if ($id <= 0) {
            error_log('RSL: Invalid license ID for deletion: ' . $id);
            return false;
        }
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            error_log('RSL: Database error deleting license ID ' . $id . ': ' . $wpdb->last_error);
            return false;
        }
        
        return $result !== false;
    }
    
    public function generate_rsl_xml($license_data, $options = array()) {
        $defaults = array(
            'namespace' => 'https://rslstandard.org/rsl',
            'standalone' => true
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $xml = '';
        
        if ($options['standalone']) {
            $xml .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        }
        
        $xml .= '<rsl xmlns="' . esc_attr($options['namespace']) . '">' . "\n";
        $xml .= '  <content url="' . esc_attr($license_data['content_url']) . '"';
        
        if (!empty($license_data['server_url'])) {
            $xml .= ' server="' . esc_attr($license_data['server_url']) . '"';
        }
        
        if (!empty($license_data['encrypted']) && $license_data['encrypted'] == 1) {
            $xml .= ' encrypted="true"';
        }
        
        if (!empty($license_data['lastmod'])) {
            $xml .= ' lastmod="' . esc_attr(date('c', strtotime($license_data['lastmod']))) . '"';
        }
        
        $xml .= '>' . "\n";
        
        if (!empty($license_data['schema_url'])) {
            $xml .= '    <schema>' . esc_html($license_data['schema_url']) . '</schema>' . "\n";
        }
        
        if (!empty($license_data['copyright_holder'])) {
            $xml .= '    <copyright';
            if (!empty($license_data['copyright_type'])) {
                $xml .= ' type="' . esc_attr($license_data['copyright_type']) . '"';
            }
            if (!empty($license_data['contact_email'])) {
                $xml .= ' contactEmail="' . esc_attr($license_data['contact_email']) . '"';
            }
            if (!empty($license_data['contact_url'])) {
                $xml .= ' contactUrl="' . esc_attr($license_data['contact_url']) . '"';
            }
            $xml .= '>' . esc_html($license_data['copyright_holder']) . '</copyright>' . "\n";
        }
        
        if (!empty($license_data['terms_url'])) {
            $xml .= '    <terms>' . esc_html($license_data['terms_url']) . '</terms>' . "\n";
        }
        
        $xml .= '    <license>' . "\n";
        
        if (!empty($license_data['permits_usage'])) {
            $xml .= '      <permits type="usage">' . esc_html($license_data['permits_usage']) . '</permits>' . "\n";
        }
        
        if (!empty($license_data['permits_user'])) {
            $xml .= '      <permits type="user">' . esc_html($license_data['permits_user']) . '</permits>' . "\n";
        }
        
        if (!empty($license_data['permits_geo'])) {
            $xml .= '      <permits type="geo">' . esc_html($license_data['permits_geo']) . '</permits>' . "\n";
        }
        
        if (!empty($license_data['prohibits_usage'])) {
            $xml .= '      <prohibits type="usage">' . esc_html($license_data['prohibits_usage']) . '</prohibits>' . "\n";
        }
        
        if (!empty($license_data['prohibits_user'])) {
            $xml .= '      <prohibits type="user">' . esc_html($license_data['prohibits_user']) . '</prohibits>' . "\n";
        }
        
        if (!empty($license_data['prohibits_geo'])) {
            $xml .= '      <prohibits type="geo">' . esc_html($license_data['prohibits_geo']) . '</prohibits>' . "\n";
        }
        
        if (!empty($license_data['payment_type']) && $license_data['payment_type'] !== 'free') {
            $xml .= '      <payment type="' . esc_attr($license_data['payment_type']) . '">' . "\n";
            
            if (!empty($license_data['standard_url'])) {
                $xml .= '        <standard>' . esc_html($license_data['standard_url']) . '</standard>' . "\n";
            }
            
            if (!empty($license_data['custom_url'])) {
                $xml .= '        <custom>' . esc_html($license_data['custom_url']) . '</custom>' . "\n";
            }
            
            if (!empty($license_data['amount']) && $license_data['amount'] > 0) {
                $xml .= '        <amount currency="' . esc_attr($license_data['currency']) . '">' . 
                        esc_html($license_data['amount']) . '</amount>' . "\n";
            }
            
            $xml .= '      </payment>' . "\n";
        } else {
            $xml .= '      <payment type="free"/>' . "\n";
        }
        
        if (!empty($license_data['warranty'])) {
            $xml .= '      <legal type="warranty">' . esc_html($license_data['warranty']) . '</legal>' . "\n";
        }
        
        if (!empty($license_data['disclaimer'])) {
            $xml .= '      <legal type="disclaimer">' . esc_html($license_data['disclaimer']) . '</legal>' . "\n";
        }
        
        $xml .= '    </license>' . "\n";
        $xml .= '  </content>' . "\n";
        $xml .= '</rsl>';
        
        return $xml;
    }
    
    public function get_usage_options() {
        return array(
            'all' => __('All automated processing', 'rsl-licensing'),
            'train-ai' => __('Train AI model', 'rsl-licensing'),
            'train-genai' => __('Train generative AI model', 'rsl-licensing'),
            'ai-use' => __('Use as AI input (RAG)', 'rsl-licensing'),
            'ai-summarize' => __('AI summarization', 'rsl-licensing'),
            'search' => __('Search indexing', 'rsl-licensing')
        );
    }
    
    public function get_user_options() {
        return array(
            'commercial' => __('Commercial use', 'rsl-licensing'),
            'non-commercial' => __('Non-commercial use', 'rsl-licensing'),
            'education' => __('Educational use', 'rsl-licensing'),
            'government' => __('Government use', 'rsl-licensing'),
            'personal' => __('Personal use', 'rsl-licensing')
        );
    }
    
    public function get_payment_options() {
        return array(
            'free' => __('Free', 'rsl-licensing'),
            'purchase' => __('One-time purchase', 'rsl-licensing'),
            'subscription' => __('Subscription', 'rsl-licensing'),
            'training' => __('Per training use', 'rsl-licensing'),
            'crawl' => __('Per crawl', 'rsl-licensing'),
            'inference' => __('Per inference', 'rsl-licensing'),
            'attribution' => __('Attribution required', 'rsl-licensing'),
            'royalty' => __('Royalty', 'rsl-licensing')
        );
    }
    
    public function get_warranty_options() {
        return array(
            'ownership' => __('Ownership rights', 'rsl-licensing'),
            'authority' => __('Authorization to license', 'rsl-licensing'),
            'no-infringement' => __('No third-party infringement', 'rsl-licensing'),
            'privacy-consent' => __('Privacy consents obtained', 'rsl-licensing'),
            'no-malware' => __('Free from malware', 'rsl-licensing')
        );
    }
    
    public function get_disclaimer_options() {
        return array(
            'as-is' => __('Provided "as is"', 'rsl-licensing'),
            'no-warranty' => __('No warranties', 'rsl-licensing'),
            'no-liability' => __('No liability', 'rsl-licensing'),
            'no-indemnity' => __('No indemnification', 'rsl-licensing')
        );
    }
}
</file>

<file path="admin/templates/admin-dashboard.php">
<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <img src="<?php echo RSL_PLUGIN_URL . 'admin/images/rsl-logo.png'; ?>" 
             alt="RSL" class="rsl-admin-icon">
        <?php _e('RSL Licensing', 'rsl-licensing'); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>" class="page-title-action">
        <?php _e('Add New License', 'rsl-licensing'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php
    // Display admin notices
    if (function_exists('settings_errors')) {
        settings_errors();
    }
    do_action('admin_notices');
    ?>
    
    <?php if (isset($_GET['settings-updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully.', 'rsl-licensing'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="rsl-dashboard-wrap">
        <!-- At A Glance with Quick Actions -->
        <div class="postbox-container rsl-full-width">
            <div class="meta-box-sortables">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle"><?php _e('At a Glance', 'rsl-licensing'); ?></h2>
                    </div>
                    <div class="inside">
                        <div class="rsl-at-glance-wrapper">
                            <div class="rsl-stats-section">
                                <div class="main">
                                    <ul>
                                        <li>
                                            <strong><?php echo $total_licenses; ?></strong> 
                                            <span><?php _e('Total Licenses', 'rsl-licensing'); ?></span>
                                        </li>
                                        <li>
                                            <strong><?php echo $active_licenses; ?></strong> 
                                            <span><?php _e('Active Licenses', 'rsl-licensing'); ?></span>
                                        </li>
                                        <li>
                                            <span class="<?php echo $global_license_id > 0 ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                                <?php echo $global_license_id > 0 ? '✓' : '×'; ?>
                                            </span>
                                            <span><?php _e('Global License Configured', 'rsl-licensing'); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="rsl-quick-actions-section">
                                <div class="rsl-actions-inline">
                                    <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>" 
                                       class="button button-primary">
                                        <span class="dashicons dashicons-plus"></span>
                                        <?php _e('Create License', 'rsl-licensing'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=rsl-licenses'); ?>" 
                                       class="button button-secondary">
                                        <span class="dashicons dashicons-list-view"></span>
                                        <?php _e('Manage Licenses', 'rsl-licensing'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=rsl-settings'); ?>" 
                                       class="button button-secondary">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                        <?php _e('Settings', 'rsl-licensing'); ?>
                                    </a>
                                    <a href="https://rslstandard.org" target="_blank" class="button button-secondary">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php _e('RSL Standard', 'rsl-licensing'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integration Status -->
        <div class="postbox-container rsl-full-width">
            <div class="meta-box-sortables">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">
                            <?php _e('Integration Status', 'rsl-licensing'); ?>
                            <a href="<?php echo admin_url('admin.php?page=rsl-settings'); ?>" class="rsl-header-link">
                                <?php _e('Go to Settings', 'rsl-licensing'); ?>
                            </a>
                        </h2>
                    </div>
                    <div class="inside">
                        <table class="widefat striped">
                            <tbody>
                                <tr>
                                    <td><?php _e('HTML Head Injection', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_html_injection', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_html_injection', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('HTTP Link Headers', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_http_headers', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_http_headers', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('robots.txt Integration', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_robots_txt', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_robots_txt', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('RSS Feed Enhancement', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_rss_feed', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_rss_feed', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('Media Metadata', 'rsl-licensing'); ?></td>
                                    <td>
                                        <span class="<?php echo get_option('rsl_enable_media_metadata', 1) ? 'rsl-enabled' : 'rsl-disabled'; ?>">
                                            <?php echo get_option('rsl_enable_media_metadata', 1) ? __('Enabled', 'rsl-licensing') : __('Disabled', 'rsl-licensing'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php _e('WooCommerce', 'rsl-licensing'); ?></td>
                                    <td>
                                        <?php if (class_exists('WooCommerce')) : ?>
                                            <span class="rsl-enabled">✓ <?php _e('Active', 'rsl-licensing'); ?></span>
                                            <?php if (class_exists('WC_Subscriptions')) : ?>
                                                <br><small><?php _e('Subscriptions: Available', 'rsl-licensing'); ?></small>
                                            <?php else : ?>
                                                <br><small style="color: #856404;"><?php _e('Subscriptions: Extension needed', 'rsl-licensing'); ?></small>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="rsl-disabled">✗ <?php _e('Not installed', 'rsl-licensing'); ?></span>
                                            <br><small><?php _e('Required for paid licensing', 'rsl-licensing'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Row 1 -->
        
        <!-- Row 2 -->
        <div class="rsl-dashboard-row">
            <div class="postbox-container rsl-half-width">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('Recent Licenses', 'rsl-licensing'); ?></h2>
                        </div>
                        <div class="inside">
                            <?php if (!empty($licenses)) : ?>
                                <?php 
                                $recent_licenses = array_slice(array_reverse($licenses), 0, 5);
                                foreach ($recent_licenses as $license) : 
                                ?>
                                    <div class="rsl-recent-license">
                                        <div>
                                            <strong><?php echo esc_html($license['name']); ?></strong>
                                            <span class="rsl-payment-tag">
                                                <?php echo esc_html(ucfirst(str_replace('-', ' ', $license['payment_type']))); ?>
                                            </span>
                                        </div>
                                        <div class="rsl-license-content-url">
                                            <?php echo esc_html($license['content_url']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <p class="rsl-view-all-link">
                                    <a href="<?php echo admin_url('admin.php?page=rsl-licenses'); ?>">
                                        <?php _e('View all licenses →', 'rsl-licensing'); ?>
                                    </a>
                                </p>
                            <?php else : ?>
                                <p>
                                    <?php _e('No licenses created yet.', 'rsl-licensing'); ?>
                                    <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>">
                                        <?php _e('Create your first license', 'rsl-licensing'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="postbox-container rsl-half-width">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('About RSL', 'rsl-licensing'); ?></h2>
                        </div>
                        <div class="inside">
                            <p>
                                <?php _e('Really Simple Licensing (RSL) is a machine-readable format for defining licensing terms for digital content. It enables content owners to specify how their content can be used by AI systems, search engines, and other automated tools.', 'rsl-licensing'); ?>
                            </p>
                            
                            <p>
                                <a href="https://rslstandard.org" target="_blank" class="button">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php _e('RSL Standard', 'rsl-licensing'); ?>
                                </a>
                                <a href="https://rslcollective.org" target="_blank" class="button rsl-button-gap">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php _e('RSL Collective', 'rsl-licensing'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('System Status', 'rsl-licensing'); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="widefat">
                                <tbody>
                                    <tr>
                                        <td><?php _e('WordPress', 'rsl-licensing'); ?></td>
                                        <td><?php echo get_bloginfo('version'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php _e('PHP', 'rsl-licensing'); ?></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php _e('RSL Plugin', 'rsl-licensing'); ?></td>
                                        <td><?php echo RSL_PLUGIN_VERSION; ?></td>
                                    </tr>
                                    <?php if (function_exists('curl_version')) : ?>
                                    <tr>
                                        <td><?php _e('cURL Support', 'rsl-licensing'); ?></td>
                                        <td><span class="rsl-enabled">✓</span></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</file>

<file path="admin/templates/admin-licenses.php">
<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <img src="<?php echo RSL_PLUGIN_URL . 'admin/images/rsl-logo.png'; ?>" 
             alt="RSL" class="rsl-admin-icon">
        <?php _e('RSL Licenses', 'rsl-licensing'); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>" class="page-title-action">
        <?php _e('Add New License', 'rsl-licensing'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php
    // Display admin notices
    if (function_exists('settings_errors')) {
        settings_errors();
    }
    do_action('admin_notices');
    ?>
    
    <div id="rsl-message" class="notice rsl-hidden"></div>
    
    <?php if (empty($licenses)) : ?>
        <div class="notice notice-info">
            <p>
                <?php _e('No licenses found.', 'rsl-licensing'); ?>
                <a href="<?php echo admin_url('admin.php?page=rsl-add-license'); ?>">
                    <?php _e('Create your first license', 'rsl-licensing'); ?>
                </a>
            </p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('Name', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Content URL', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Payment Type', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Usage Permits', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Status', 'rsl-licensing'); ?></th>
                    <th scope="col"><?php _e('Actions', 'rsl-licensing'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($license['name']); ?></strong>
                            <?php if (!empty($license['description'])) : ?>
                                <br><small><?php echo esc_html($license['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $content_url = $license['content_url'];
                            if (strlen($content_url) > 50) {
                                echo esc_html(substr($content_url, 0, 50) . '...');
                            } else {
                                echo esc_html($content_url);
                            }
                            ?>
                        </td>
                        <td>
                            <span class="rsl-payment-type rsl-payment-<?php echo esc_attr($license['payment_type']); ?>">
                                <?php echo esc_html(ucfirst(str_replace('-', ' ', $license['payment_type']))); ?>
                            </span>
                            <?php if (!empty($license['amount']) && $license['amount'] > 0) : ?>
                                <br><small>$<?php echo esc_html(number_format($license['amount'], 2) . ' ' . $license['currency']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $permits = array();
                            if (!empty($license['permits_usage'])) {
                                $permits[] = 'Usage: ' . $license['permits_usage'];
                            }
                            if (!empty($license['permits_user'])) {
                                $permits[] = 'User: ' . $license['permits_user'];
                            }
                            if (!empty($license['permits_geo'])) {
                                $permits[] = 'Geo: ' . $license['permits_geo'];
                            }
                            
                            if (!empty($permits)) {
                                echo '<small>' . esc_html(implode('; ', $permits)) . '</small>';
                            } else {
                                echo '<em>' . __('All permitted', 'rsl-licensing') . '</em>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($license['active']) : ?>
                                <span class="rsl-status-active"><?php _e('Active', 'rsl-licensing'); ?></span>
                            <?php else : ?>
                                <span class="rsl-status-inactive"><?php _e('Inactive', 'rsl-licensing'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=rsl-add-license&edit=' . $license['id']); ?>" 
                               class="button button-small">
                                <?php _e('Edit', 'rsl-licensing'); ?>
                            </a>
                            
                            <button type="button" class="button button-small rsl-generate-xml" 
                                    data-license-id="<?php echo esc_attr($license['id']); ?>">
                                <?php _e('Generate XML', 'rsl-licensing'); ?>
                            </button>
                            
                            <button type="button" class="button button-small button-link-delete rsl-delete-license" 
                                    data-license-id="<?php echo esc_attr($license['id']); ?>"
                                    data-license-name="<?php echo esc_attr($license['name']); ?>">
                                <?php _e('Delete', 'rsl-licensing'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="rsl-xml-modal" class="rsl-modal rsl-hidden">
    <div class="rsl-modal-content">
        <div class="rsl-modal-header">
            <h3><?php _e('Generated RSL XML', 'rsl-licensing'); ?></h3>
            <span class="rsl-modal-close">&times;</span>
        </div>
        <div class="rsl-modal-body">
            <textarea id="rsl-xml-content" rows="20" cols="80" readonly></textarea>
            <p>
                <button type="button" id="rsl-copy-xml" class="button button-primary">
                    <?php _e('Copy to Clipboard', 'rsl-licensing'); ?>
                </button>
                <button type="button" id="rsl-download-xml" class="button">
                    <?php _e('Download XML', 'rsl-licensing'); ?>
                </button>
            </p>
        </div>
    </div>
</div>
</file>

<file path="admin/templates/admin-add-license.php">
<?php
if (!defined('ABSPATH')) {
    exit;
}

$license_handler = new RSL_License();
$is_edit = !empty($license_data);
$title = $is_edit ? __('Edit RSL License', 'rsl-licensing') : __('Add RSL License', 'rsl-licensing');
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <img src="<?php echo RSL_PLUGIN_URL . 'admin/images/rsl-logo.png'; ?>" 
             alt="RSL" class="rsl-admin-icon">
        <?php echo esc_html($title); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=rsl-licenses'); ?>" class="page-title-action">
        <?php _e('View All Licenses', 'rsl-licensing'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php
    // Display admin notices
    if (function_exists('settings_errors')) {
        settings_errors();
    }
    do_action('admin_notices');
    ?>
    
    <div id="rsl-message" class="notice rsl-hidden"></div>
    
    <form id="rsl-license-form" method="post">
        <?php wp_nonce_field('rsl_license_form', 'rsl_nonce'); ?>
        
        <?php if ($is_edit) : ?>
            <input type="hidden" name="license_id" value="<?php echo esc_attr($license_id); ?>">
        <?php endif; ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="name"><?php _e('License Name', 'rsl-licensing'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" id="name" name="name" class="regular-text" required
                               value="<?php echo esc_attr($license_data['name'] ?? ''); ?>">
                        <p class="description"><?php _e('A descriptive name for this license configuration.', 'rsl-licensing'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="description"><?php _e('Description', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <textarea id="description" name="description" rows="3" class="large-text"><?php echo esc_textarea($license_data['description'] ?? ''); ?></textarea>
                        <p class="description"><?php _e('Optional description of this license.', 'rsl-licensing'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="content_url"><?php _e('Content URL', 'rsl-licensing'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" id="content_url" name="content_url" class="regular-text" required
                               value="<?php echo esc_attr($license_data['content_url'] ?? ''); ?>"
                               placeholder="https://example.com/content/ or / for site root">
                        <p class="description">
                            <?php _e('URL pattern for licensed content. Use "/" for entire site, or specific paths like "/images/". Supports wildcards (* and $).', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="server_option"><?php _e('License Server', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('License Server Options', 'rsl-licensing'); ?></legend>
                            
                            <p>
                                <label>
                                    <input type="radio" name="server_option" value="builtin" 
                                           <?php checked(empty($license_data['server_url']) || parse_url($license_data['server_url'] ?? '', PHP_URL_HOST) === parse_url(home_url(), PHP_URL_HOST)); ?>>
                                    <strong><?php _e('Built-in License Server', 'rsl-licensing'); ?></strong> (<?php _e('Recommended', 'rsl-licensing'); ?>)
                                </label>
                                <br>
                                <span class="description" style="margin-left: 25px;">
                                    <?php _e('Use this WordPress site as the license server. Handles free licenses immediately and integrates with WooCommerce for paid licensing.', 'rsl-licensing'); ?>
                                </span>
                            </p>
                            
                            <p>
                                <label>
                                    <input type="radio" name="server_option" value="external" 
                                           <?php checked(!empty($license_data['server_url']) && parse_url($license_data['server_url'] ?? '', PHP_URL_HOST) !== parse_url(home_url(), PHP_URL_HOST)); ?>>
                                    <strong><?php _e('External License Server', 'rsl-licensing'); ?></strong>
                                </label>
                                <br>
                                <span class="description" style="margin-left: 25px;">
                                    <?php _e('Use an external RSL License Server (e.g., RSL Collective) for centralized licensing and payment processing.', 'rsl-licensing'); ?>
                                </span>
                            </p>
                            
                            <div id="external_server_url_field" style="margin-top: 15px; padding-left: 25px; display: none;">
                                <label for="server_url"><?php _e('External Server URL:', 'rsl-licensing'); ?></label><br>
                                <input type="url" id="server_url" name="server_url" class="regular-text"
                                       value="<?php echo esc_attr($license_data['server_url'] ?? ''); ?>"
                                       placeholder="https://rslcollective.org/api">
                                <p class="description">
                                    <?php _e('Enter the URL of the external RSL License Server API endpoint.', 'rsl-licensing'); ?>
                                </p>
                            </div>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Content Encryption', 'rsl-licensing'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="encrypted" value="1" 
                                   <?php checked($license_data['encrypted'] ?? 0, 1); ?>>
                            <?php _e('Content is encrypted', 'rsl-licensing'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Check if the licensed content requires decryption keys from the license server.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Permissions', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="permits_usage"><?php _e('Permitted Usage', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="permits_usage" name="permits_usage" multiple class="rsl-multiselect">
                            <?php 
                            $selected_usage = explode(',', $license_data['permits_usage'] ?? '');
                            foreach ($license_handler->get_usage_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_usage), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select permitted usage types. Leave empty to permit all usage types.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="permits_user"><?php _e('Permitted Users', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="permits_user" name="permits_user" multiple class="rsl-multiselect">
                            <?php 
                            $selected_user = explode(',', $license_data['permits_user'] ?? '');
                            foreach ($license_handler->get_user_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_user), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select permitted user types. Leave empty to permit all user types.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="permits_geo"><?php _e('Permitted Geography', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="permits_geo" name="permits_geo" class="regular-text"
                               value="<?php echo esc_attr($license_data['permits_geo'] ?? ''); ?>"
                               placeholder="US,EU,CA">
                        <p class="description">
                            <?php _e('Comma-separated list of permitted countries/regions (ISO 3166-1 alpha-2 codes).', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Restrictions', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="prohibits_usage"><?php _e('Prohibited Usage', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="prohibits_usage" name="prohibits_usage" multiple class="rsl-multiselect">
                            <?php 
                            $selected_prohibited = explode(',', $license_data['prohibits_usage'] ?? '');
                            foreach ($license_handler->get_usage_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_prohibited), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select explicitly prohibited usage types.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="prohibits_user"><?php _e('Prohibited Users', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="prohibits_user" name="prohibits_user" multiple class="rsl-multiselect">
                            <?php 
                            $selected_prohibited_user = explode(',', $license_data['prohibits_user'] ?? '');
                            foreach ($license_handler->get_user_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_prohibited_user), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select explicitly prohibited user types.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="prohibits_geo"><?php _e('Prohibited Geography', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="prohibits_geo" name="prohibits_geo" class="regular-text"
                               value="<?php echo esc_attr($license_data['prohibits_geo'] ?? ''); ?>"
                               placeholder="CN,RU">
                        <p class="description">
                            <?php _e('Comma-separated list of prohibited countries/regions (ISO 3166-1 alpha-2 codes).', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Payment & Compensation', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="payment_type"><?php _e('Payment Type', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="payment_type" name="payment_type" class="regular-text">
                            <?php foreach ($license_handler->get_payment_options() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected($license_data['payment_type'] ?? 'free', $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select the payment model for this license. Set amount to 0 for free licenses of any type.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr id="payment_amount_row" style="display: none;">
                    <th scope="row">
                        <label for="amount"><?php _e('Amount', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="amount" name="amount" step="0.01" min="0" class="small-text"
                               value="<?php echo esc_attr($license_data['amount'] ?? '0'); ?>">
                        <select name="currency" class="regular-text">
                            <option value="USD" <?php selected($license_data['currency'] ?? 'USD', 'USD'); ?>>USD</option>
                            <option value="EUR" <?php selected($license_data['currency'] ?? 'USD', 'EUR'); ?>>EUR</option>
                            <option value="GBP" <?php selected($license_data['currency'] ?? 'USD', 'GBP'); ?>>GBP</option>
                            <option value="BTC" <?php selected($license_data['currency'] ?? 'USD', 'BTC'); ?>>BTC</option>
                        </select>
                        <p class="description">
                            <?php _e('Set to 0 for free licenses. Amounts > 0 require WooCommerce for payment processing.', 'rsl-licensing'); ?>
                            <?php if (!$woocommerce_active) : ?>
                                <br><span style="color: #d63638;">
                                    <strong><?php _e('WooCommerce not installed:', 'rsl-licensing'); ?></strong>
                                    <?php _e('Only amount = 0 will work for token generation.', 'rsl-licensing'); ?>
                                    <a href="<?php echo admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'); ?>">
                                        <?php _e('Install WooCommerce', 'rsl-licensing'); ?>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="standard_url"><?php _e('Standard License URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="standard_url" name="standard_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['standard_url'] ?? ''); ?>"
                               placeholder="https://creativecommons.org/licenses/by/4.0/">
                        <p class="description">
                            <?php _e('URL to standard license terms (e.g., Creative Commons, RSL Collective).', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="custom_url"><?php _e('Custom License URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="custom_url" name="custom_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['custom_url'] ?? ''); ?>">
                        <p class="description">
                            <?php _e('URL to custom licensing terms and contact information.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Legal Information', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="warranty"><?php _e('Warranties', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="warranty" name="warranty" multiple class="rsl-multiselect">
                            <?php 
                            $selected_warranty = explode(',', $license_data['warranty'] ?? '');
                            foreach ($license_handler->get_warranty_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_warranty), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select warranties provided with this license.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="disclaimer"><?php _e('Disclaimers', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <select id="disclaimer" name="disclaimer" multiple class="rsl-multiselect">
                            <?php 
                            $selected_disclaimer = explode(',', $license_data['disclaimer'] ?? '');
                            foreach ($license_handler->get_disclaimer_options() as $value => $label) :
                            ?>
                                <option value="<?php echo esc_attr($value); ?>" 
                                        <?php selected(in_array($value, $selected_disclaimer), true); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Select disclaimers for this license.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Additional Information', 'rsl-licensing'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="schema_url"><?php _e('Schema.org URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="schema_url" name="schema_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['schema_url'] ?? ''); ?>">
                        <p class="description">
                            <?php _e('URL to Schema.org CreativeWork metadata for this content.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="copyright_holder"><?php _e('Copyright Holder', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="copyright_holder" name="copyright_holder" class="regular-text"
                               value="<?php echo esc_attr($license_data['copyright_holder'] ?? ''); ?>">
                        
                        <select name="copyright_type" class="regular-text">
                            <option value=""><?php _e('Select Type', 'rsl-licensing'); ?></option>
                            <option value="person" <?php selected($license_data['copyright_type'] ?? '', 'person'); ?>>
                                <?php _e('Person', 'rsl-licensing'); ?>
                            </option>
                            <option value="organization" <?php selected($license_data['copyright_type'] ?? '', 'organization'); ?>>
                                <?php _e('Organization', 'rsl-licensing'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="contact_email"><?php _e('Contact Email', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="contact_email" name="contact_email" class="regular-text"
                               value="<?php echo esc_attr($license_data['contact_email'] ?? ''); ?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="contact_url"><?php _e('Contact URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="contact_url" name="contact_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['contact_url'] ?? ''); ?>">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="terms_url"><?php _e('Terms URL', 'rsl-licensing'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="terms_url" name="terms_url" class="regular-text"
                               value="<?php echo esc_attr($license_data['terms_url'] ?? ''); ?>">
                        <p class="description">
                            <?php _e('URL to additional legal information about the license.', 'rsl-licensing'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Status', 'rsl-licensing'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="active" value="1" 
                                   <?php checked($license_data['active'] ?? 1, 1); ?>>
                            <?php _e('License is active', 'rsl-licensing'); ?>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button($is_edit ? __('Update License', 'rsl-licensing') : __('Create License', 'rsl-licensing')); ?>
    </form>
</div>
</file>

<file path="includes/class-rsl-server.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_Server {
    
    private $license_handler;
    private $payment_registry;
    private $session_manager;
    
    public function __construct() {
        $this->license_handler = new RSL_License();
        $this->payment_registry = RSL_Payment_Registry::get_instance();
        $this->session_manager = RSL_Session_Manager::get_instance();
        
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_license_requests'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Add authentication handling
        add_action('wp', array($this, 'handle_license_authentication'));
        
        // REST API endpoints for license server functionality
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Hook into WooCommerce payment completion
        add_action('woocommerce_order_status_completed', array($this, 'handle_wc_payment_completed'));
        add_action('woocommerce_payment_complete', array($this, 'handle_wc_payment_completed'));
    }
    
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^rsl-license/([0-9]+)/?$',
            'index.php?rsl_license_id=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^rsl-feed/?$',
            'index.php?rsl_feed=1',
            'top'
        );
        
        add_rewrite_rule(
            '^\.well-known/rsl/?$',
            'index.php?rsl_wellknown=1',
            'top'
        );
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'rsl_license_id';
        $vars[] = 'rsl_feed';
        $vars[] = 'rsl_wellknown';
        return $vars;
    }
    
    public function handle_license_requests() {
        global $wp_query;
        
        // Handle individual license XML requests
        if (get_query_var('rsl_license_id')) {
            $this->serve_license_xml(get_query_var('rsl_license_id'));
            exit;
        }
        
        // Handle RSL feed requests
        if (get_query_var('rsl_feed')) {
            $this->serve_rsl_feed();
            exit;
        }
        
        // Handle .well-known/rsl discovery
        if (get_query_var('rsl_wellknown')) {
            $this->serve_wellknown_rsl();
            exit;
        }
    }
    
    public function handle_license_authentication() {
        // Check if this request requires RSL license authentication
        if (!$this->requires_license_auth()) {
            return;
        }
        
        // Check for License authorization header
        $auth_header = $this->get_authorization_header();
        
        if (!$auth_header || !$this->is_license_auth($auth_header)) {
            $this->send_license_required_response();
            exit;
        }
        
        // Validate the license token
        $token = $this->extract_license_token($auth_header);
        
        if (!$this->validate_license_token($token)) {
            $this->send_invalid_license_response();
            exit;
        }
        
        // Token is valid, allow request to continue
    }
    
    private function serve_license_xml($license_id) {
        $license_id = intval($license_id);
        $license_data = $this->license_handler->get_license($license_id);
        
        if (!$license_data || !$license_data['active']) {
            status_header(404);
            exit;
        }
        
        header('Content-Type: application/rsl+xml; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        $this->add_cors_headers();
        
        echo $this->license_handler->generate_rsl_xml($license_data);
    }
    
    private function serve_rsl_feed() {
        $rsl_rss = new RSL_RSS();
        $rsl_rss->rsl_feed_template();
    }
    
    private function serve_wellknown_rsl() {
        $site_info = array(
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'rsl_version' => '1.0',
            'plugin_version' => RSL_PLUGIN_VERSION,
            'licenses' => array(),
            'feeds' => array(
                'rsl_feed' => home_url('feed/rsl-licenses/'),
                'rss_feed' => get_feed_link()
            ),
            'endpoints' => array(
                'license_xml' => home_url('rsl-license/{id}/'),
                'wellknown' => home_url('.well-known/rsl/')
            )
        );
        
        // Add license information
        $licenses = $this->license_handler->get_licenses(array('active' => 1));
        foreach ($licenses as $license) {
            $site_info['licenses'][] = array(
                'id' => $license['id'],
                'name' => $license['name'],
                'content_url' => $license['content_url'],
                'payment_type' => $license['payment_type'],
                'xml_url' => home_url('rsl-license/' . $license['id'] . '/')
            );
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: public, max-age=1800');
        $this->add_cors_headers();
        
        echo json_encode($site_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    public function register_rest_routes() {
        register_rest_route('rsl/v1', '/licenses', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_licenses'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('rsl/v1', '/licenses/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_license'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('rsl/v1', '/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_validate_license'),
            'permission_callback' => '__return_true'
        ));
        
        // === RSL Open Licensing Protocol (OLP) endpoints ===
        register_rest_route('rsl-olp/v1', '/token', [
            'methods' => 'POST',
            'callback' => [$this, 'olp_issue_token'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('rsl-olp/v1', '/introspect', [
            'methods' => 'POST',
            'callback' => [$this, 'olp_introspect'],
            'permission_callback' => '__return_true'
        ]);
        
        // Optional future: key delivery for encrypted assets
        register_rest_route('rsl-olp/v1', '/key', [
            'methods' => 'GET',
            'callback' => [$this, 'olp_get_key'],
            'permission_callback' => '__return_true'
        ]);
        
        // === Session Management Endpoints (MCP-inspired) ===
        register_rest_route('rsl-olp/v1', '/session', [
            'methods' => 'POST',
            'callback' => [$this, 'olp_create_session'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('rsl-olp/v1', '/session/(?P<session_id>[a-f0-9\-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'olp_get_session'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    public function rest_get_licenses($request) {
        $licenses = $this->license_handler->get_licenses(array('active' => 1));
        
        $response_licenses = array();
        foreach ($licenses as $license) {
            $response_licenses[] = $this->format_license_for_api($license);
        }
        
        return rest_ensure_response($response_licenses);
    }
    
    public function rest_get_license($request) {
        $license_id = intval($request['id']);
        $license_data = $this->license_handler->get_license($license_id);
        
        if (!$license_data || !$license_data['active']) {
            return new WP_Error('license_not_found', 'License not found', array('status' => 404));
        }
        
        return rest_ensure_response($this->format_license_for_api($license_data));
    }
    
    public function rest_validate_license($request) {
        $params = $request->get_json_params();
        
        if (!isset($params['content_url'])) {
            return new WP_Error('missing_content_url', 'Content URL is required', array('status' => 400));
        }
        
        $content_url = $params['content_url'];
        $matching_licenses = $this->find_matching_licenses($content_url);
        
        if (empty($matching_licenses)) {
            return new WP_Error('no_license', 'No license found for this content', array('status' => 404));
        }
        
        return rest_ensure_response(array(
            'valid' => true,
            'licenses' => array_map(array($this, 'format_license_for_api'), $matching_licenses)
        ));
    }
    
    // ===== RSL Open Licensing Protocol (OLP) Endpoints =====
    
    public function olp_issue_token(\WP_REST_Request $req) {
        $license_id = intval($req->get_param('license_id'));
        $client     = sanitize_text_field($req->get_param('client'));
        $create_checkout = filter_var($req->get_param('create_checkout'), FILTER_VALIDATE_BOOLEAN);
        $order_key  = sanitize_text_field($req->get_param('wc_order_key')); // Woo order key flow
        $sub_id     = intval($req->get_param('wc_subscription_id'));        // optional for subs

        $license = $this->license_handler->get_license($license_id);
        if (!$license || !$license['active']) {
            return new \WP_Error('license_not_found', 'License not found', ['status' => 404]);
        }

        // If license points to an external server, refuse and forward
        if (!empty($license['server_url'])) {
            $srv = $license['server_url'];
            $here = parse_url(home_url(), PHP_URL_HOST);
            $there = parse_url($srv, PHP_URL_HOST);
            if ($there && $there !== $here) {
                return new \WP_Error('external_server', 'Managed by external server', [
                    'status' => 409,
                    'server_url' => $srv
                ]);
            }
        }

        $ptype = $license['payment_type'] ?: 'free';

        // Free license (amount = 0 or free/attribution type) → mint immediately
        if ($this->is_free_license($license)) {
            $out = $this->mint_token_for_license($license, $client);
            $this->add_cors_headers();
            return rest_ensure_response($out);
        }

        // Paid license (amount > 0) → require WooCommerce
        if (!$this->is_wc_active()) {
            return new \WP_Error('payment_not_available', 'Paid licensing (amount > 0) requires WooCommerce', ['status' => 501]);
        }

        // Purchase (one-time) — simplest happy path
        if ($ptype === 'purchase') {
            $product_id = $this->ensure_wc_product_for_license($license);
            if (is_wp_error($product_id)) return $product_id;

            if ($create_checkout) {
                // Send back a checkout URL with the product pre-added
                $url = wc_get_checkout_url();
                // Add-to-cart param keeps it simple; cart must be empty ideally
                $url = add_query_arg(['add-to-cart' => $product_id], $url);
                $this->add_cors_headers();
                return rest_ensure_response(['checkout_url' => esc_url_raw($url)]);
            }

            if (!$order_key) {
                return new \WP_Error('missing_order', 'Provide wc_order_key or set create_checkout=true', ['status' => 400]);
            }

            $order_id = wc_get_order_id_by_order_key($order_key);
            if (!$order_id) return new \WP_Error('order_not_found', 'Order not found', ['status' => 404]);

            $order = wc_get_order($order_id);
            if (!$order || !$order->is_paid()) {
                return new \WP_Error('payment_required', 'Order not paid', ['status' => 402]);
            }

            // Verify the product is in the order
            $ok = false;
            foreach ($order->get_items() as $item) {
                if ((int) $item->get_product_id() === (int) $product_id) { $ok = true; break; }
            }
            if (!$ok) return new \WP_Error('product_mismatch', 'Order does not contain the license product', ['status' => 403]);

            $out = $this->mint_token_for_license($license, $client ?: ('order:'.$order_id));
            $this->add_cors_headers();
            return rest_ensure_response($out);
        }

        // Subscription (if Woo Subscriptions is present)
        if ($ptype === 'subscription') {
            if (!$this->is_wcs_active()) {
                return new \WP_Error('subscriptions_unavailable', 'WooCommerce Subscriptions not active', ['status' => 501]);
            }
            $product_id = $this->ensure_wc_product_for_license($license);
            if (is_wp_error($product_id)) return $product_id;

            if ($create_checkout) {
                $url = wc_get_checkout_url();
                $url = add_query_arg(['add-to-cart' => $product_id], $url);
                $this->add_cors_headers();
                return rest_ensure_response(['checkout_url' => esc_url_raw($url)]);
            }

            if (!$sub_id) {
                return new \WP_Error('missing_subscription', 'Provide wc_subscription_id or set create_checkout=true', ['status' => 400]);
            }

            // Minimal subscription check (pseudo; refine as needed)
            $subscription = wcs_get_subscription($sub_id);
            if (!$subscription || !$subscription->has_product($product_id)) {
                return new \WP_Error('subscription_mismatch', 'Subscription does not cover this license', ['status' => 403]);
            }
            if (!$subscription->has_status('active')) {
                return new \WP_Error('subscription_inactive', 'Subscription is not active', ['status' => 402]);
            }

            $out = $this->mint_token_for_license($license, $client ?: ('subscription:'.$sub_id));
            $this->add_cors_headers();
            return rest_ensure_response($out);
        }

        // Other paid models not implemented locally → advise external server
        return new \WP_Error('not_implemented', 'Use an external license server for this payment type', ['status' => 501]);
    }

    public function olp_introspect(\WP_REST_Request $req) {
        $token = $req->get_param('token');
        if (!$token) return new \WP_Error('bad_request', 'Missing token', ['status' => 400]);
        $payload = $this->jwt_decode_token($token);
        if (is_wp_error($payload)) return new \WP_Error('invalid_token', $payload->get_error_message(), ['status' => 401]);
        $now = time();
        if (!empty($payload['exp']) && $now > intval($payload['exp'])) {
            return new \WP_Error('expired', 'Token expired', ['status' => 401]);
        }
        $this->add_cors_headers();
        return rest_ensure_response(['active' => true, 'payload' => $payload]);
    }

    public function olp_get_key(\WP_REST_Request $req) {
        // Optional; return 501 for now
        return new \WP_Error('not_implemented', 'Key delivery not implemented', ['status' => 501]);
    }
    
    private function format_license_for_api($license_data) {
        return array(
            'id' => $license_data['id'],
            'name' => $license_data['name'],
            'description' => $license_data['description'],
            'content_url' => $license_data['content_url'],
            'server_url' => $license_data['server_url'],
            'encrypted' => (bool) $license_data['encrypted'],
            'payment_type' => $license_data['payment_type'],
            'amount' => floatval($license_data['amount']),
            'currency' => $license_data['currency'],
            'permits' => array(
                'usage' => $license_data['permits_usage'],
                'user' => $license_data['permits_user'],
                'geo' => $license_data['permits_geo']
            ),
            'prohibits' => array(
                'usage' => $license_data['prohibits_usage'],
                'user' => $license_data['prohibits_user'],
                'geo' => $license_data['prohibits_geo']
            ),
            'xml_url' => home_url('rsl-license/' . $license_data['id'] . '/'),
            'updated_at' => $license_data['updated_at']
        );
    }
    
    private function find_matching_licenses($content_url) {
        $licenses = $this->license_handler->get_licenses(array('active' => 1));
        $matching = array();
        
        foreach ($licenses as $license) {
            if ($this->url_matches_pattern($content_url, $license['content_url'])) {
                $matching[] = $license;
            }
        }
        
        return $matching;
    }
    
    private function url_matches_pattern($url, $pattern) {
        // If pattern starts with '/', match against the URL path+query; otherwise match against the full URL.
        $haystack = $url;
        if (strlen($pattern) > 0 && $pattern[0] === '/') {
            $u = wp_parse_url($url);
            $path = isset($u['path']) ? $u['path'] : '/';
            $query = isset($u['query']) ? '?' . $u['query'] : '';
            $haystack = $path . $query;
        }

        // Build regex: escape everything, then re-enable '*' -> '.*' and '$' -> '$'
        $quoted = preg_quote($pattern, '#');
        $quoted = str_replace('\*', '.*', $quoted);
        $quoted = str_replace('\$', '$', $quoted);
        $regex = '#^' . $quoted . '#';

        return (bool) preg_match($regex, $haystack);
    }
    
    private function is_crawler_request() {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        if ($ua === '') {
            return false;
        }
        // Lightweight heuristic; editable via filter
        $needles = apply_filters('rsl_crawler_ua_needles', array(
            'bot','crawler','spider','fetch','httpclient','wget','curl',
            'libwww','python-requests','java','apache-httpclient',
            'gpt','ai','anthropic','scrape','indexer','bingpreview'
        ));
        foreach ($needles as $n) {
            if (strpos($ua, $n) !== false) {
                return true;
            }
        }
        return false;
    }

    private function requires_license_auth() {
        // Never for admins
        if (is_admin() || current_user_can('manage_options')) {
            return false;
        }

        // Skip core/asset paths
        $request_uri = esc_url_raw($_SERVER['REQUEST_URI']);
        $wp_core_paths = array('/wp-admin/','/wp-login.php','/wp-cron.php','/xmlrpc.php','/wp-json/','/wp-content/','/wp-includes/');
        foreach ($wp_core_paths as $core_path) {
            if (strpos($request_uri, $core_path) !== false) {
                return false;
            }
        }

        // Only challenge probable crawlers
        if (!$this->is_crawler_request()) {
            return false;
        }

        // Require auth if a license with server_url matches the current request
        $current_url = home_url($request_uri);
        $licenses = $this->license_handler->get_licenses(array('active' => 1));
        foreach ($licenses as $license) {
            if (!empty($license['server_url']) && $this->url_matches_pattern($current_url, $license['content_url'])) {
                return true;
            }
        }
        return false;
    }
    
    private function get_authorization_header() {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
        }
        
        return null;
    }
    
    private function is_license_auth($auth_header) {
        return strpos($auth_header, 'License ') === 0;
    }
    
    private function extract_license_token($auth_header) {
        return trim(substr($auth_header, 8)); // Remove "License " prefix
    }
    
    // ===== WooCommerce Integration Helpers =====
    
    private function is_wc_active() {
        return class_exists('WooCommerce');
    }
    
    private function is_wcs_active() {
        return class_exists('WC_Subscriptions') || function_exists('wcs_get_subscriptions');
    }
    
    private function requires_payment_processing($license) {
        $amount = floatval($license['amount'] ?? 0);
        return $amount > 0;
    }
    
    private function is_free_license($license) {
        $amount = floatval($license['amount'] ?? 0);
        $type = $license['payment_type'] ?? 'free';
        // Free if amount is 0 OR explicitly free/attribution type
        return $amount === 0.0 || in_array($type, ['free', 'attribution'], true);
    }
    
    // ===== JWT Secret Management =====
    
    private function get_jwt_secret() {
        if (defined('RSL_JWT_SECRET') && RSL_JWT_SECRET) {
            return RSL_JWT_SECRET;
        }
        $secret = get_option('rsl_jwt_secret');
        if (!$secret) { 
            $secret = wp_generate_password(64, true, true); 
            add_option('rsl_jwt_secret', $secret); 
        }
        return $secret;
    }
    
    private function get_jwt_ttl() {
        return apply_filters('rsl_token_ttl', 3600); // seconds
    }

    // ===== JWT Encode/Decode (Firebase library preferred, fallback included) =====
    
    private function jwt_encode_payload(array $payload) {
        if (class_exists('\Firebase\JWT\JWT')) {
            return \Firebase\JWT\JWT::encode($payload, $this->get_jwt_secret(), 'HS256');
        }
        // Fallback HS256
        $h = ['alg'=>'HS256','typ'=>'JWT'];
        $b64 = function($d) { return rtrim(strtr(base64_encode(is_string($d) ? $d : wp_json_encode($d)), '+/', '-_'), '='); };
        $head = $b64($h); $body = $b64($payload);
        $sig = hash_hmac('sha256', $head.'.'.$body, $this->get_jwt_secret(), true);
        return $head.'.'.$body.'.'.$b64($sig);
    }
    
    private function jwt_decode_token($jwt) {
        if (class_exists('\Firebase\JWT\JWT') && class_exists('\Firebase\JWT\Key')) {
            try {
                $obj = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key($this->get_jwt_secret(), 'HS256'));
                return json_decode(json_encode($obj), true);
            } catch (\Throwable $e) {
                return new \WP_Error('invalid_token', $e->getMessage());
            }
        }
        // Fallback HS256
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return new \WP_Error('invalid_token', 'Malformed token');
        [$h,$p,$s] = $parts;
        $b64d = function($x) { return base64_decode(strtr($x, '-_', '+/')); };
        $expected = hash_hmac('sha256', $h.'.'.$p, $this->get_jwt_secret(), true);
        if (!hash_equals($expected, $b64d($s))) return new \WP_Error('invalid_token', 'Signature mismatch');
        $payload = json_decode($b64d($p), true);
        if (!is_array($payload)) return new \WP_Error('invalid_token', 'Bad payload');
        return $payload;
    }

    private function validate_license_token($token) {
        $payload = $this->jwt_decode_token($token);
        if (is_wp_error($payload)) return false;
        $now = time();
        if (!empty($payload['nbf']) && $now < intval($payload['nbf'])) return false;
        if (!empty($payload['exp']) && $now > intval($payload['exp'])) return false;

        // Audience should be this host
        $aud = isset($payload['aud']) ? $payload['aud'] : '';
        $host = parse_url(home_url(), PHP_URL_HOST);
        if ($aud && $aud !== $host) return false;

        // Optional: ensure this URL is within the licensed pattern
        $pattern = isset($payload['pattern']) ? $payload['pattern'] : '';
        $request_uri = esc_url_raw($_SERVER['REQUEST_URI']);
        if ($pattern && !$this->url_matches_pattern(home_url($request_uri), $pattern)) {
            return false;
        }
        return true;
    }
    
    // ===== Token Minting =====
    
    private function mint_token_for_license(array $license, $client = 'anonymous') {
        $now = time();
        $ttl = $this->get_jwt_ttl();
        $payload = [
            'iss'     => home_url(),
            'aud'     => parse_url(home_url(), PHP_URL_HOST),
            'sub'     => $client ?: 'anonymous',
            'iat'     => $now,
            'nbf'     => $now,
            'exp'     => $now + $ttl,
            'lic'     => intval($license['id']),
            'scope'   => $license['permits_usage'] ?: 'all',
            'pattern' => $license['content_url'],
        ];
        $token = $this->jwt_encode_payload($payload);
        return [
            'token'       => $token,
            'expires_at'  => gmdate('c', $payload['exp']),
            'license_url' => home_url('rsl-license/' . $license['id'] . '/'),
        ];
    }
    
    // ===== WooCommerce Product Creation =====
    
    private function ensure_wc_product_for_license(array $license) {
        if (!$this->is_wc_active()) return new \WP_Error('wc_inactive', 'WooCommerce is not active');

        $license_id = intval($license['id']);
        // Reuse product by meta
        $q = new \WP_Query([
            'post_type'  => 'product',
            'meta_key'   => '_rsl_license_id',
            'meta_value' => $license_id,
            'fields'     => 'ids',
            'post_status'=> 'publish',
            'posts_per_page' => 1
        ]);
        if ($q->have_posts()) {
            return intval($q->posts[0]);
        }

        // Create simple virtual/hidden product
        $product = new \WC_Product_Simple();
        $product->set_name('RSL License #' . $license_id . ' — ' . ($license['name'] ?? ''));
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_sold_individually(true);

        // Price/currency
        $amount = floatval($license['amount'] ?: 0);
        $store_curr = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD';
        if ($amount > 0 && strtoupper($license['currency'] ?: $store_curr) !== $store_curr) {
            return new \WP_Error('currency_mismatch', 'Store currency does not match license currency');
        }
        if ($amount > 0) {
            $product->set_regular_price($amount);
            $product->set_price($amount);
        } else {
            // Use 0; your checkout still needs a billing method; alternatively block zero-price paid types.
            $product->set_regular_price(0);
            $product->set_price(0);
        }

        $product_id = $product->save();
        if (!$product_id) return new \WP_Error('product_create_failed', 'Could not create product');
        update_post_meta($product_id, '_rsl_license_id', $license_id);
        return $product_id;
    }
    
    private function send_license_required_response() {
        status_header(401);

        $authorization_uri = home_url('.well-known/rsl/');
        $current = home_url(esc_url_raw($_SERVER['REQUEST_URI']));
        $licenses = $this->license_handler->get_licenses(['active' => 1]);

        foreach ($licenses as $lic) {
            if ($this->url_matches_pattern($current, $lic['content_url'])) {
                // Prefer external server if set and not this host
                if (!empty($lic['server_url'])) {
                    $srv = $lic['server_url'];
                    $here = parse_url(home_url(), PHP_URL_HOST);
                    $there = parse_url($srv, PHP_URL_HOST);
                    if ($there && $there !== $here) {
                        $authorization_uri = $srv;
                    } else {
                        // Built-in server token endpoint
                        $authorization_uri = add_query_arg('license_id', $lic['id'], home_url('/wp-json/rsl-olp/v1/token'));
                    }
                } else {
                    // Built-in by default
                    $authorization_uri = add_query_arg('license_id', $lic['id'], home_url('/wp-json/rsl-olp/v1/token'));
                }
                break;
            }
        }

        header('WWW-Authenticate: License error="invalid_request", error_description="Access to this resource requires a valid license", authorization_uri="' . esc_url_raw($authorization_uri) . '"');
        header('Content-Type: text/plain');
        echo "License required. Obtain a token at $authorization_uri";
    }
    
    private function send_invalid_license_response() {
        status_header(401);
        header('WWW-Authenticate: License error="invalid_license", ' .
               'error_description="The provided license token is invalid or expired", ' .
               'authorization_uri="' . home_url('.well-known/rsl/') . '"');
        header('Content-Type: text/plain');
        
        echo "Invalid license token. Please obtain a valid license at " . home_url('.well-known/rsl/');
    }
    
    private function add_cors_headers() {
        // Restrict CORS to trusted origins for security
        $allowed_origins = apply_filters('rsl_cors_allowed_origins', array(
            home_url(), // Allow the site's own origin
            'https://rslcollective.org', // RSL Collective
            // Add other trusted origins as needed
        ));
        
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $origin = esc_url_raw($_SERVER['HTTP_ORIGIN']);
            if (in_array($origin, $allowed_origins, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
            }
        } else {
            // Allow same-origin requests
            header('Access-Control-Allow-Origin: ' . home_url());
        }
        
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
    
    public function get_server_info() {
        return array(
            'server_name' => get_bloginfo('name'),
            'server_url' => home_url(),
            'rsl_version' => '1.0',
            'plugin_version' => RSL_PLUGIN_VERSION,
            'endpoints' => array(
                'licenses' => home_url('wp-json/rsl/v1/licenses'),
                'validate' => home_url('wp-json/rsl/v1/validate'),
                'wellknown' => home_url('.well-known/rsl/')
            ),
            'features' => array(
                'license_authentication' => true,
                'content_encryption' => false, // Not implemented in this version
                'payment_processing' => true,   // Now available via processors
                'session_management' => true    // MCP-inspired sessions
            )
        );
    }
    
    // === Session Management Methods ===
    
    /**
     * Create payment session (MCP-inspired)
     */
    public function olp_create_session(\WP_REST_Request $req) {
        $license_id = intval($req->get_param('license_id'));
        $client = sanitize_text_field($req->get_param('client')) ?: 'anonymous';
        
        $license = $this->license_handler->get_license($license_id);
        if (!$license || !$license['active']) {
            return new \WP_Error('license_not_found', 'License not found', ['status' => 404]);
        }
        
        // Create session
        $session_data = $this->session_manager->create_session($license, $client, $req->get_params());
        
        // If free license, no payment needed
        $amount = floatval($license['amount']);
        if ($amount === 0.0) {
            $this->session_manager->update_session_status($session_data['session_id'], 'completed');
            
            // Generate free token immediately
            $token_data = $this->mint_token_for_license($license, $client);
            return rest_ensure_response(array_merge($session_data, [
                'token' => $token_data['token'],
                'expires_at' => $token_data['expires_at']
            ]));
        }
        
        // Get payment processor
        $processor = $this->payment_registry->get_processor_for_license($license);
        if (!$processor) {
            return new \WP_Error('no_processor', 'No payment processor available for this license', ['status' => 501]);
        }
        
        // Create checkout session
        $checkout_result = $processor->create_checkout_session($license, $client, $session_data['session_id']);
        if (is_wp_error($checkout_result)) {
            return $checkout_result;
        }
        
        // Update session with checkout URL
        $this->session_manager->set_checkout_url(
            $session_data['session_id'],
            $checkout_result['checkout_url'],
            $processor->get_id()
        );
        
        return rest_ensure_response(array_merge($session_data, [
            'checkout_url' => $checkout_result['checkout_url'],
            'processor' => $processor->get_name()
        ]));
    }
    
    /**
     * Get session status (MCP-inspired polling)
     */
    public function olp_get_session(\WP_REST_Request $req) {
        $session_id = $req->get_param('session_id');
        
        if (!$session_id) {
            return new \WP_Error('missing_session_id', 'Session ID required', ['status' => 400]);
        }
        
        $status = $this->session_manager->get_session_status($session_id);
        
        if (is_wp_error($status)) {
            return $status;
        }
        
        return rest_ensure_response($status);
    }
    
    /**
     * Handle WooCommerce payment completion
     */
    public function handle_wc_payment_completed($order_id) {
        $order = wc_get_order($order_id);
        if (!$order || !$order->is_paid()) {
            return;
        }
        
        $session_id = $order->get_meta('rsl_session_id');
        $license_id = $order->get_meta('rsl_license_id');
        
        if (!$session_id || !$license_id) {
            return;
        }
        
        $session = $this->session_manager->get_session($session_id);
        if (!$session) {
            return;
        }
        
        $license = $this->license_handler->get_license($license_id);
        if (!$license) {
            return;
        }
        
        // Get WooCommerce processor to generate proof
        $processor = $this->payment_registry->get_processor('woocommerce');
        if (!$processor) {
            return;
        }
        
        $proof = $processor->generate_payment_proof($license, $session_id, [
            'order_id' => $order_id
        ]);
        
        if (!is_wp_error($proof)) {
            $this->session_manager->store_payment_proof($session_id, $proof);
        }
    }
}
</file>

<file path="admin/js/admin.js">
jQuery(document).ready(function($) {
    
    // Global variables
    var rslAdmin = {
        init: function() {
            this.bindEvents();
            this.initializeFields();
        },
        
        bindEvents: function() {
            // Payment type change
            $(document).on('change', '#payment_type', this.togglePaymentFields);
            
            // Server option change
            $(document).on('change', 'input[name="server_option"]', this.toggleServerFields);
            
            // Form submission
            $(document).on('submit', '#rsl-license-form', this.handleFormSubmission);
            
            // Generate XML button
            $(document).on('click', '.rsl-generate-xml', this.generateXML);
            
            // Delete license button
            $(document).on('click', '.rsl-delete-license', this.deleteLicense);
            
            // Modal events
            $(document).on('click', '.rsl-modal-close', this.closeModal);
            $(document).on('click', '.rsl-modal', this.handleModalBackdropClick);
            
            // Copy XML button
            $(document).on('click', '#rsl-copy-xml', this.copyXMLToClipboard);
            
            // Download XML button
            $(document).on('click', '#rsl-download-xml', this.downloadXML);
            
            // Multiselect handling
            $(document).on('change', '.rsl-multiselect', this.updateMultiselectValues);
        },
        
        initializeFields: function() {
            this.togglePaymentFields();
            this.toggleServerFields();
            this.styleMultiselects();
        },
        
        togglePaymentFields: function() {
            var paymentType = $('#payment_type').val();
            var typesWithAmounts = ['purchase', 'subscription', 'training', 'crawl', 'inference', 'royalty'];
            
            if (typesWithAmounts.indexOf(paymentType) !== -1) {
                $('#payment_amount_row').show();
            } else {
                $('#payment_amount_row').hide();
            }
        },
        
        toggleServerFields: function() {
            var serverOption = $('input[name="server_option"]:checked').val();
            
            if (serverOption === 'external') {
                $('#external_server_url_field').show();
            } else {
                $('#external_server_url_field').hide();
                $('#server_url').val(''); // Clear external URL when using built-in
            }
        },
        
        validateWooCommerceRequirement: function() {
            var amount = parseFloat($('#amount').val()) || 0;
            var paymentType = $('#payment_type').val();
            var hasWooCommerce = typeof rsl_ajax.woocommerce_active !== 'undefined' ? rsl_ajax.woocommerce_active : false;
            var hasPaymentCapability = typeof rsl_ajax.has_payment_capability !== 'undefined' ? rsl_ajax.has_payment_capability : false;
            
            // Allow any payment type with $0 amount (including attribution)
            if (amount === 0) {
                return { valid: true };
            }
            
            // Block any amount > 0 without payment capability
            if (amount > 0 && !hasPaymentCapability) {
                var message = 'Payment processing is required for paid licensing (amount > $0). ';
                
                if (!hasWooCommerce) {
                    if (paymentType === 'attribution') {
                        message += 'For paid attribution licenses, please install and activate WooCommerce, then set up your preferred payment gateway (Stripe, PayPal, etc.).';
                    } else {
                        message += 'Please install and activate WooCommerce to enable payment processing.';
                    }
                } else {
                    message += 'WooCommerce is installed but may not support this payment type.';
                }
                
                return {
                    valid: false,
                    message: message
                };
            }
            
            return { valid: true };
        },
        
        styleMultiselects: function() {
            $('.rsl-multiselect').css({
                'width': '400px',
                'height': '100px'
            });
        },
        
        updateMultiselectValues: function() {
            $('.rsl-multiselect').each(function() {
                var values = $(this).val();
                if (values && values.length > 0) {
                    // Store comma-separated values in a hidden field
                    var hiddenField = $('input[name="' + $(this).attr('name') + '_hidden"]');
                    if (hiddenField.length === 0) {
                        hiddenField = $('<input type="hidden" name="' + $(this).attr('name') + '_hidden">');
                        $(this).after(hiddenField);
                    }
                    hiddenField.val(values.join(','));
                }
            });
        },
        
        handleFormSubmission: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            var originalText = $submitButton.val();
            
            // Validate WooCommerce requirement before submission
            var wooValidation = rslAdmin.validateWooCommerceRequirement();
            if (!wooValidation.valid) {
                rslAdmin.showMessage(wooValidation.message, 'error');
                return;
            }
            
            // Update submit button
            $submitButton.val(rsl_ajax.strings.saving).prop('disabled', true);
            
            // Prepare form data
            var formData = $form.serialize();
            
            // Handle server option - set server_url based on radio selection
            var serverOption = $('input[name="server_option"]:checked').val();
            if (serverOption === 'builtin') {
                formData += '&server_url=' + encodeURIComponent(rsl_ajax.rest_url);
            }
            // External option uses the URL field value (already in formData)
            
            // Handle multiselect fields (convert to comma-separated values)
            $('.rsl-multiselect').each(function() {
                var fieldName = $(this).attr('name');
                var values = $(this).val();
                
                // Remove existing field from formData to prevent duplicates
                var regex = new RegExp('&?' + encodeURIComponent(fieldName) + '=[^&]*', 'g');
                formData = formData.replace(regex, '');
                
                // Add the multiselect field with proper comma-separated values
                if (values && values.length > 0) {
                    formData += '&' + fieldName + '=' + encodeURIComponent(values.join(','));
                } else {
                    formData += '&' + fieldName + '=';
                }
            });
            
            // Add action and nonce
            formData += '&action=rsl_save_license&nonce=' + rsl_ajax.nonce;
            
            
            $.ajax({
                url: rsl_ajax.url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        rslAdmin.showMessage(response.data.message, 'success');
                        
                        // Redirect after short delay
                        setTimeout(function() {
                            window.location.href = rsl_ajax.redirect_url;
                        }, 1500);
                    } else {
                        rslAdmin.showMessage(response.data.message, 'error');
                        $submitButton.val(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    rslAdmin.showMessage(rsl_ajax.strings.error_occurred, 'error');
                    $submitButton.val(originalText).prop('disabled', false);
                }
            });
        },
        
        generateXML: function() {
            var licenseId = $(this).data('license-id');
            
            $.ajax({
                url: rsl_ajax.url,
                type: 'POST',
                data: {
                    action: 'rsl_generate_xml',
                    license_id: licenseId,
                    nonce: rsl_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#rsl-xml-content').val(response.data.xml);
                        $('#rsl-xml-modal').show();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(rsl_ajax.strings.error_generating_xml);
                }
            });
        },
        
        deleteLicense: function() {
            var licenseId = $(this).data('license-id');
            var licenseName = $(this).data('license-name');
            
            if (!confirm(rsl_ajax.strings.delete_confirm.replace('%s', licenseName))) {
                return;
            }
            
            $.ajax({
                url: rsl_ajax.url,
                type: 'POST',
                data: {
                    action: 'rsl_delete_license',
                    license_id: licenseId,
                    nonce: rsl_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert(rsl_ajax.strings.error_deleting);
                }
            });
        },
        
        closeModal: function() {
            $('.rsl-modal').hide();
        },
        
        handleModalBackdropClick: function(e) {
            if ($(e.target).hasClass('rsl-modal')) {
                $('.rsl-modal').hide();
            }
        },
        
        copyXMLToClipboard: function() {
            var textarea = document.getElementById('rsl-xml-content');
            if (textarea) {
                textarea.select();
                textarea.setSelectionRange(0, 99999); // For mobile devices
                
                try {
                    document.execCommand('copy');
                    rslAdmin.showTempMessage(rsl_ajax.strings.xml_copied);
                } catch (err) {
                    console.error('Failed to copy: ', err);
                    alert(rsl_ajax.strings.copy_failed);
                }
            }
        },
        
        downloadXML: function() {
            var content = $('#rsl-xml-content').val();
            var blob = new Blob([content], { type: 'application/xml' });
            var url = window.URL.createObjectURL(blob);
            
            var a = document.createElement('a');
            a.href = url;
            a.download = 'rsl-license.xml';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },
        
        showMessage: function(message, type) {
            var className = 'notice-' + (type === 'success' ? 'success' : 'error');
            var $message = $('#rsl-message');
            
            $message
                .removeClass('notice-success notice-error rsl-hidden')
                .addClass('notice ' + className)
                .html('<p>' + message + '</p>')
                .css('display', 'block');
            
            $('html, body').animate({scrollTop: 0}, 500);
        },
        
        showTempMessage: function(message) {
            var $temp = $('<div class="notice notice-success" style="position: fixed; top: 32px; right: 20px; z-index: 999999; padding: 10px 15px;"><p>' + message + '</p></div>');
            $('body').append($temp);
            
            setTimeout(function() {
                $temp.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 2000);
        }
    };
    
    // Initialize admin functionality
    rslAdmin.init();
    
    // Handle escape key for modals
    $(document).keyup(function(e) {
        if (e.keyCode === 27) { // Escape key
            $('.rsl-modal').hide();
        }
    });
    
    // Validation helpers
    window.rslValidation = {
        validateURL: function (value) {
            if (!value) return false;
            value = value.trim();

            // Absolute http(s) URL
            var abs = /^(https?:\/\/)((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3}))(:\d+)?(\/[-a-z\d%_.~+*]*)*(\?[;&a-z\d%_.~+=-]*)?(#[-a-z\d_]*)?$/i;
            if (abs.test(value)) return true;

            // Server-relative RFC 9309-style pattern: starts with '/', allows *, $
            var rel = /^\/[A-Za-z0-9._~!'()*+,;=:@\/\-%]*\*?\$?$/;
            return rel.test(value);
        },
        
        validateEmail: function(email) {
            var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return pattern.test(email);
        }
    };
    
    // Add real-time validation for payment fields
    $('#amount, #payment_type').on('change blur', function() {
        var validation = rslAdmin.validateWooCommerceRequirement();
        var $amountField = $('#amount');
        var $paymentField = $('#payment_type');
        
        if (!validation.valid) {
            // Highlight both amount and payment type fields
            $amountField.css('border-color', '#dc3545');
            $paymentField.css('border-color', '#dc3545');
            
            // Show error message under amount field
            $amountField.next('.validation-error').remove();
            $amountField.after('<div class="validation-error" style="color: #dc3545; font-size: 12px; margin-top: 5px; padding: 8px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">' + validation.message + '</div>');
            
            // Disable submit button
            $('input[type="submit"]').prop('disabled', true).css('opacity', '0.6');
        } else {
            // Clear validation errors
            $amountField.css('border-color', '');
            $paymentField.css('border-color', '');
            $amountField.next('.validation-error').remove();
            
            // Re-enable submit button
            $('input[type="submit"]').prop('disabled', false).css('opacity', '1');
        }
    });
    
    // Add real-time validation for URLs
    $('#content_url, #server_url, #standard_url, #custom_url, #schema_url, #contact_url, #terms_url').on('blur', function() {
        var $field = $(this);
        var value = $field.val();
        
        if (value && !rslValidation.validateURL(value)) {
            $field.css('border-color', '#dc3545');
            if ($field.next('.validation-error').length === 0) {
                $field.after('<span class="validation-error" style="color: #dc3545; font-size: 12px;">' + rsl_ajax.strings.validate_url + '</span>');
            }
        } else {
            $field.css('border-color', '');
            $field.next('.validation-error').remove();
        }
    });
    
    $('#contact_email').on('blur', function() {
        var $field = $(this);
        var value = $field.val();
        
        if (value && !rslValidation.validateEmail(value)) {
            $field.css('border-color', '#dc3545');
            if ($field.next('.validation-error').length === 0) {
                $field.after('<span class="validation-error" style="color: #dc3545; font-size: 12px;">' + rsl_ajax.strings.validate_email + '</span>');
            }
        } else {
            $field.css('border-color', '');
            $field.next('.validation-error').remove();
        }
    });
});
</file>

<file path="includes/class-rsl-admin.php">
<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_Admin {
    
    private $license_handler;
    private $payment_registry;
    
    public function __construct() {
        $this->license_handler = new RSL_License();
        $this->payment_registry = RSL_Payment_Registry::get_instance();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_rsl_save_license', array($this, 'ajax_save_license'));
        add_action('wp_ajax_rsl_delete_license', array($this, 'ajax_delete_license'));
        add_action('wp_ajax_rsl_generate_xml', array($this, 'ajax_generate_xml'));
        
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        
        // Add help tabs to admin pages
        add_action('load-toplevel_page_rsl-licensing', array($this, 'add_help_tabs_dashboard'));
        add_action('load-rsl-licensing_page_rsl-licenses', array($this, 'add_help_tabs_licenses'));
        add_action('load-rsl-licensing_page_rsl-add-license', array($this, 'add_help_tabs_add_license'));
        add_action('load-rsl-licensing_page_rsl-settings', array($this, 'add_help_tabs_dashboard')); // Settings uses same help as dashboard
        
        // Gutenberg support
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('init', array($this, 'register_meta_fields'));
    }
    
    public function add_admin_menu() {
        // Add main menu page with RSL icon - this will be the dashboard
        add_menu_page(
            __('RSL Licensing', 'rsl-licensing'),
            __('RSL Licensing', 'rsl-licensing'),
            'manage_options',
            'rsl-licensing',
            array($this, 'admin_page'),
            $this->get_menu_icon(),
            30 // Position after Settings
        );
        
        // Add submenu pages under RSL Licensing (first submenu will be the same as parent)
        add_submenu_page(
            'rsl-licensing',
            __('Dashboard', 'rsl-licensing'),
            __('Dashboard', 'rsl-licensing'),
            'manage_options',
            'rsl-licensing', // Same as parent to avoid duplication
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'rsl-licensing',
            __('All Licenses', 'rsl-licensing'),
            __('All Licenses', 'rsl-licensing'),
            'manage_options',
            'rsl-licenses',
            array($this, 'licenses_page')
        );
        
        add_submenu_page(
            'rsl-licensing',
            __('Add New License', 'rsl-licensing'),
            __('Add New License', 'rsl-licensing'),
            'manage_options',
            'rsl-add-license',
            array($this, 'add_license_page')
        );
        
        add_submenu_page(
            'rsl-licensing',
            __('Settings', 'rsl-licensing'),
            __('Settings', 'rsl-licensing'),
            'manage_options',
            'rsl-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('rsl_settings', 'rsl_global_license_id', array(
            'sanitize_callback' => 'absint'
        ));
        register_setting('rsl_settings', 'rsl_enable_html_injection', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        register_setting('rsl_settings', 'rsl_enable_http_headers', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        register_setting('rsl_settings', 'rsl_enable_robots_txt', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        register_setting('rsl_settings', 'rsl_enable_rss_feed', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        register_setting('rsl_settings', 'rsl_enable_media_metadata', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        register_setting('rsl_settings', 'rsl_default_namespace', array(
            'sanitize_callback' => 'esc_url_raw'
        ));
    }
    
    public function sanitize_checkbox($value) {
        return intval($value) ? 1 : 0;
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'rsl') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('rsl-admin', RSL_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), RSL_PLUGIN_VERSION, true);
            wp_enqueue_style('rsl-admin', RSL_PLUGIN_URL . 'admin/css/admin.css', array(), RSL_PLUGIN_VERSION);
            
            // Get payment processor info for UI
            $wc_processor = $this->payment_registry->get_processor('woocommerce');
            $has_payment_capability = $this->payment_registry->has_payment_capability();
            
            wp_localize_script('rsl-admin', 'rsl_ajax', array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rsl_nonce'),
                'redirect_url' => admin_url('admin.php?page=rsl-licenses'),
                'rest_url' => rest_url('rsl-olp/v1'),
                'woocommerce_active' => ($wc_processor && $wc_processor->is_available()),
                'woocommerce_subscriptions_active' => ($wc_processor && in_array('subscription', $wc_processor->get_supported_payment_types())),
                'has_payment_capability' => $has_payment_capability,
                'strings' => array(
                    'saving' => __('Saving...', 'rsl-licensing'),
                    'error_occurred' => __('An error occurred while saving the license.', 'rsl-licensing'),
                    'error_generating_xml' => __('Error generating XML', 'rsl-licensing'),
                    'delete_confirm' => __('Are you sure you want to delete the license "%s"? This action cannot be undone.', 'rsl-licensing'),
                    'error_deleting' => __('Error deleting license', 'rsl-licensing'),
                    'xml_copied' => __('XML copied to clipboard!', 'rsl-licensing'),
                    'copy_failed' => __('Failed to copy to clipboard. Please select and copy manually.', 'rsl-licensing'),
                    'validate_url' => __('Please enter a valid URL', 'rsl-licensing'),
                    'validate_email' => __('Please enter a valid email address', 'rsl-licensing')
                )
            ));
        }
    }
    
    public function admin_page() {
        // This is now the main RSL dashboard page
        $licenses = $this->license_handler->get_licenses();
        $global_license_id = get_option('rsl_global_license_id', 0);
        $total_licenses = count($licenses);
        $active_licenses = count(array_filter($licenses, function($license) {
            return $license['active'] == 1;
        }));
        
        include RSL_PLUGIN_PATH . 'admin/templates/admin-dashboard.php';
    }
    
    public function settings_page() {
        $licenses = $this->license_handler->get_licenses();
        $global_license_id = get_option('rsl_global_license_id', 0);
        
        include RSL_PLUGIN_PATH . 'admin/templates/admin-settings.php';
    }
    
    public function licenses_page() {
        $licenses = $this->license_handler->get_licenses();
        
        include RSL_PLUGIN_PATH . 'admin/templates/admin-licenses.php';
    }
    
    public function add_license_page() {
        $license_data = array();
        $license_id = 0;
        
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
            $license_id = intval($_GET['edit']);
            $license_data = $this->license_handler->get_license($license_id);
        }
        
        // Get payment processor information for the UI
        $wc_processor = $this->payment_registry->get_processor('woocommerce');
        $woocommerce_active = ($wc_processor && $wc_processor->is_available());
        $woocommerce_subscriptions_active = ($wc_processor && in_array('subscription', $wc_processor->get_supported_payment_types()));
        $has_payment_capability = $this->payment_registry->has_payment_capability();
        
        include RSL_PLUGIN_PATH . 'admin/templates/admin-add-license.php';
    }
    
    public function ajax_save_license() {
        check_ajax_referer('rsl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'rsl-licensing'));
        }
        
        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['content_url'])) {
            wp_send_json_error(array(
                'message' => __('Name and Content URL are required fields.', 'rsl-licensing')
            ));
            return;
        }
        
        // --- BEGIN: RSL-safe URL/path validation ---
        $content_url_input = isset($_POST['content_url']) ? trim((string) $_POST['content_url']) : '';

        // Allow either:
        // 1) Absolute URL (http/https), OR
        // 2) Server-relative path per RFC 9309 patterns, including * and $ (e.g., "/", "/images/*", "*.pdf", "/api/*$").
        $looks_absolute = preg_match('#^https?://#i', $content_url_input) === 1;
        $looks_server_relative = (strlen($content_url_input) > 0 && $content_url_input[0] === '/');

        if ($looks_absolute) {
            $content_url = esc_url_raw($content_url_input);
            if (empty($content_url)) {
                wp_send_json_error(['message' => __('Invalid Content URL format.', 'rsl-licensing')]);
                return;
            }
        } elseif ($looks_server_relative) {
            // Basic character allowlist per robots-style patterns; allow RFC3986 pchar + '*' and '$'
            if (!preg_match('#^/[A-Za-z0-9._~!\'()*+,;=:@/\-%]*\*?\$?$#', $content_url_input)) {
                wp_send_json_error(['message' => __('Invalid server-relative path/pattern.', 'rsl-licensing')]);
                return;
            }
            $content_url = $content_url_input; // store as provided (e.g., "/images/*")
        } else {
            wp_send_json_error(['message' => __('Content URL must be an absolute URL (http/https) or a server-relative path (starting with "/").', 'rsl-licensing')]);
            return;
        }
        // --- END: RSL-safe URL/path validation ---
        
        $server_url = esc_url_raw($_POST['server_url']);
        if (!empty($server_url) && !filter_var($server_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(array(
                'message' => __('Invalid Server URL format.', 'rsl-licensing')
            ));
            return;
        }
        
        // Validate amount
        $amount = floatval($_POST['amount']);
        if ($amount < 0 || $amount > 999999.99) {
            wp_send_json_error(array(
                'message' => __('Amount must be between 0 and 999,999.99.', 'rsl-licensing')
            ));
            return;
        }
        
        // Validate currency code
        $currency = strtoupper(sanitize_text_field($_POST['currency']));
        if (!empty($currency) && !preg_match('/^[A-Z]{3}$/', $currency)) {
            wp_send_json_error(array(
                'message' => __('Currency must be a valid 3-letter ISO code (e.g., USD, EUR).', 'rsl-licensing')
            ));
            return;
        }
        
        // Validate payment processor availability for paid licenses
        $payment_type = sanitize_text_field($_POST['payment_type']);
        if ($amount > 0) {
            $processor = $this->payment_registry->get_processor_for_license([
                'payment_type' => $payment_type,
                'amount' => $amount
            ]);
            
            if (!$processor) {
                // Generate WooCommerce-first error message
                $wc_processor = $this->payment_registry->get_processor('woocommerce');
                
                if (!$wc_processor) {
                    $message = __('WooCommerce is required for paid licensing (amount > $0). ', 'rsl-licensing');
                    
                    if ($payment_type === 'attribution') {
                        $message .= __('For paid attribution licenses, please install and activate WooCommerce, then set up your preferred payment gateway (Stripe, PayPal, etc.).', 'rsl-licensing');
                    } else {
                        $message .= __('Please install and activate WooCommerce to enable payment processing.', 'rsl-licensing');
                    }
                } else {
                    $message = sprintf(
                        __('The %s payment method is not supported. WooCommerce supports %s payment types. ', 'rsl-licensing'),
                        $payment_type,
                        implode(', ', $wc_processor->get_supported_payment_types())
                    );
                    
                    if ($payment_type === 'attribution') {
                        $message .= __('For attribution licenses, set the amount to $0 or use "purchase" payment type.', 'rsl-licensing');
                    }
                }
                
                wp_send_json_error(array(
                    'message' => $message
                ));
                return;
            }
        }
        
        // Validate email
        $contact_email = sanitize_email($_POST['contact_email']);
        if (!empty($contact_email) && !is_email($contact_email)) {
            wp_send_json_error(array(
                'message' => __('Invalid email address format.', 'rsl-licensing')
            ));
            return;
        }
        
        // Validate payment type
        $allowed_payment_types = array('free', 'purchase', 'subscription', 'training', 'crawl', 'inference', 'attribution', 'royalty');
        $payment_type = sanitize_text_field($_POST['payment_type']);
        if (!in_array($payment_type, $allowed_payment_types)) {
            $payment_type = 'free';
        }
        
        $license_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'content_url' => $content_url,
            'server_url' => $server_url,
            'encrypted' => isset($_POST['encrypted']) ? 1 : 0,
            'lastmod' => current_time('mysql'),
            'permits_usage' => sanitize_text_field($_POST['permits_usage']),
            'permits_user' => sanitize_text_field($_POST['permits_user']),
            'permits_geo' => sanitize_text_field($_POST['permits_geo']),
            'prohibits_usage' => sanitize_text_field($_POST['prohibits_usage']),
            'prohibits_user' => sanitize_text_field($_POST['prohibits_user']),
            'prohibits_geo' => sanitize_text_field($_POST['prohibits_geo']),
            'payment_type' => $payment_type,
            'standard_url' => esc_url_raw($_POST['standard_url']),
            'custom_url' => esc_url_raw($_POST['custom_url']),
            'amount' => $amount,
            'currency' => $currency,
            'warranty' => sanitize_text_field($_POST['warranty']),
            'disclaimer' => sanitize_text_field($_POST['disclaimer']),
            'schema_url' => esc_url_raw($_POST['schema_url']),
            'copyright_holder' => sanitize_text_field($_POST['copyright_holder']),
            'copyright_type' => sanitize_text_field($_POST['copyright_type']),
            'contact_email' => $contact_email,
            'contact_url' => esc_url_raw($_POST['contact_url']),
            'terms_url' => esc_url_raw($_POST['terms_url']),
            'active' => isset($_POST['active']) ? 1 : 0
        );
        
        if (isset($_POST['license_id']) && is_numeric($_POST['license_id']) && $_POST['license_id'] > 0) {
            $result = $this->license_handler->update_license($_POST['license_id'], $license_data);
            $license_id = $_POST['license_id'];
        } else {
            $license_id = $this->license_handler->create_license($license_data);
            $result = $license_id !== false;
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('License saved successfully', 'rsl-licensing'),
                'license_id' => $license_id
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save license', 'rsl-licensing')
            ));
        }
    }
    
    public function ajax_delete_license() {
        check_ajax_referer('rsl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'rsl-licensing'));
        }
        
        $license_id = intval($_POST['license_id']);
        
        if ($this->license_handler->delete_license($license_id)) {
            wp_send_json_success(array(
                'message' => __('License deleted successfully', 'rsl-licensing')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete license', 'rsl-licensing')
            ));
        }
    }
    
    public function ajax_generate_xml() {
        check_ajax_referer('rsl_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'rsl-licensing'));
        }
        
        $license_id = intval($_POST['license_id']);
        $license_data = $this->license_handler->get_license($license_id);
        
        if ($license_data) {
            $xml = $this->license_handler->generate_rsl_xml($license_data);
            wp_send_json_success(array(
                'xml' => $xml
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('License not found', 'rsl-licensing')
            ));
        }
    }
    
    public function add_meta_boxes() {
        // Only add meta boxes for classic editor (non-Gutenberg)
        // Gutenberg uses the native Document Settings Panel instead
        $screen = get_current_screen();
        
        if ($screen && method_exists($screen, 'is_block_editor') && $screen->is_block_editor()) {
            return; // Skip meta boxes for Gutenberg
        }
        
        $post_types = array('post', 'page');
        $post_types = apply_filters('rsl_supported_post_types', $post_types);
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'rsl_license_meta',
                __('RSL License', 'rsl-licensing'),
                array($this, 'meta_box_callback'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    public function meta_box_callback($post) {
        wp_nonce_field('rsl_meta_box', 'rsl_meta_nonce');
        
        $licenses = $this->license_handler->get_licenses();
        $selected_license = get_post_meta($post->ID, '_rsl_license_id', true);
        $override_content_url = get_post_meta($post->ID, '_rsl_override_content_url', true);
        $global_license_id = get_option('rsl_global_license_id', 0);
        
        // Show global license info if configured
        if ($global_license_id > 0 && empty($selected_license)) {
            $global_license = $this->license_handler->get_license($global_license_id);
            if ($global_license) {
                echo '<div style="background: #f0f6fc; border: 1px solid #c3c4c7; padding: 8px; margin-bottom: 12px; border-radius: 4px;">';
                echo '<strong>' . __('Global License Active:', 'rsl-licensing') . '</strong><br>';
                echo '<small>' . esc_html($global_license['name']) . ' (' . esc_html($global_license['payment_type']) . ')</small>';
                echo '</div>';
            }
        }
        
        echo '<p><label for="rsl_license_select">' . __('License Override:', 'rsl-licensing') . '</label></p>';
        echo '<select id="rsl_license_select" name="rsl_license_id" style="width: 100%;">';
        echo '<option value="">' . __('Use Global License', 'rsl-licensing') . '</option>';
        
        foreach ($licenses as $license) {
            $selected = selected($selected_license, $license['id'], false);
            echo '<option value="' . esc_attr($license['id']) . '" ' . $selected . '>';
            echo esc_html($license['name']) . ' (' . esc_html($license['payment_type']) . ')';
            echo '</option>';
        }
        
        echo '</select>';
        echo '<p><small>' . __('Select a specific license to override the global license for this content.', 'rsl-licensing') . '</small></p>';
        
        echo '<p style="margin-top: 15px;"><label for="rsl_override_url">';
        echo __('Override Content URL:', 'rsl-licensing');
        echo '</label></p>';
        echo '<input type="url" id="rsl_override_url" name="rsl_override_content_url" ';
        echo 'value="' . esc_attr($override_content_url) . '" style="width: 100%;" ';
        echo 'placeholder="' . __('Leave empty to use post URL', 'rsl-licensing') . '">';
        
        echo '<p><small>' . __('Override the content URL for this specific post/page. Useful for syndicated content.', 'rsl-licensing') . '</small></p>';
    }
    
    public function save_post_meta($post_id) {
        // Skip if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if this is a REST API request (Gutenberg saves via REST)
        if (defined('REST_REQUEST') && REST_REQUEST) {
            // For Gutenberg, meta is saved automatically via REST API
            // We just need to validate the data
            $this->validate_post_meta($post_id);
            return;
        }
        
        // Classic editor nonce verification
        if (!isset($_POST['rsl_meta_nonce']) || !wp_verify_nonce($_POST['rsl_meta_nonce'], 'rsl_meta_box')) {
            return;
        }
        
        // Process classic editor form data
        $license_id = isset($_POST['rsl_license_id']) ? intval($_POST['rsl_license_id']) : 0;
        $override_url = isset($_POST['rsl_override_content_url']) ? esc_url_raw($_POST['rsl_override_content_url']) : '';
        
        // Validate override URL if provided
        if (!empty($override_url) && !filter_var($override_url, FILTER_VALIDATE_URL)) {
            // Invalid URL, don't save it
            $override_url = '';
        }
        
        if ($license_id > 0) {
            update_post_meta($post_id, '_rsl_license_id', $license_id);
        } else {
            delete_post_meta($post_id, '_rsl_license_id');
        }
        
        if (!empty($override_url)) {
            update_post_meta($post_id, '_rsl_override_content_url', $override_url);
        } else {
            delete_post_meta($post_id, '_rsl_override_content_url');
        }
        
        $this->validate_post_meta($post_id);
    }
    
    private function validate_post_meta($post_id) {
        // Validate license ID exists if set
        $license_id = get_post_meta($post_id, '_rsl_license_id', true);
        if ($license_id > 0) {
            $license = $this->license_handler->get_license($license_id);
            if (!$license || !$license['active']) {
                // Invalid license, remove it
                delete_post_meta($post_id, '_rsl_license_id');
                error_log('RSL: Removed invalid license ID ' . $license_id . ' from post ' . $post_id);
            }
        }
        
        // Validate override URL format
        $override_url = get_post_meta($post_id, '_rsl_override_content_url', true);
        if (!empty($override_url) && !filter_var($override_url, FILTER_VALIDATE_URL)) {
            delete_post_meta($post_id, '_rsl_override_content_url');
            error_log('RSL: Removed invalid override URL from post ' . $post_id);
        }
    }
    
    private function get_menu_icon() {
        // RSL SVG icon as base64 data URI for WordPress admin menu
        $svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20">
  <defs>
    <mask id="text-mask">
      <rect width="100%" height="100%" fill="white"/>
      <text x="10" y="14" font-family="Arial, sans-serif" font-size="10" font-weight="bold"
            fill="black" text-anchor="middle">RSL</text>
    </mask>
  </defs>
  <!-- Shield shape with applied mask -->
  <path d="M0 4 L10 0 L20 4 L18 16 L10 20 L2 16 Z" fill="#E44D26" mask="url(#text-mask)"/>
  <path d="M10 0 L20 4 L18 16 L10 20 Z" fill="#F16529" mask="url(#text-mask)"/>
</svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg_icon);
    }
    
    public function register_meta_fields() {
        $post_types = array('post', 'page');
        $post_types = apply_filters('rsl_supported_post_types', $post_types);
        
        foreach ($post_types as $post_type) {
            register_post_meta($post_type, '_rsl_license_id', array(
                'type' => 'integer',
                'description' => 'RSL License ID for this post',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can('edit_posts');
                }
            ));
            
            register_post_meta($post_type, '_rsl_override_content_url', array(
                'type' => 'string',
                'description' => 'Override content URL for RSL license',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can('edit_posts');
                }
            ));
        }
    }
    
    public function enqueue_block_editor_assets() {
        $screen = get_current_screen();
        
        if (!$screen || !$screen->is_block_editor()) {
            return;
        }
        
        wp_enqueue_script(
            'rsl-gutenberg',
            RSL_PLUGIN_URL . 'admin/js/gutenberg.js',
            array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'),
            RSL_PLUGIN_VERSION,
            true
        );
        
        // Pass license data to JavaScript
        $licenses = $this->license_handler->get_licenses();
        $global_license_id = get_option('rsl_global_license_id', 0);
        
        wp_localize_script('rsl-gutenberg', 'rslGutenberg', array(
            'licenses' => $licenses,
            'globalLicenseId' => $global_license_id,
            'nonce' => wp_create_nonce('rsl_nonce')
        ));
    }
    
    // === Help Tab Methods ===
    
    public function add_help_tabs_dashboard() {
        $screen = get_current_screen();
        
        $screen->add_help_tab(array(
            'id' => 'rsl-overview',
            'title' => __('RSL Overview', 'rsl-licensing'),
            'content' => '<h3>' . __('Really Simple Licensing (RSL)', 'rsl-licensing') . '</h3>
                <p>' . __('RSL enables you to define machine-readable licensing terms for your content. This helps AI companies, search engines, and other automated systems understand how they can use your content.', 'rsl-licensing') . '</p>
                <h4>' . __('Integration Methods', 'rsl-licensing') . '</h4>
                <ul>
                    <li><strong>' . __('HTML Head Injection', 'rsl-licensing') . '</strong>: ' . __('Embed licenses in page headers', 'rsl-licensing') . '</li>
                    <li><strong>' . __('HTTP Headers', 'rsl-licensing') . '</strong>: ' . __('Add Link headers to responses', 'rsl-licensing') . '</li>
                    <li><strong>' . __('robots.txt Integration', 'rsl-licensing') . '</strong>: ' . __('Extend robots.txt with RSL directives', 'rsl-licensing') . '</li>
                    <li><strong>' . __('RSS Enhancement', 'rsl-licensing') . '</strong>: ' . __('Add licensing to feed items', 'rsl-licensing') . '</li>
                </ul>'
        ));
        
        $screen->add_help_tab(array(
            'id' => 'rsl-quick-start',
            'title' => __('Quick Start', 'rsl-licensing'),
            'content' => '<h3>' . __('Getting Started', 'rsl-licensing') . '</h3>
                <ol>
                    <li>' . __('Create your first license using "Add New License"', 'rsl-licensing') . '</li>
                    <li>' . __('Configure license terms and permissions', 'rsl-licensing') . '</li>
                    <li>' . __('Set it as your global license', 'rsl-licensing') . '</li>
                    <li>' . __('Enable integration methods (HTML, robots.txt, etc.)', 'rsl-licensing') . '</li>
                    <li>' . __('Save settings', 'rsl-licensing') . '</li>
                </ol>
                <p>' . __('Your site will now broadcast machine-readable licensing terms!', 'rsl-licensing') . '</p>'
        ));
        
        $screen->set_help_sidebar(
            '<p><strong>' . __('RSL Resources', 'rsl-licensing') . '</strong></p>
            <p><a href="https://rslstandard.org" target="_blank">' . __('RSL Standard Documentation', 'rsl-licensing') . '</a></p>
            <p><a href="https://github.com/jameswlepage/rsl-wp" target="_blank">' . __('Plugin Documentation', 'rsl-licensing') . '</a></p>'
        );
    }
    
    public function add_help_tabs_licenses() {
        $screen = get_current_screen();
        
        $screen->add_help_tab(array(
            'id' => 'rsl-license-management',
            'title' => __('License Management', 'rsl-licensing'),
            'content' => '<h3>' . __('Managing Your Licenses', 'rsl-licensing') . '</h3>
                <p>' . __('This page shows all your RSL licenses. You can create multiple licenses for different content areas or use cases.', 'rsl-licensing') . '</p>
                <h4>' . __('License Actions', 'rsl-licensing') . '</h4>
                <ul>
                    <li><strong>' . __('Edit', 'rsl-licensing') . '</strong>: ' . __('Modify license terms and settings', 'rsl-licensing') . '</li>
                    <li><strong>' . __('Generate XML', 'rsl-licensing') . '</strong>: ' . __('Download the RSL XML for this license', 'rsl-licensing') . '</li>
                    <li><strong>' . __('Delete', 'rsl-licensing') . '</strong>: ' . __('Remove the license permanently', 'rsl-licensing') . '</li>
                </ul>'
        ));
        
        $screen->add_help_tab(array(
            'id' => 'rsl-payment-types',
            'title' => __('Payment Types', 'rsl-licensing'),
            'content' => '<h3>' . __('Understanding Payment Types', 'rsl-licensing') . '</h3>
                <ul>
                    <li><strong>' . __('Free', 'rsl-licensing') . '</strong>: ' . __('No payment required', 'rsl-licensing') . '</li>
                    <li><strong>' . __('Purchase', 'rsl-licensing') . '</strong>: ' . __('One-time payment', 'rsl-licensing') . '</li>
                    <li><strong>' . __('Subscription', 'rsl-licensing') . '</strong>: ' . __('Recurring payments (requires WooCommerce Subscriptions)', 'rsl-licensing') . '</li>
                    <li><strong>' . __('Attribution', 'rsl-licensing') . '</strong>: ' . __('Credit/attribution required (can be free or paid)', 'rsl-licensing') . '</li>
                    <li><strong>' . __('Training', 'rsl-licensing') . '</strong>: ' . __('AI training-specific licensing', 'rsl-licensing') . '</li>
                    <li><strong>' . __('Crawl', 'rsl-licensing') . '</strong>: ' . __('Web crawling permissions', 'rsl-licensing') . '</li>
                    <li><strong>' . __('Inference', 'rsl-licensing') . '</strong>: ' . __('AI inference usage rights', 'rsl-licensing') . '</li>
                </ul>
                <p><em>' . __('Note: Set amount to $0 for free licenses of any type. Amounts > $0 require WooCommerce for payment processing.', 'rsl-licensing') . '</em></p>'
        ));
    }
    
    public function add_help_tabs_add_license() {
        $screen = get_current_screen();
        
        $screen->add_help_tab(array(
            'id' => 'rsl-license-creation',
            'title' => __('Creating Licenses', 'rsl-licensing'),
            'content' => '<h3>' . __('License Creation Guide', 'rsl-licensing') . '</h3>
                <h4>' . __('Required Fields', 'rsl-licensing') . '</h4>
                <ul>
                    <li><strong>' . __('Name', 'rsl-licensing') . '</strong>: ' . __('Descriptive name for this license', 'rsl-licensing') . '</li>
                    <li><strong>' . __('Content URL', 'rsl-licensing') . '</strong>: ' . __('URL pattern this license covers (e.g., "/", "/blog/*", "*.pdf")', 'rsl-licensing') . '</li>
                </ul>
                <h4>' . __('URL Patterns', 'rsl-licensing') . '</h4>
                <ul>
                    <li><code>/</code> - ' . __('Entire site', 'rsl-licensing') . '</li>
                    <li><code>/blog/*</code> - ' . __('Blog directory and subdirectories', 'rsl-licensing') . '</li>
                    <li><code>*.pdf</code> - ' . __('All PDF files', 'rsl-licensing') . '</li>
                    <li><code>/api/*$</code> - ' . __('API endpoints (end anchor)', 'rsl-licensing') . '</li>
                </ul>'
        ));
        
        $screen->add_help_tab(array(
            'id' => 'rsl-woocommerce-setup',
            'title' => __('WooCommerce Setup', 'rsl-licensing'),
            'content' => '<h3>' . __('Setting Up Paid Licensing', 'rsl-licensing') . '</h3>
                <p>' . __('For paid licensing (amount > $0), you need WooCommerce installed and configured.', 'rsl-licensing') . '</p>
                <h4>' . __('Setup Steps', 'rsl-licensing') . '</h4>
                <ol>
                    <li>' . __('Install and activate WooCommerce plugin', 'rsl-licensing') . '</li>
                    <li>' . __('Complete WooCommerce setup wizard', 'rsl-licensing') . '</li>
                    <li>' . __('Configure payment gateways (Stripe, PayPal, etc.)', 'rsl-licensing') . '</li>
                    <li>' . __('Create paid license with amount > $0', 'rsl-licensing') . '</li>
                    <li>' . __('Set Server URL to built-in server option', 'rsl-licensing') . '</li>
                </ol>
                <p>' . __('The plugin will automatically create hidden WooCommerce products for your licenses and handle the complete payment-to-token flow.', 'rsl-licensing') . '</p>'
        ));
        
        $screen->set_help_sidebar(
            '<p><strong>' . __('Need Help?', 'rsl-licensing') . '</strong></p>
            <p><a href="https://rslstandard.org" target="_blank">' . __('RSL Standard Docs', 'rsl-licensing') . '</a></p>
            <p><a href="https://github.com/jameswlepage/rsl-wp/blob/main/docs/PAYMENTS.md" target="_blank">' . __('Payment Setup Guide', 'rsl-licensing') . '</a></p>
            <p><a href="https://github.com/jameswlepage/rsl-wp/blob/main/docs/DEVELOPER.md" target="_blank">' . __('Developer Guide', 'rsl-licensing') . '</a></p>'
        );
    }
}
</file>

</files>
