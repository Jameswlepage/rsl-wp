<?php
/**
 * PHPUnit bootstrap file
 *
 * @package RSL_Licensing
 */

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv('WP_PHPUNIT__DIR') ?: '/tmp/wordpress-tests-lib/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname(__DIR__) . '/rsl-licensing.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require getenv('WP_PHPUNIT__DIR') ?: '/tmp/wordpress-tests-lib/includes/bootstrap.php';

// Load Brain Monkey
\Brain\Monkey\setUp();

// Register shutdown function to tear down Brain Monkey
register_shutdown_function(function() {
    \Brain\Monkey\tearDown();
});