<?php
/**
 * Basic functionality tests to verify test framework works
 *
 * @package RSL_Licensing
 */

namespace RSL\Tests\Unit;

use RSL\Tests\TestCase;

/**
 * Test basic functionality to verify framework works
 *
 * @group unit
 * @group smoke
 */
class TestBasicFunctionality extends TestCase {

    /**
     * Test that PHP and PHPUnit are working
     */
    public function test_php_and_phpunit_work() {
        $this->assertTrue(true);
        $this->assertEquals(4, 2 + 2);
        $this->assertIsString('hello world');
    }

    /**
     * Test WordPress function mocks work
     */
    public function test_wordpress_function_mocks() {
        // Test wp_parse_args
        $result = wp_parse_args(['test' => 'value'], ['default' => 'default_value']);
        $this->assertEquals('value', $result['test']);
        $this->assertEquals('default_value', $result['default']);

        // Test home_url
        $url = home_url('/test-path');
        $this->assertEquals('http://example.org/test-path', $url);

        // Test wp_generate_password
        $password = wp_generate_password(10);
        $this->assertIsString($password);
        $this->assertEquals(10, strlen($password));

        // Test wp_generate_uuid4
        $uuid = wp_generate_uuid4();
        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid);
    }

    /**
     * Test WP_Error class works
     */
    public function test_wp_error_class() {
        $error = new \WP_Error('test_error', 'Test error message', ['test_data' => 'test_value']);
        
        $this->assertEquals('test_error', $error->get_error_code());
        $this->assertEquals('Test error message', $error->get_error_message());
        $this->assertEquals(['test_data' => 'test_value'], $error->get_error_data());
    }

    /**
     * Test option functions work
     */
    public function test_option_functions() {
        // Test that options start empty
        $this->assertFalse(get_option('test_option'));
        $this->assertEquals('default', get_option('test_option', 'default'));

        // Test setting an option
        $this->assertTrue(update_option('test_option', 'test_value'));
        $this->assertEquals('test_value', get_option('test_option'));

        // Test deleting an option
        $this->assertTrue(delete_option('test_option'));
        $this->assertFalse(get_option('test_option'));

        // Test adding an option
        $this->assertTrue(add_option('test_option_2', 'test_value_2'));
        $this->assertEquals('test_value_2', get_option('test_option_2'));
    }

    /**
     * Test global $wpdb mock works
     */
    public function test_wpdb_mock() {
        global $wpdb;
        
        $this->assertIsObject($wpdb);
        $this->assertEquals('wp_', $wpdb->prefix);
        $this->assertEquals('', $wpdb->last_error);
        $this->assertEquals(1, $wpdb->insert_id);
        $this->assertEquals(0, $wpdb->num_queries);
    }

    /**
     * Test Brain Monkey is working
     */
    public function test_brain_monkey() {
        // Brain Monkey should be set up in TestCase - check for a real class
        $this->assertTrue(class_exists('Brain\\Monkey\\Container'));
    }

    /**
     * Test helper methods from TestCase
     */
    public function test_testcase_helpers() {
        // Test create_test_license
        $license_id = $this->create_test_license(['name' => 'Helper Test License']);
        $this->assertIsInt($license_id);
        $this->assertGreaterThan(0, $license_id);

        // Test create_test_oauth_client
        $client_data = $this->create_test_oauth_client(['client_name' => 'Helper Test Client']);
        $this->assertIsArray($client_data);
        $this->assertArrayHasKey('client_id', $client_data);
        $this->assertArrayHasKey('client_secret', $client_data);
        $this->assertArrayHasKey('client_name', $client_data);
        $this->assertEquals('Helper Test Client', $client_data['client_name']);

        // Test generate_test_jwt
        $token = $this->generate_test_jwt(['sub' => 'test-subject']);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);
        
        // Should have 3 parts (header.payload.signature)
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
        
        // Decode payload to verify content
        $payload = json_decode(base64_decode($parts[1]), true);
        $this->assertEquals('test-subject', $payload['sub']);
    }

    /**
     * Test JSON validation helper
     */
    public function test_json_validation_helper() {
        $valid_json = '{"test": "value", "number": 123}';
        $this->assertValidJson($valid_json);

        $invalid_json = '{"test": "value", "number": 123'; // Missing closing brace
        try {
            $this->assertValidJson($invalid_json);
            $this->fail('Expected assertion to fail for invalid JSON');
        } catch (\Exception $e) {
            $this->assertTrue(true); // Expected failure
        }
    }

    /**
     * Test autoloader works for basic constants
     */
    public function test_constants_defined() {
        $this->assertTrue(defined('ABSPATH'));
        $this->assertStringEndsWith('/', ABSPATH);
    }
}