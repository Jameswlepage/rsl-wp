<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove all RSL options from the database
$options_to_delete = array(
	'rsl_global_license_id',
	'rsl_enable_html_injection',
	'rsl_enable_http_headers',
	'rsl_enable_robots_txt',
	'rsl_enable_rss_feed',
	'rsl_enable_media_metadata',
	'rsl_default_namespace',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
	delete_site_option( $option );
}

// Remove all post meta related to RSL licensing
delete_metadata( 'post', 0, '_rsl_license_id', '', true );
delete_metadata( 'post', 0, '_rsl_override_content_url', '', true );

// Ask user if they want to remove the licenses table and data
$remove_data = get_option( 'rsl_remove_data_on_uninstall', false );

if ( $remove_data ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'rsl_licenses';

	// Drop the licenses table
	$wpdb->query( "DROP TABLE IF EXISTS `$table_name`" );

	// Remove the cleanup option itself
	delete_option( 'rsl_remove_data_on_uninstall' );
}
