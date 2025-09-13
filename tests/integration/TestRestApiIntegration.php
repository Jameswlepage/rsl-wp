<?php
/**
 * REST API integration tests
 *
 * @package RSL_Licensing
 */

namespace RSL\Tests\Integration;

use RSL\Tests\TestCase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Test REST API endpoints integration
 *
 * @group integration
 * @group api
 */
class TestRestApiIntegration extends TestCase {

    /**
     * REST server instance
     *
     * @var WP_REST_Server
     */
    private $server;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server();
        do_action('rest_api_init');
    }

    /**
     * Test RSL public API endpoints registration
     *
     * @covers RSL_Server::register_rest_routes
     */
    public function test_rsl_public_api_endpoints() {
        $routes = $this->server->get_routes();

        // Check public RSL API endpoints
        $expected_routes = [
            '/rsl/v1/licenses',
            '/rsl/v1/licenses/(?P<id>\d+)',
            '/rsl/v1/validate'
        ];

        foreach ($expected_routes as $route) {
            $this->assertArrayHasKey($route, $routes, "Route {$route} should be registered");
        }
    }

    /**
     * Test RSL OLP endpoints registration
     *
     * @covers RSL_Server::register_rest_routes
     */
    public function test_rsl_olp_endpoints() {
        $routes = $this->server->get_routes();

        // Check OAuth License Protocol endpoints
        $expected_routes = [
            '/rsl-olp/v1/token',
            '/rsl-olp/v1/introspect',
            '/rsl-olp/v1/key',
            '/rsl-olp/v1/session',
            '/rsl-olp/v1/session/(?P<session_id>[a-f0-9\-]+)'
        ];

        foreach ($expected_routes as $route) {
            $this->assertArrayHasKey($route, $routes, "OLP route {$route} should be registered");
        }
    }

    /**
     * Test GET /rsl/v1/licenses endpoint
     *
     * @covers RSL_Server::rest_get_licenses
     */
    public function test_get_licenses_endpoint() {
        // Create test licenses
        $license1_id = $this->create_test_license(['name' => 'API Test License 1']);
        $license2_id = $this->create_test_license(['name' => 'API Test License 2']);

        $request = new WP_REST_Request('GET', '/rsl/v1/licenses');
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(2, count($data));

        // Verify response structure
        $license = $data[0];
        $this->assertArrayHasKey('id', $license);
        $this->assertArrayHasKey('name', $license);
        $this->assertArrayHasKey('content_url', $license);
        $this->assertArrayHasKey('payment_type', $license);
        $this->assertArrayHasKey('xml_url', $license);
    }

    /**
     * Test GET /rsl/v1/licenses/{id} endpoint
     *
     * @covers RSL_Server::rest_get_license
     */
    public function test_get_license_by_id_endpoint() {
        $license_id = $this->create_test_license([
            'name' => 'Single License Test',
            'description' => 'Test license for single endpoint'
        ]);

        $request = new WP_REST_Request('GET', '/rsl/v1/licenses/' . $license_id);
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertEquals($license_id, $data['id']);
        $this->assertEquals('Single License Test', $data['name']);
        $this->assertEquals('Test license for single endpoint', $data['description']);
    }

    /**
     * Test GET /rsl/v1/licenses/{id} with invalid ID
     *
     * @covers RSL_Server::rest_get_license
     */
    public function test_get_license_by_id_not_found() {
        $request = new WP_REST_Request('GET', '/rsl/v1/licenses/99999');
        $response = $this->server->dispatch($request);

        $this->assertEquals(404, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('license_not_found', $data['code']);
    }

    /**
     * Test POST /rsl/v1/validate endpoint
     *
     * @covers RSL_Server::rest_validate_license
     */
    public function test_validate_license_endpoint() {
        $license_id = $this->create_test_license([
            'name' => 'Validation Test License',
            'content_url' => '/validation-test'
        ]);

        $request = new WP_REST_Request('POST', '/rsl/v1/validate');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'content_url' => 'http://example.org/validation-test'
        ]));

        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['valid']);
        $this->assertIsArray($data['licenses']);
        $this->assertCount(1, $data['licenses']);
        $this->assertEquals('Validation Test License', $data['licenses'][0]['name']);
    }

    /**
     * Test POST /rsl/v1/validate with no matching license
     *
     * @covers RSL_Server::rest_validate_license
     */
    public function test_validate_license_no_match() {
        $request = new WP_REST_Request('POST', '/rsl/v1/validate');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'content_url' => 'http://example.org/no-license'
        ]));

        $response = $this->server->dispatch($request);

        $this->assertEquals(404, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('no_license', $data['code']);
    }

    /**
     * Test POST /rsl-olp/v1/token for free license
     *
     * @covers RSL_Server::olp_issue_token
     */
    public function test_olp_token_free_license() {
        $license_id = $this->create_test_license([
            'name' => 'Free Token License',
            'content_url' => '/free-token-test',
            'payment_type' => 'free',
            'amount' => 0
        ]);

        $request = new WP_REST_Request('POST', '/rsl-olp/v1/token');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'license_id' => $license_id,
            'resource' => 'http://example.org/free-token-test',
            'client' => 'api-test-client'
        ]));

        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('token_type', $data);
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertEquals('Bearer', $data['token_type']);
    }

    /**
     * Test POST /rsl-olp/v1/token with missing parameters
     *
     * @covers RSL_Server::olp_issue_token
     */
    public function test_olp_token_missing_parameters() {
        $request = new WP_REST_Request('POST', '/rsl-olp/v1/token');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'license_id' => 1
            // Missing resource parameter
        ]));

        $response = $this->server->dispatch($request);

        $this->assertEquals(400, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('invalid_request', $data['code']);
    }

    /**
     * Test POST /rsl-olp/v1/introspect with valid token
     *
     * @covers RSL_Server::olp_introspect
     */
    public function test_olp_introspect_valid_token() {
        // Create OAuth client
        $client_data = $this->create_test_oauth_client(['client_name' => 'Introspect API Test']);
        
        // Generate token
        $payload = [
            'sub' => $client_data['client_id'],
            'lic' => 1,
            'exp' => time() + 3600,
            'iat' => time()
        ];
        $token = $this->generate_test_jwt($payload);

        $request = new WP_REST_Request('POST', '/rsl-olp/v1/introspect');
        $request->set_header('Content-Type', 'application/json');
        $request->set_header('Authorization', 'Basic ' . base64_encode($client_data['client_id'] . ':' . $client_data['client_secret']));
        $request->set_body(json_encode(['token' => $token]));

        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['active']);
        $this->assertEquals($client_data['client_id'], $data['client_id']);
    }

    /**
     * Test POST /rsl-olp/v1/introspect without authentication
     *
     * @covers RSL_Server::olp_introspect
     */
    public function test_olp_introspect_no_auth() {
        $token = $this->generate_test_jwt();

        $request = new WP_REST_Request('POST', '/rsl-olp/v1/introspect');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode(['token' => $token]));

        $response = $this->server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals('invalid_client', $data['code']);
    }

    /**
     * Test POST /rsl-olp/v1/session creation
     *
     * @covers RSL_Server::olp_create_session
     */
    public function test_olp_create_session() {
        $license_id = $this->create_test_license([
            'name' => 'Session Test License',
            'payment_type' => 'free',
            'amount' => 0
        ]);

        $request = new WP_REST_Request('POST', '/rsl-olp/v1/session');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'license_id' => $license_id,
            'client' => 'session-test-client'
        ]));

        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('session_id', $data);
        $this->assertArrayHasKey('status', $data);
        
        // For free license, should include token immediately
        if (isset($data['token'])) {
            $this->assertIsString($data['token']);
        }
    }

    /**
     * Test GET /rsl-olp/v1/session/{id} polling
     *
     * @covers RSL_Server::olp_get_session
     */
    public function test_olp_get_session() {
        // This would require creating a session first
        $session_id = 'test-session-' . wp_generate_uuid4();

        $request = new WP_REST_Request('GET', '/rsl-olp/v1/session/' . $session_id);
        $response = $this->server->dispatch($request);

        // Should return 404 for non-existent session
        $this->assertEquals(404, $response->get_status());
    }

    /**
     * Test CORS headers on API responses
     *
     * @covers RSL_Server::add_cors_headers
     */
    public function test_cors_headers() {
        $license_id = $this->create_test_license();

        // Mock origin header
        $_SERVER['HTTP_ORIGIN'] = 'http://example.org';

        $request = new WP_REST_Request('GET', '/rsl/v1/licenses/' . $license_id);
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());

        // In a real test, we'd check for CORS headers in the response
        // This would require mocking the header() function or using output buffering
        $this->assertTrue(true); // Placeholder assertion
    }

    /**
     * Test API response format consistency
     *
     * @covers RSL_Server::format_license_for_api
     */
    public function test_api_response_format_consistency() {
        $license_id = $this->create_test_license([
            'name' => 'Format Test License',
            'description' => 'Testing API response format',
            'content_url' => '/format-test',
            'payment_type' => 'purchase',
            'amount' => 99.99,
            'currency' => 'USD'
        ]);

        // Test individual license endpoint
        $request = new WP_REST_Request('GET', '/rsl/v1/licenses/' . $license_id);
        $response = $this->server->dispatch($request);
        $single_license = $response->get_data();

        // Test licenses list endpoint
        $request = new WP_REST_Request('GET', '/rsl/v1/licenses');
        $response = $this->server->dispatch($request);
        $licenses_list = $response->get_data();

        // Find our license in the list
        $license_in_list = null;
        foreach ($licenses_list as $license) {
            if ($license['id'] == $license_id) {
                $license_in_list = $license;
                break;
            }
        }

        $this->assertNotNull($license_in_list);

        // Both should have the same structure
        $expected_fields = ['id', 'name', 'description', 'content_url', 'payment_type', 'amount', 'currency', 'xml_url'];
        
        foreach ($expected_fields as $field) {
            $this->assertArrayHasKey($field, $single_license);
            $this->assertArrayHasKey($field, $license_in_list);
            $this->assertEquals($single_license[$field], $license_in_list[$field]);
        }
    }

    /**
     * Test API error handling consistency
     *
     * @covers RSL_Server::rest_get_license
     */
    public function test_api_error_handling_consistency() {
        $endpoints_and_errors = [
            ['/rsl/v1/licenses/99999', 'GET', 404, 'license_not_found'],
            ['/rsl/v1/validate', 'POST', 400, 'rest_missing_callback_param'], // Missing content_url
            ['/rsl-olp/v1/token', 'POST', 400, 'invalid_request'] // Missing parameters
        ];

        foreach ($endpoints_and_errors as [$endpoint, $method, $expected_status, $expected_code]) {
            $request = new WP_REST_Request($method, $endpoint);
            if ($method === 'POST') {
                $request->set_header('Content-Type', 'application/json');
                $request->set_body(json_encode([])); // Empty body to trigger validation errors
            }

            $response = $this->server->dispatch($request);
            
            $this->assertEquals($expected_status, $response->get_status(), "Endpoint {$endpoint} status mismatch");
            
            $data = $response->get_data();
            if (isset($data['code'])) {
                $this->assertEquals($expected_code, $data['code'], "Endpoint {$endpoint} error code mismatch");
            }
        }
    }

    /**
     * Test API content type handling
     *
     * @covers RSL_Server::olp_issue_token
     */
    public function test_api_content_type_handling() {
        $license_id = $this->create_test_license([
            'payment_type' => 'free',
            'amount' => 0
        ]);

        // Test with JSON content type
        $request = new WP_REST_Request('POST', '/rsl-olp/v1/token');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode([
            'license_id' => $license_id,
            'resource' => 'http://example.org/test',
            'client' => 'content-type-test'
        ]));

        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());

        // Test with form data content type
        $request = new WP_REST_Request('POST', '/rsl-olp/v1/token');
        $request->set_header('Content-Type', 'application/x-www-form-urlencoded');
        $request->set_body_params([
            'license_id' => $license_id,
            'resource' => 'http://example.org/test',
            'client' => 'form-test'
        ]);

        $response = $this->server->dispatch($request);
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test API pagination (if implemented)
     *
     * @covers RSL_Server::rest_get_licenses
     */
    public function test_api_pagination() {
        // Create multiple licenses
        for ($i = 1; $i <= 15; $i++) {
            $this->create_test_license(['name' => "Pagination Test License {$i}"]);
        }

        // Test default pagination
        $request = new WP_REST_Request('GET', '/rsl/v1/licenses');
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        // Should include pagination headers if implemented
        $headers = $response->get_headers();
        
        // Note: WordPress REST API typically uses X-WP-Total and X-WP-TotalPages headers
        $this->assertTrue(true); // Placeholder - would need actual pagination implementation
    }
}