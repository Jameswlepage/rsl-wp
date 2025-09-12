<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_Admin {
    
    private $license_handler;
    
    public function __construct() {
        $this->license_handler = new RSL_License();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_rsl_save_license', array($this, 'ajax_save_license'));
        add_action('wp_ajax_rsl_delete_license', array($this, 'ajax_delete_license'));
        add_action('wp_ajax_rsl_generate_xml', array($this, 'ajax_generate_xml'));
        
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        
        // Gutenberg support
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('init', array($this, 'register_meta_fields'));
    }
    
    public function add_admin_menu() {
        // Add main menu page with RSL icon
        add_menu_page(
            __('RSL Licensing', 'rsl-licensing'),
            __('RSL Licensing', 'rsl-licensing'),
            'manage_options',
            'rsl-licensing',
            array($this, 'admin_page'),
            $this->get_menu_icon(),
            30 // Position after Settings
        );
        
        // Add submenu pages under RSL Licensing
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
        register_setting('rsl_settings', 'rsl_global_license_id');
        register_setting('rsl_settings', 'rsl_enable_html_injection');
        register_setting('rsl_settings', 'rsl_enable_http_headers');
        register_setting('rsl_settings', 'rsl_enable_robots_txt');
        register_setting('rsl_settings', 'rsl_enable_rss_feed');
        register_setting('rsl_settings', 'rsl_enable_media_metadata');
        register_setting('rsl_settings', 'rsl_default_namespace');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'rsl') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('rsl-admin', RSL_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), RSL_PLUGIN_VERSION, true);
            wp_enqueue_style('rsl-admin', RSL_PLUGIN_URL . 'admin/css/admin.css', array(), RSL_PLUGIN_VERSION);
            
            wp_localize_script('rsl-admin', 'rsl_ajax', array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rsl_nonce')
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
        
        // Validate URLs
        $content_url = esc_url_raw($_POST['content_url']);
        if (!empty($content_url) && !filter_var($content_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(array(
                'message' => __('Invalid Content URL format.', 'rsl-licensing')
            ));
            return;
        }
        
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
        
        // Validate email
        $contact_email = sanitize_email($_POST['contact_email']);
        if (!empty($contact_email) && !is_email($contact_email)) {
            wp_send_json_error(array(
                'message' => __('Invalid email address format.', 'rsl-licensing')
            ));
            return;
        }
        
        // Validate payment type
        $allowed_payment_types = array('free', 'purchase', 'subscription', 'training', 'crawl', 'inference', 'attribution');
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
        // Return path to PNG icon for WordPress admin menu
        return RSL_PLUGIN_URL . 'assets/icon-128x128.png';
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
}