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
            header('Access-Control-Allow-Origin: *');
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
                header('Access-Control-Allow-Origin: *');
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
            header('Access-Control-Allow-Origin: *');
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
                header('Access-Control-Allow-Origin: *');
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
            header('Access-Control-Allow-Origin: *');
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
        header('Access-Control-Allow-Origin: *');
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
        $request_uri = $_SERVER['REQUEST_URI'];
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
        $b64 = fn($d)=> rtrim(strtr(base64_encode(is_string($d)?$d:wp_json_encode($d)), '+/', '-_'), '=');
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
        $b64d = fn($x)=> base64_decode(strtr($x, '-_', '+/'));
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
        if ($pattern && !$this->url_matches_pattern(home_url($_SERVER['REQUEST_URI']), $pattern)) {
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
        $current = home_url($_SERVER['REQUEST_URI']);
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