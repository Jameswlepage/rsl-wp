<?php
/**
 * RSL License management class
 *
 * Handles CRUD operations for RSL licenses and provides
 * methods for license validation and retrieval.
 *
 * @package RSL_WP
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RSL License management class.
 *
 * Manages license creation, retrieval, updating, and deletion
 * operations for the RSL WordPress plugin.
 */
class RSL_License {

	/**
	 * Database table name for storing licenses.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor.
	 *
	 * Initializes the license class and sets up database table name.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'rsl_licenses';
	}

	/**
	 * Create a new license.
	 *
	 * @param array $data License data array containing fields like name, content_url, etc.
	 * @return int|false License ID on success, false on failure.
	 */
	public function create_license( $data ) {
		global $wpdb;

		$defaults = array(
			'name'             => '',
			'description'      => '',
			'content_url'      => '',
			'server_url'       => '',
			'encrypted'        => 0,
			'lastmod'          => current_time( 'mysql' ),
			'permits_usage'    => '',
			'permits_user'     => '',
			'permits_geo'      => '',
			'prohibits_usage'  => '',
			'prohibits_user'   => '',
			'prohibits_geo'    => '',
			'payment_type'     => 'free',
			'standard_url'     => '',
			'custom_url'       => '',
			'amount'           => 0,
			'currency'         => 'USD',
			'warranty'         => '',
			'disclaimer'       => '',
			'schema_url'       => '',
			'copyright_holder' => '',
			'copyright_type'   => '',
			'contact_email'    => '',
			'contact_url'      => '',
			'terms_url'        => '',
			'active'           => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		// Validate required fields.
		if ( empty( $data['name'] ) || empty( $data['content_url'] ) ) {
			// Error logged elsewhere if needed.
			return false;
		}

		$result = $wpdb->insert(
			$this->table_name,
			$data,
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%f',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
			)
		);

		if ( false === $result ) {
			// Database error handled elsewhere.
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get a license by ID.
	 *
	 * @param int $id License ID.
	 * @return array|null License data array on success, null on failure.
	 */
	public function get_license( $id ) {
		global $wpdb;

		$id = intval( $id );
		if ( 0 >= $id ) {
			return null;
		}

		// Check cache first.
		$cache_key = "rsl_license_{$id}";
		$result    = wp_cache_get( $cache_key, 'rsl_licenses' );

		if ( false === $result ) {
			$result = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}rsl_licenses` WHERE id = %d",
					$id
				),
				ARRAY_A
			);

			// Cache the result for 1 hour.
			if ( $result ) {
				wp_cache_set( $cache_key, $result, 'rsl_licenses', 3600 );
			}
		}

		if ( $wpdb->last_error ) {
			// Database error handled elsewhere.
			return null;
		}

		return $result;
	}

	/**
	 * Get multiple licenses with optional filtering.
	 *
	 * @param array $args Optional arguments for filtering and sorting.
	 * @return array|null Array of license data on success, null on failure.
	 */
	public function get_licenses( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'active'  => 1,
			'limit'   => -1,
			'offset'  => 0,
			'orderby' => 'name',
			'order'   => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate and sanitize orderby to prevent SQL injection.
		$allowed_orderby = array( 'id', 'name', 'created_at', 'updated_at', 'lastmod', 'payment_type' );
		if ( ! in_array( $args['orderby'], $allowed_orderby, true ) ) {
			$args['orderby'] = 'name';
		}

		// Validate order parameter.
		$args['order'] = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

		// Validate numeric parameters.
		$args['limit']  = max( -1, intval( $args['limit'] ) );
		$args['offset'] = max( 0, intval( $args['offset'] ) );

		$where  = array();
		$values = array();

		if ( null !== $args['active'] ) {
			$where[]  = 'active = %d';
			$values[] = intval( $args['active'] );
		}

		$base_sql = "SELECT * FROM `{$wpdb->prefix}rsl_licenses`";

		if ( ! empty( $where ) ) {
			$base_sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		// Safe to use directly since $args['orderby'] is validated against allowlist.
		$base_sql .= " ORDER BY `{$args['orderby']}` {$args['order']}";

		if ( 0 < $args['limit'] ) {
			$base_sql .= ' LIMIT %d';
			$values[]  = $args['limit'];

			if ( 0 < $args['offset'] ) {
				$base_sql .= ' OFFSET %d';
				$values[]  = $args['offset'];
			}
		}

		if ( ! empty( $values ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare( $base_sql, $values );
		} else {
			$sql = $base_sql;
		}

		// Check cache first for listings.
		$cache_key = 'rsl_licenses_' . md5( $sql . serialize( $values ) );
		$results   = wp_cache_get( $cache_key, 'rsl_licenses' );

		if ( false === $results ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$results = $wpdb->get_results( $sql, ARRAY_A );

			// Cache the results for 30 minutes.
			if ( ! $wpdb->last_error ) {
				wp_cache_set( $cache_key, $results, 'rsl_licenses', 1800 );
			}
		}

		// Add error handling.
		if ( $wpdb->last_error ) {
			// License query error handled elsewhere.
			return array();
		}

		return $results ? $results : array();
	}

	/**
	 * Update an existing license.
	 *
	 * @param int   $id   License ID.
	 * @param array $data Data to update.
	 * @return bool True on success, false on failure.
	 */
	public function update_license( $id, $data ) {
		global $wpdb;

		$id = intval( $id );
		if ( 0 >= $id ) {
			// Invalid license ID logged elsewhere.
			return false;
		}

		$data['updated_at'] = current_time( 'mysql' );

		$format = array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%f',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s',
		);

		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			// Database error handled elsewhere.
			return false;
		}

		return false !== $result;
	}

	/**
	 * Delete a license.
	 *
	 * @param int $id License ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_license( $id ) {
		global $wpdb;

		$id = intval( $id );
		if ( 0 >= $id ) {
			// Invalid license ID logged elsewhere.
			return false;
		}

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( false === $result ) {
			// Database error handled elsewhere.
			return false;
		}

		return false !== $result;
	}

	/**
	 * Generate RSL XML from license data.
	 *
	 * @param array $license_data License data array.
	 * @param array $options      Optional settings for XML generation.
	 * @return string Generated RSL XML string.
	 */
	public function generate_rsl_xml( $license_data, $options = array() ) {
		$defaults = array(
			'namespace'  => 'https://rslstandard.org/rsl',
			'standalone' => true,
		);

		$options = wp_parse_args( $options, $defaults );

		$xml = '';

		if ( $options['standalone'] ) {
			$xml .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		}

		$xml .= '<rsl xmlns="' . esc_attr( $options['namespace'] ) . '">' . "\n";
		$xml .= '  <content url="' . esc_attr( $license_data['content_url'] ) . '"';

		if ( ! empty( $license_data['server_url'] ) ) {
			$xml .= ' server="' . esc_attr( $license_data['server_url'] ) . '"';
		}

		if ( ! empty( $license_data['encrypted'] ) && 1 === $license_data['encrypted'] ) {
			$xml .= ' encrypted="true"';
		}

		if ( ! empty( $license_data['lastmod'] ) ) {
			$xml .= ' lastmod="' . esc_attr( gmdate( 'c', strtotime( $license_data['lastmod'] ) ) ) . '"';
		}

		$xml .= '>' . "\n";

		if ( ! empty( $license_data['schema_url'] ) ) {
			$xml .= '    <schema>' . esc_html( $license_data['schema_url'] ) . '</schema>' . "\n";
		}

		if ( ! empty( $license_data['copyright_holder'] ) ) {
			$xml .= '    <copyright';
			if ( ! empty( $license_data['copyright_type'] ) ) {
				$xml .= ' type="' . esc_attr( $license_data['copyright_type'] ) . '"';
			}
			if ( ! empty( $license_data['contact_email'] ) ) {
				$xml .= ' contactEmail="' . esc_attr( $license_data['contact_email'] ) . '"';
			}
			if ( ! empty( $license_data['contact_url'] ) ) {
				$xml .= ' contactUrl="' . esc_attr( $license_data['contact_url'] ) . '"';
			}
			$xml .= '>' . esc_html( $license_data['copyright_holder'] ) . '</copyright>' . "\n";
		}

		if ( ! empty( $license_data['terms_url'] ) ) {
			$xml .= '    <terms>' . esc_html( $license_data['terms_url'] ) . '</terms>' . "\n";
		}

		$xml .= '    <license>' . "\n";

		if ( ! empty( $license_data['permits_usage'] ) ) {
			$xml .= '      <permits type="usage">' . esc_html( $license_data['permits_usage'] ) . '</permits>' . "\n";
		}

		if ( ! empty( $license_data['permits_user'] ) ) {
			$xml .= '      <permits type="user">' . esc_html( $license_data['permits_user'] ) . '</permits>' . "\n";
		}

		if ( ! empty( $license_data['permits_geo'] ) ) {
			$xml .= '      <permits type="geo">' . esc_html( $license_data['permits_geo'] ) . '</permits>' . "\n";
		}

		if ( ! empty( $license_data['prohibits_usage'] ) ) {
			$xml .= '      <prohibits type="usage">' . esc_html( $license_data['prohibits_usage'] ) . '</prohibits>' . "\n";
		}

		if ( ! empty( $license_data['prohibits_user'] ) ) {
			$xml .= '      <prohibits type="user">' . esc_html( $license_data['prohibits_user'] ) . '</prohibits>' . "\n";
		}

		if ( ! empty( $license_data['prohibits_geo'] ) ) {
			$xml .= '      <prohibits type="geo">' . esc_html( $license_data['prohibits_geo'] ) . '</prohibits>' . "\n";
		}

		if ( ! empty( $license_data['payment_type'] ) && 'free' !== $license_data['payment_type'] ) {
			$xml .= '      <payment type="' . esc_attr( $license_data['payment_type'] ) . '">' . "\n";

			if ( ! empty( $license_data['standard_url'] ) ) {
				$xml .= '        <standard>' . esc_html( $license_data['standard_url'] ) . '</standard>' . "\n";
			}

			if ( ! empty( $license_data['custom_url'] ) ) {
				$xml .= '        <custom>' . esc_html( $license_data['custom_url'] ) . '</custom>' . "\n";
			}

			if ( ! empty( $license_data['amount'] ) && $license_data['amount'] > 0 ) {
				$xml .= '        <amount currency="' . esc_attr( $license_data['currency'] ) . '">' .
						esc_html( $license_data['amount'] ) . '</amount>' . "\n";
			}

			$xml .= '      </payment>' . "\n";
		} else {
			$xml .= '      <payment type="free"/>' . "\n";
		}

		if ( ! empty( $license_data['warranty'] ) ) {
			$xml .= '      <legal type="warranty">' . esc_html( $license_data['warranty'] ) . '</legal>' . "\n";
		}

		if ( ! empty( $license_data['disclaimer'] ) ) {
			$xml .= '      <legal type="disclaimer">' . esc_html( $license_data['disclaimer'] ) . '</legal>' . "\n";
		}

		$xml .= '    </license>' . "\n";
		$xml .= '  </content>' . "\n";
		$xml .= '</rsl>';

		return $xml;
	}

	/**
	 * Get available usage options for license configuration.
	 *
	 * @return array Array of usage options with labels.
	 */
	public function get_usage_options() {
		return array(
			'all'          => __( 'All automated processing', 'rsl-wp' ),
			'train-ai'     => __( 'Train AI model', 'rsl-wp' ),
			'train-genai'  => __( 'Train generative AI model', 'rsl-wp' ),
			'ai-use'       => __( 'Use as AI input (RAG)', 'rsl-wp' ),
			'ai-summarize' => __( 'AI summarization', 'rsl-wp' ),
			'search'       => __( 'Search indexing', 'rsl-wp' ),
		);
	}

	/**
	 * Get available user options for license configuration.
	 *
	 * @return array Array of user options with labels.
	 */
	public function get_user_options() {
		return array(
			'commercial'     => __( 'Commercial use', 'rsl-wp' ),
			'non-commercial' => __( 'Non-commercial use', 'rsl-wp' ),
			'education'      => __( 'Educational use', 'rsl-wp' ),
			'government'     => __( 'Government use', 'rsl-wp' ),
			'personal'       => __( 'Personal use', 'rsl-wp' ),
		);
	}

	/**
	 * Get available payment options for license configuration.
	 *
	 * @return array Array of payment options with labels.
	 */
	public function get_payment_options() {
		return array(
			'free'         => __( 'Free', 'rsl-wp' ),
			'purchase'     => __( 'One-time purchase', 'rsl-wp' ),
			'subscription' => __( 'Subscription', 'rsl-wp' ),
			'training'     => __( 'Per training use', 'rsl-wp' ),
			'crawl'        => __( 'Per crawl', 'rsl-wp' ),
			'inference'    => __( 'Per inference', 'rsl-wp' ),
			'attribution'  => __( 'Attribution required', 'rsl-wp' ),
			'royalty'      => __( 'Royalty', 'rsl-wp' ),
		);
	}

	/**
	 * Get available warranty options for license configuration.
	 *
	 * @return array Array of warranty options with labels.
	 */
	public function get_warranty_options() {
		return array(
			'ownership'       => __( 'Ownership rights', 'rsl-wp' ),
			'authority'       => __( 'Authorization to license', 'rsl-wp' ),
			'no-infringement' => __( 'No third-party infringement', 'rsl-wp' ),
			'privacy-consent' => __( 'Privacy consents obtained', 'rsl-wp' ),
			'no-malware'      => __( 'Free from malware', 'rsl-wp' ),
		);
	}

	/**
	 * Get available disclaimer options for license configuration.
	 *
	 * @return array Array of disclaimer options with labels.
	 */
	public function get_disclaimer_options() {
		return array(
			'as-is'        => __( 'Provided "as is"', 'rsl-wp' ),
			'no-warranty'  => __( 'No warranties', 'rsl-wp' ),
			'no-liability' => __( 'No liability', 'rsl-wp' ),
			'no-indemnity' => __( 'No indemnification', 'rsl-wp' ),
		);
	}
}
