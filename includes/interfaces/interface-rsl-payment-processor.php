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