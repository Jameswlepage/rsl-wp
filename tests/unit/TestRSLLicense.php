<?php
/**
 * Tests for RSL_License class
 *
 * @package RSL_Licensing
 */

namespace RSL\Tests\Unit;

use RSL\Tests\TestCase;
use RSL_License;

/**
 * Test RSL_License functionality
 *
 * @group unit
 * @group license
 */
class TestRSLLicense extends TestCase {

    /**
     * License handler instance
     *
     * @var RSL_License
     */
    private $license_handler;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        $this->license_handler = new RSL_License();
        
        // Reset mock license storage
        RSL_License::reset();
    }

    /**
     * Test license creation with valid data
     *
     * @covers RSL_License::create_license
     */
    public function test_create_license_with_valid_data() {
        $license_data = [
            'name' => 'Test License',
            'description' => 'Test description',
            'content_url' => '/test-content',
            'payment_type' => 'free',
            'amount' => 0,
            'currency' => 'USD',
            'permits_usage' => 'search',
            'prohibits_usage' => 'train-ai'
        ];

        $license_id = $this->license_handler->create_license($license_data);

        $this->assertIsInt($license_id);
        $this->assertGreaterThan(0, $license_id);
    }

    /**
     * Test license creation with missing required fields
     *
     * @covers RSL_License::create_license
     */
    public function test_create_license_with_missing_required_fields() {
        // Missing name
        $license_data = [
            'description' => 'Test description',
            'content_url' => '/test-content'
        ];

        $result = $this->license_handler->create_license($license_data);
        $this->assertFalse($result);

        // Missing content_url
        $license_data = [
            'name' => 'Test License',
            'description' => 'Test description'
        ];

        $result = $this->license_handler->create_license($license_data);
        $this->assertFalse($result);
    }

    /**
     * Test license creation with defaults
     *
     * @covers RSL_License::create_license
     */
    public function test_create_license_with_defaults() {
        $license_data = [
            'name' => 'Minimal License',
            'content_url' => '/minimal'
        ];

        $license_id = $this->license_handler->create_license($license_data);
        $license = $this->license_handler->get_license($license_id);

        $this->assertEquals('free', $license['payment_type']);
        $this->assertEquals(0, $license['amount']);
        $this->assertEquals('USD', $license['currency']);
        $this->assertEquals(1, $license['active']);
        $this->assertEquals(0, $license['encrypted']);
    }

    /**
     * Test getting license by ID
     *
     * @covers RSL_License::get_license
     */
    public function test_get_license_by_id() {
        $license_id = $this->create_test_license([
            'name' => 'Test Get License',
            'content_url' => '/get-test'
        ]);

        $license = $this->license_handler->get_license($license_id);

        $this->assertIsArray($license);
        $this->assertEquals('Test Get License', $license['name']);
        $this->assertEquals('/get-test', $license['content_url']);
        $this->assertEquals($license_id, $license['id']);
    }

    /**
     * Test getting non-existent license
     *
     * @covers RSL_License::get_license
     */
    public function test_get_nonexistent_license() {
        $license = $this->license_handler->get_license(99999);
        $this->assertNull($license);
    }

    /**
     * Test getting license with invalid ID
     *
     * @covers RSL_License::get_license
     */
    public function test_get_license_with_invalid_id() {
        $license = $this->license_handler->get_license(0);
        $this->assertNull($license);

        $license = $this->license_handler->get_license(-1);
        $this->assertNull($license);

        $license = $this->license_handler->get_license('invalid');
        $this->assertNull($license);
    }

    /**
     * Test getting all licenses
     *
     * @covers RSL_License::get_licenses
     */
    public function test_get_licenses() {
        // Create multiple test licenses
        $license1_id = $this->create_test_license(['name' => 'License 1']);
        $license2_id = $this->create_test_license(['name' => 'License 2']);
        $license3_id = $this->create_test_license([
            'name' => 'Inactive License',
            'active' => 0
        ]);

        // Get all licenses
        $licenses = $this->license_handler->get_licenses();
        $this->assertIsArray($licenses);
        $this->assertCount(3, $licenses); // Including default license from setup

        // Get only active licenses
        $active_licenses = $this->license_handler->get_licenses(['active' => 1]);
        $this->assertCount(2, $active_licenses); // Including default license
    }

    /**
     * Test license update
     *
     * @covers RSL_License::update_license
     */
    public function test_update_license() {
        $license_id = $this->create_test_license([
            'name' => 'Original Name',
            'description' => 'Original description'
        ]);

        $update_data = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'amount' => 99.99
        ];

        $result = $this->license_handler->update_license($license_id, $update_data);
        $this->assertTrue($result);

        $updated_license = $this->license_handler->get_license($license_id);
        $this->assertEquals('Updated Name', $updated_license['name']);
        $this->assertEquals('Updated description', $updated_license['description']);
        $this->assertEquals(99.99, $updated_license['amount']);
    }

    /**
     * Test license deletion
     *
     * @covers RSL_License::delete_license
     */
    public function test_delete_license() {
        $license_id = $this->create_test_license(['name' => 'To Be Deleted']);

        $result = $this->license_handler->delete_license($license_id);
        $this->assertTrue($result);

        $license = $this->license_handler->get_license($license_id);
        $this->assertNull($license);
    }

    /**
     * Test RSL XML generation
     *
     * @covers RSL_License::generate_rsl_xml
     */
    public function test_generate_rsl_xml() {
        $license_data = [
            'id' => 1,
            'name' => 'XML Test License',
            'content_url' => '/xml-test',
            'payment_type' => 'free',
            'permits_usage' => 'search,ai-summarize',
            'prohibits_usage' => 'train-ai,train-genai',
            'permits_user' => 'non-commercial',
            'prohibits_user' => 'commercial',
            'warranty' => 'ownership',
            'disclaimer' => 'as-is',
            'copyright_holder' => 'Test Author',
            'copyright_type' => 'person',
            'contact_email' => 'test@example.com'
        ];

        $xml = $this->license_handler->generate_rsl_xml($license_data);

        $this->assertValidRslXml($xml);
        $this->assertStringContains('xmlns="https://rslstandard.org/rsl"', $xml);
        $this->assertStringContains('url="/xml-test"', $xml);
        $this->assertStringContains('type="free"', $xml);
        $this->assertStringContains('search,ai-summarize', $xml);
        $this->assertStringContains('train-ai,train-genai', $xml);
    }

    /**
     * Test RSL XML generation with paid license
     *
     * @covers RSL_License::generate_rsl_xml
     */
    public function test_generate_rsl_xml_paid_license() {
        $license_data = [
            'id' => 2,
            'name' => 'Paid XML Test',
            'content_url' => '/paid-content',
            'payment_type' => 'purchase',
            'amount' => 99.99,
            'currency' => 'USD',
            'server_url' => 'http://example.org/wp-json/rsl-olp/v1',
            'permits_usage' => 'train-ai',
            'permits_user' => 'commercial'
        ];

        $xml = $this->license_handler->generate_rsl_xml($license_data);

        $this->assertValidRslXml($xml);
        $this->assertStringContains('type="purchase"', $xml);
        $this->assertStringContains('99.99', $xml);
        $this->assertStringContains('USD', $xml);
        $this->assertStringContains('http://example.org/wp-json/rsl-olp/v1', $xml);
    }

    /**
     * Test RSL XML generation with subscription license
     *
     * @covers RSL_License::generate_rsl_xml
     */
    public function test_generate_rsl_xml_subscription() {
        $license_data = [
            'id' => 3,
            'name' => 'Subscription XML Test',
            'content_url' => '/subscription-content',
            'payment_type' => 'subscription',
            'amount' => 29.99,
            'currency' => 'USD',
            'server_url' => 'http://example.org/wp-json/rsl-olp/v1'
        ];

        $xml = $this->license_handler->generate_rsl_xml($license_data);

        $this->assertValidRslXml($xml);
        $this->assertStringContains('type="subscription"', $xml);
        $this->assertStringContains('29.99', $xml);
    }

    /**
     * Test RSL XML generation with attribution license
     *
     * @covers RSL_License::generate_rsl_xml
     */
    public function test_generate_rsl_xml_attribution() {
        $license_data = [
            'id' => 4,
            'name' => 'Attribution XML Test',
            'content_url' => '/attribution-content',
            'payment_type' => 'attribution',
            'standard_url' => 'https://creativecommons.org/licenses/by/4.0/'
        ];

        $xml = $this->license_handler->generate_rsl_xml($license_data);

        $this->assertValidRslXml($xml);
        $this->assertStringContains('type="attribution"', $xml);
        $this->assertStringContains('https://creativecommons.org/licenses/by/4.0/', $xml);
    }

    /**
     * Test license validation
     *
     * @covers RSL_License::validate_license_data
     */
    public function test_validate_license_data() {
        // Valid data
        $valid_data = [
            'name' => 'Valid License',
            'content_url' => '/valid',
            'payment_type' => 'free',
            'amount' => 0,
            'currency' => 'USD'
        ];

        $result = $this->license_handler->validate_license_data($valid_data);
        $this->assertTrue($result);

        // Invalid payment type
        $invalid_data = [
            'name' => 'Invalid License',
            'content_url' => '/invalid',
            'payment_type' => 'invalid_type'
        ];

        $result = $this->license_handler->validate_license_data($invalid_data);
        $this->assertInstanceOf('WP_Error', $result);

        // Invalid currency
        $invalid_currency = [
            'name' => 'Invalid Currency',
            'content_url' => '/invalid-currency',
            'currency' => 'INVALID'
        ];

        $result = $this->license_handler->validate_license_data($invalid_currency);
        $this->assertInstanceOf('WP_Error', $result);
    }

    /**
     * Test license data sanitization
     *
     * @covers RSL_License::sanitize_license_data
     */
    public function test_sanitize_license_data() {
        $dirty_data = [
            'name' => '<script>alert("xss")</script>Clean Name',
            'description' => 'Description with <b>HTML</b>',
            'content_url' => '/path/../../../etc/passwd',
            'amount' => '99.99abc',
            'permits_usage' => 'train-ai,<script>alert()</script>,search',
            'contact_email' => 'invalid-email@',
            'server_url' => 'javascript:alert("xss")'
        ];

        $clean_data = $this->license_handler->sanitize_license_data($dirty_data);

        $this->assertEquals('Clean Name', $clean_data['name']);
        $this->assertEquals('Description with HTML', $clean_data['description']);
        $this->assertStringNotContains('../', $clean_data['content_url']);
        $this->assertEquals(99.99, $clean_data['amount']);
        $this->assertStringNotContains('<script>', $clean_data['permits_usage']);
        $this->assertStringStartsWith('http', $clean_data['server_url']);
    }

    /**
     * Test getting license by content URL pattern matching
     *
     * @covers RSL_License::get_license_by_url
     */
    public function test_get_license_by_url() {
        // Create licenses with different URL patterns
        $exact_license = $this->create_test_license([
            'name' => 'Exact Match',
            'content_url' => '/exact-path'
        ]);

        $wildcard_license = $this->create_test_license([
            'name' => 'Wildcard Match',
            'content_url' => '/wildcard/*'
        ]);

        $root_license = $this->create_test_license([
            'name' => 'Root License',
            'content_url' => '/'
        ]);

        // Test exact match
        $license = $this->license_handler->get_license_by_url('/exact-path');
        $this->assertEquals('Exact Match', $license['name']);

        // Test wildcard match
        $license = $this->license_handler->get_license_by_url('/wildcard/subpath');
        $this->assertEquals('Wildcard Match', $license['name']);

        // Test root fallback
        $license = $this->license_handler->get_license_by_url('/unmatched-path');
        $this->assertEquals('Root License', $license['name']);
    }

    /**
     * Test license statistics
     *
     * @covers RSL_License::get_license_stats
     */
    public function test_get_license_stats() {
        // Create various license types
        $this->create_test_license(['payment_type' => 'free']);
        $this->create_test_license(['payment_type' => 'purchase', 'amount' => 99.99]);
        $this->create_test_license(['payment_type' => 'subscription', 'amount' => 29.99]);
        $this->create_test_license(['payment_type' => 'attribution']);
        $this->create_test_license(['active' => 0]); // Inactive license

        $stats = $this->license_handler->get_license_stats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('inactive', $stats);
        $this->assertArrayHasKey('by_type', $stats);

        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(4, $stats['active']);
        $this->assertEquals(1, $stats['inactive']);
        $this->assertIsArray($stats['by_type']);
    }

    /**
     * Test license export
     *
     * @covers RSL_License::export_license
     */
    public function test_export_license() {
        $license_id = $this->create_test_license([
            'name' => 'Export Test License',
            'description' => 'License for export testing'
        ]);

        $exported = $this->license_handler->export_license($license_id);

        $this->assertIsArray($exported);
        $this->assertEquals('Export Test License', $exported['name']);
        $this->assertEquals('License for export testing', $exported['description']);
        $this->assertArrayHasKey('rsl_xml', $exported);
        $this->assertValidRslXml($exported['rsl_xml']);
    }

    /**
     * Test license import
     *
     * @covers RSL_License::import_license
     */
    public function test_import_license() {
        $import_data = [
            'name' => 'Imported License',
            'description' => 'Imported from JSON',
            'content_url' => '/imported',
            'payment_type' => 'free',
            'permits_usage' => 'search',
            'prohibits_usage' => 'train-ai'
        ];

        $license_id = $this->license_handler->import_license($import_data);

        $this->assertIsInt($license_id);
        $this->assertGreaterThan(0, $license_id);

        $imported_license = $this->license_handler->get_license($license_id);
        $this->assertEquals('Imported License', $imported_license['name']);
        $this->assertEquals('/imported', $imported_license['content_url']);
    }
}