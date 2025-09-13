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
                'label' => __('License Product Visibility', 'rsl-wp'),
                'options' => [
                    'hidden' => __('Hidden (default)', 'rsl-wp'),
                    'catalog' => __('Visible in catalog', 'rsl-wp'),
                    'search' => __('Visible in search', 'rsl-wp')
                ],
                'default' => 'hidden',
                'description' => __('How should auto-created license products appear in your store?', 'rsl-wp')
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
            /* translators: %s: license name */
            'post_title' => sprintf(__('RSL License: %s', 'rsl-wp'), $license['name']),
            /* translators: %s: license content URL */
            'post_content' => sprintf(__('Digital content license for %s', 'rsl-wp'), $license['content_url']),
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