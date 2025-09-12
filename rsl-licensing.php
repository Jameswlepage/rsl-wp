<?php
/**
 * Plugin Name: RSL Licensing for WordPress
 * Plugin URI: https://github.com/jameswlepage/rsl-wp
 * Description: Complete Really Simple Licensing (RSL) support for WordPress sites. Define machine-readable licensing terms for your content, enabling AI companies and crawlers to properly license your digital assets.
 * Version: 0.0.1
 * Author: James W. LePage
 * Author URI: https://j.cv
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rsl-licensing
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

if (!defined("ABSPATH")) {
    exit();
}

define("RSL_PLUGIN_VERSION", "1.0.0");
define("RSL_PLUGIN_URL", plugin_dir_url(__FILE__));
define("RSL_PLUGIN_PATH", plugin_dir_path(__FILE__));
define("RSL_PLUGIN_BASENAME", plugin_basename(__FILE__));

class RSL_Licensing
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action("init", [$this, "init"]);
        register_activation_hook(__FILE__, [$this, "activate"]);
        register_deactivation_hook(__FILE__, [$this, "deactivate"]);
    }

    public function init()
    {
        $this->load_textdomain();
        $this->includes();
        $this->init_hooks();
    }

    private function load_textdomain()
    {
        load_plugin_textdomain(
            "rsl-licensing",
            false,
            dirname(RSL_PLUGIN_BASENAME) . "/languages",
        );
    }

    private function includes()
    {
        require_once RSL_PLUGIN_PATH . "includes/class-rsl-license.php";
        require_once RSL_PLUGIN_PATH . "includes/class-rsl-admin.php";
        require_once RSL_PLUGIN_PATH . "includes/class-rsl-frontend.php";
        require_once RSL_PLUGIN_PATH . "includes/class-rsl-robots.php";
        require_once RSL_PLUGIN_PATH . "includes/class-rsl-rss.php";
        require_once RSL_PLUGIN_PATH . "includes/class-rsl-media.php";
        require_once RSL_PLUGIN_PATH . "includes/class-rsl-server.php";
    }

    private function init_hooks()
    {
        if (is_admin()) {
            new RSL_Admin();
        }

        new RSL_Frontend();
        new RSL_Robots();
        new RSL_RSS();
        new RSL_Media();
        new RSL_Server();
    }

    public function activate()
    {
        if (!$this->create_tables()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('RSL Licensing: Failed to create required database tables. Please check database permissions.', 'rsl-licensing'));
        }
        
        if (!$this->create_default_options()) {
            error_log('RSL: Warning - Failed to create default options during activation');
        }
        
        if (!$this->seed_global_license()) {
            error_log('RSL: Warning - Failed to create default license during activation');
        }
        
        if (!$this->create_database_indexes()) {
            error_log('RSL: Warning - Failed to create database indexes during activation');
        }
        
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    private function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . "rsl_licenses";

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            content_url text NOT NULL,
            server_url varchar(255),
            encrypted tinyint(1) DEFAULT 0,
            lastmod datetime,
            permits_usage text,
            permits_user text,
            permits_geo text,
            prohibits_usage text,
            prohibits_user text,
            prohibits_geo text,
            payment_type varchar(50) DEFAULT 'free',
            standard_url varchar(255),
            custom_url varchar(255),
            amount decimal(10,2),
            currency varchar(3),
            warranty text,
            disclaimer text,
            schema_url varchar(255),
            copyright_holder varchar(255),
            copyright_type varchar(20),
            contact_email varchar(255),
            contact_url varchar(255),
            terms_url varchar(255),
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . "wp-admin/includes/upgrade.php";
        $result = dbDelta($sql);
        
        // Verify table was created successfully
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if (!$table_exists) {
            error_log('RSL: Failed to create database table: ' . $table_name);
            if ($wpdb->last_error) {
                error_log('RSL: Database error: ' . $wpdb->last_error);
            }
            return false;
        }
        
        return true;
    }

    private function create_default_options()
    {
        $default_options = [
            "rsl_global_license_id" => 0,
            "rsl_enable_html_injection" => 1,
            "rsl_enable_http_headers" => 1,
            "rsl_enable_robots_txt" => 1,
            "rsl_enable_rss_feed" => 1,
            "rsl_enable_media_metadata" => 1,
            "rsl_default_namespace" => "https://rslstandard.org/rsl",
        ];

        $success = true;
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                $result = add_option($option, $value);
                if (!$result) {
                    error_log('RSL: Failed to create option: ' . $option);
                    $success = false;
                }
            }
        }
        
        return $success;
    }

    private function seed_global_license()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . "rsl_licenses";
        
        $existing_licenses = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($wpdb->last_error) {
            error_log('RSL: Database error checking existing licenses: ' . $wpdb->last_error);
            return false;
        }
        
        if ($existing_licenses == 0) {
            $site_url = home_url();
            $site_name = get_bloginfo('name');
            $admin_email = get_option('admin_email');
            
            $default_license = [
                'name' => __('Default Site License', 'rsl-licensing'),
                'description' => __('Default licensing terms for this WordPress site', 'rsl-licensing'),
                'content_url' => $site_url,
                'server_url' => '', // Don't require server authentication for default license
                'encrypted' => 0,
                'lastmod' => current_time('mysql'),
                'permits_usage' => 'search',
                'permits_user' => 'non-commercial',
                'permits_geo' => '',
                'prohibits_usage' => 'train-ai,train-genai',
                'prohibits_user' => 'commercial',
                'prohibits_geo' => '',
                'payment_type' => 'attribution',
                'standard_url' => '',
                'custom_url' => '',
                'amount' => 0,
                'currency' => 'USD',
                'warranty' => 'ownership,authority',
                'disclaimer' => 'as-is,no-warranty',
                'schema_url' => 'https://rslstandard.org/schema',
                'copyright_holder' => $site_name,
                'copyright_type' => 'organization',
                'contact_email' => $admin_email,
                'contact_url' => $site_url . '/contact',
                'terms_url' => $site_url . '/terms',
                'active' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
            
            $license_inserted = $wpdb->insert(
                $table_name,
                $default_license,
                [
                    '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s',
                    '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s',
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                    '%d', '%s', '%s'
                ]
            );
            
            if ($license_inserted) {
                $license_id = $wpdb->insert_id;
                $option_updated = update_option('rsl_global_license_id', $license_id);
                if (!$option_updated) {
                    error_log('RSL: Failed to set global license ID: ' . $license_id);
                    return false;
                }
                return true;
            } else {
                error_log('RSL: Failed to insert default license: ' . $wpdb->last_error);
                return false;
            }
        }
        
        return true; // Licenses already exist
    }
    
    private function create_database_indexes()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . "rsl_licenses";
        
        $indexes = array(
            "idx_active" => "active",
            "idx_updated_at" => "updated_at", 
            "idx_name" => "name"
        );
        
        $success = true;
        
        foreach ($indexes as $index_name => $column) {
            // Check if index already exists
            $index_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW INDEX FROM {$table_name} WHERE Key_name = %s",
                $index_name
            ));
            
            if (!$index_exists) {
                $sql = "ALTER TABLE {$table_name} ADD INDEX {$index_name} ({$column})";
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    error_log('RSL: Failed to create index ' . $index_name . ': ' . $wpdb->last_error);
                    $success = false;
                }
            }
        }
        
        return $success;
    }
}

RSL_Licensing::get_instance();

if (!function_exists('rsl_has_license')) {
    function rsl_has_license() {
        static $frontend = null;
        
        if ($frontend === null) {
            $frontend = new RSL_Frontend();
        }
        
        $license_data = $frontend->get_current_page_license();
        return !empty($license_data);
    }
}

if (!function_exists('rsl_get_license_info')) {
    function rsl_get_license_info() {
        static $frontend = null;
        
        if ($frontend === null) {
            $frontend = new RSL_Frontend();
        }
        
        return $frontend->get_current_page_license();
    }
}
