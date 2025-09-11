<?php
/**
 * Plugin Name: RSL Licensing for WordPress
 * Plugin URI: https://github.com/jameswlepage/rsl-wp
 * Description: Complete Really Simple Licensing (RSL) support for WordPress sites. Define machine-readable licensing terms for your content, enabling AI companies and crawlers to properly license your digital assets.
 * Version: 1.0.0
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
        $this->create_tables();
        $this->create_default_options();
        $this->seed_global_license();
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
        dbDelta($sql);
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

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }

    private function seed_global_license()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . "rsl_licenses";
        
        $existing_licenses = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($existing_licenses == 0) {
            $site_url = home_url();
            $site_name = get_bloginfo('name');
            $admin_email = get_option('admin_email');
            
            $default_license = [
                'name' => __('Default Site License', 'rsl-licensing'),
                'description' => __('Default licensing terms for this WordPress site', 'rsl-licensing'),
                'content_url' => $site_url . '/*',
                'server_url' => $site_url . '/wp-json/rsl/v1/',
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
                'copyright_type' => 'copyright',
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
                update_option('rsl_global_license_id', $license_id);
            }
        }
    }
}

RSL_Licensing::get_instance();
