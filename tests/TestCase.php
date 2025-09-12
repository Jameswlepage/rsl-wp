<?php
/**
 * Base test case for RSL Licensing tests
 *
 * @package RSL_Licensing
 */

namespace RSL\Tests;

use WP_UnitTestCase;
use Brain\Monkey;

/**
 * Base test case class
 */
class TestCase extends WP_UnitTestCase {

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Reset any global state
        $this->reset_globals();
        
        // Create test database tables
        $this->create_test_tables();
    }

    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Reset global variables
     */
    protected function reset_globals() {
        global $wpdb;
        
        // Clear any cached data
        wp_cache_flush();
        
        // Reset WordPress options
        delete_option('rsl_global_license_id');
        delete_option('rsl_enable_html_injection');
        delete_option('rsl_enable_http_headers');
        delete_option('rsl_enable_robots_txt');
        delete_option('rsl_enable_rss_feed');
        delete_option('rsl_enable_media_metadata');
        delete_option('rsl_jwt_secret');
    }

    /**
     * Create test database tables
     */
    protected function create_test_tables() {
        global $wpdb;
        
        $rsl_licensing = RSL_Licensing::get_instance();
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($rsl_licensing);
        $method = $reflection->getMethod('create_tables');
        $method->setAccessible(true);
        $method->invoke($rsl_licensing);
    }

    /**
     * Create a test license
     *
     * @param array $args License arguments
     * @return int License ID
     */
    protected function create_test_license($args = []) {
        $defaults = [
            'name' => 'Test License',
            'description' => 'Test license description',
            'content_url' => '/test-content',
            'server_url' => '',
            'encrypted' => 0,
            'permits_usage' => 'search',
            'permits_user' => 'non-commercial',
            'permits_geo' => '',
            'prohibits_usage' => 'train-ai,train-genai',
            'prohibits_user' => 'commercial',
            'prohibits_geo' => '',
            'payment_type' => 'free',
            'amount' => 0,
            'currency' => 'USD',
            'active' => 1
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $license_handler = new \RSL_License();
        return $license_handler->create_license($args);
    }

    /**
     * Create a test OAuth client
     *
     * @param array $args Client arguments
     * @return array Client data
     */
    protected function create_test_oauth_client($args = []) {
        $defaults = [
            'client_name' => 'Test Client'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $oauth_client = \RSL_OAuth_Client::get_instance();
        return $oauth_client->create_client($args['client_name'], $args);
    }

    /**
     * Generate a test JWT token
     *
     * @param array $payload Token payload
     * @return string JWT token
     */
    protected function generate_test_jwt($payload = []) {
        $defaults = [
            'iss' => 'http://example.org',
            'aud' => 'example.org',
            'sub' => 'test-client',
            'iat' => time(),
            'exp' => time() + 3600,
            'lic' => 1
        ];
        
        $payload = wp_parse_args($payload, $defaults);
        
        // Use reflection to access private method
        $server = new \RSL_Server();
        $reflection = new \ReflectionClass($server);
        $method = $reflection->getMethod('jwt_encode_payload');
        $method->setAccessible(true);
        
        return $method->invoke($server, $payload);
    }

    /**
     * Mock WordPress HTTP API response
     *
     * @param array $response_args Response arguments
     */
    protected function mock_http_response($response_args = []) {
        $defaults = [
            'response' => [
                'code' => 200,
                'message' => 'OK'
            ],
            'body' => '{"success": true}',
            'headers' => []
        ];
        
        $response = wp_parse_args($response_args, $defaults);
        
        add_filter('pre_http_request', function() use ($response) {
            return $response;
        });
    }

    /**
     * Assert that a WordPress hook was called
     *
     * @param string $hook Hook name
     * @param int $times Number of times called (default 1)
     */
    protected function assertHookCalled($hook, $times = 1) {
        $this->assertEquals($times, did_action($hook), "Hook '{$hook}' was not called {$times} time(s)");
    }

    /**
     * Assert that a string is valid JSON
     *
     * @param string $json JSON string
     * @param string $message Error message
     */
    protected function assertValidJson($json, $message = '') {
        json_decode($json);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), $message);
    }

    /**
     * Assert that a string is a valid RSL XML
     *
     * @param string $xml RSL XML string
     * @param string $message Error message
     */
    protected function assertValidRslXml($xml, $message = '') {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        
        // Check for RSL namespace
        $this->assertStringContains('https://rslstandard.org/rsl', $xml, $message);
        
        // Check for required elements
        $this->assertStringContains('<rsl', $xml, $message);
        $this->assertStringContains('<content', $xml, $message);
        $this->assertStringContains('<license', $xml, $message);
    }
}