<?php

if (!defined('ABSPATH')) {
    exit;
}

class RSL_Server {
    
    private $license_handler;
    
    public function __construct() {
        $this->license_handler = new RSL_License();
        
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_license_requests'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Add authentication handling
        add_action('wp', array($this, 'handle_license_authentication'));
        
        // REST API endpoints for license server functionality
        add_action('rest_api_init', array($this, 'register_rest_routes'));
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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: User-Agent, Authorization');
        
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
        header('Access-Control-Allow-Origin: *');
        
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
        // Implement URL pattern matching similar to robots.txt rules
        
        // Exact match
        if ($url === $pattern) {
            return true;
        }
        
        // Pattern matching with wildcards
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = str_replace('$', '\$', $pattern);
        $pattern = '/^' . str_replace('/', '\/', $pattern) . '/';
        
        return preg_match($pattern, $url);
    }
    
    private function requires_license_auth() {
        // Check if current request matches a license that requires server authentication
        $current_url = home_url($_SERVER['REQUEST_URI']);
        
        $licenses = $this->license_handler->get_licenses(array('active' => 1));
        
        foreach ($licenses as $license) {
            if (!empty($license['server_url']) && 
                $this->url_matches_pattern($current_url, $license['content_url'])) {
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
    
    private function validate_license_token($token) {
        // This is a simplified validation
        // In a real implementation, you would validate against a license server
        
        // For demonstration, accept any non-empty token
        return !empty($token) && strlen($token) > 10;
    }
    
    private function send_license_required_response() {
        status_header(401);
        header('WWW-Authenticate: License error="invalid_request", ' .
               'error_description="Access to this resource requires a valid license", ' .
               'authorization_uri="' . home_url('.well-known/rsl/') . '"');
        header('Content-Type: text/plain');
        
        echo "License required. Please obtain a license at " . home_url('.well-known/rsl/');
    }
    
    private function send_invalid_license_response() {
        status_header(401);
        header('WWW-Authenticate: License error="invalid_license", ' .
               'error_description="The provided license token is invalid or expired", ' .
               'authorization_uri="' . home_url('.well-known/rsl/') . '"');
        header('Content-Type: text/plain');
        
        echo "Invalid license token. Please obtain a valid license at " . home_url('.well-known/rsl/');
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
                'payment_processing' => false  // Not implemented in this version
            )
        );
    }
}