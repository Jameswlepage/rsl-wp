<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment Processor Registry
 *
 * Manages all available payment processors and their capabilities.
 */
class RSL_Payment_Registry {

	private static $instance = null;
	private $processors      = array();

	public static function get_instance() {
		if ( null === self::$instance ) {
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
		if ( class_exists( 'WooCommerce' ) ) {
			require_once RSL_PLUGIN_PATH . 'includes/processors/class-rsl-woocommerce-processor.php';
			$this->register_processor( new RSL_WooCommerce_Processor() );
		}

		// Allow third-party processors to register
		do_action( 'rsl_register_payment_processors', $this );
	}

	/**
	 * Register a payment processor
	 *
	 * @param RSL_Payment_Processor_Interface $processor
	 * @return bool
	 */
	public function register_processor( RSL_Payment_Processor_Interface $processor ) {
		if ( ! $processor->is_available() ) {
			return false;
		}

		$this->processors[ $processor->get_id() ] = $processor;
		return true;
	}

	/**
	 * Get all registered processors
	 *
	 * @return array
	 */
	public function get_processors() {
		return $this->processors;
	}

	/**
	 * Get available processors
	 *
	 * @return array
	 */
	public function get_available_processors() {
		return array_filter(
			$this->processors,
			function ( $processor ) {
				return $processor->is_available();
			}
		);
	}

	/**
	 * Get processor by ID
	 *
	 * @param string $processor_id
	 * @return RSL_Payment_Processor_Interface|null
	 */
	public function get_processor( $processor_id ) {
		return isset( $this->processors[ $processor_id ] ) ? $this->processors[ $processor_id ] : null;
	}

	/**
	 * Get the best processor for a license
	 *
	 * @param array $license
	 * @return RSL_Payment_Processor_Interface|null
	 */
	public function get_processor_for_license( $license ) {
		$payment_type = $license['payment_type'];
		$amount       = floatval( $license['amount'] );

		// For free licenses, no processor needed
		if ( $amount === 0.0 ) {
			return null;
		}

		// Look for processors that support this payment type
		foreach ( $this->processors as $processor ) {
			if ( $processor->supports_payment_type( $payment_type ) ) {
				return $processor;
			}
		}

		return null;
	}

	/**
	 * Check if any processor can handle paid licenses
	 *
	 * @return bool
	 */
	public function has_payment_capability() {
		foreach ( $this->processors as $processor ) {
			$supported_types = $processor->get_supported_payment_types();
			if ( ! empty( $supported_types ) ) {
				return true;
			}
		}
		return false;
	}
}
