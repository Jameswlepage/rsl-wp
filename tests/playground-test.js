#!/usr/bin/env node

/**
 * RSL Server Test Suite using WordPress Playground CLI
 * 
 * This test suite uses the programmatic API to spin up a WordPress instance,
 * activate the RSL plugin, and test all functionality automatically.
 */

import { runCLI } from '@wp-playground/cli';

async function runRSLTests() {
    console.log('🧪 RSL Server Test Suite (WordPress Playground)');
    console.log('================================================');
    
    let testsTotal = 0;
    let testsPassed = 0;
    
    // Helper function for tests
    const test = (name, expected, actual) => {
        testsTotal++;
        const passed = actual.includes(expected);
        console.log(`Testing: ${name}... ${passed ? '✅ PASS' : '❌ FAIL'}`);
        if (passed) testsPassed++;
        if (!passed) {
            console.log(`  Expected: ${expected}`);
            console.log(`  Got: ${actual.substring(0, 200)}...`);
        }
    };
    
    try {
        console.log('🚀 Starting WordPress Playground...');
        
        // Start WordPress with RSL plugin auto-mounted
        const playground = await runCLI({
            command: 'server',
            php: '8.3',
            wp: 'latest',
            port: 0, // Auto-assign port
            autoMount: true,
            login: true
        });
        
        const siteUrl = playground.url;
        console.log(`✅ WordPress started at: ${siteUrl}`);
        console.log('');
        
        // Test helper function
        const fetch = async (path, options = {}) => {
            const url = `${siteUrl}${path}`;
            const response = await playground.request(url, options);
            return await response.text();
        };
        
        console.log('=== Core API Tests ===');
        
        // Test 1: License API
        const licensesResponse = await fetch('/wp-json/rsl/v1/licenses');
        test('License API endpoint', '"id":"1"', licensesResponse);
        
        // Test 2: Individual license
        const licenseResponse = await fetch('/wp-json/rsl/v1/licenses/1');
        test('Individual license API', '"name":"Default Site License"', licenseResponse);
        
        console.log('=== RSL Server Token Tests ===');
        
        // Test 3: Token generation
        console.log('Generating test token...');
        const tokenResponse = await fetch('/wp-json/rsl-olp/v1/token', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ license_id: 1, client: 'playground-test' })
        });
        
        let token = '';
        try {
            const tokenData = JSON.parse(tokenResponse);
            token = tokenData.token || '';
            if (token) {
                console.log('✅ Token generated successfully');
                testsTotal++; testsPassed++;
            } else {
                console.log('❌ Token generation failed');
                testsTotal++;
            }
        } catch (e) {
            console.log('❌ Token generation failed (parse error)');
            testsTotal++;
        }
        
        // Test 4: Token validation
        if (token) {
            const introspectResponse = await fetch('/wp-json/rsl-olp/v1/introspect', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token })
            });
            test('Token validation', '"active":true', introspectResponse);
        }
        
        // Test 5: Invalid token
        const invalidTokenResponse = await fetch('/wp-json/rsl-olp/v1/introspect', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: 'invalid.token.here' })
        });
        test('Invalid token rejection', '"code":"invalid_token"', invalidTokenResponse);
        
        console.log('=== Integration Tests ===');
        
        // Test 6: robots.txt
        const robotsResponse = await fetch('/robots.txt');
        test('robots.txt License directive', 'License:', robotsResponse);
        test('robots.txt AI Preferences', 'Content-Usage:', robotsResponse);
        
        // Test 7: RSL feed
        const feedResponse = await fetch('/?rsl_feed=1');
        test('RSL license feed', 'xmlns:rsl="https://rslstandard.org/rsl"', feedResponse);
        
        // Test 8: HTML injection
        const homeResponse = await fetch('/');
        test('HTML head injection', 'type="application/rsl+xml"', homeResponse);
        
        // Test 9: License XML
        const xmlResponse = await fetch('/?rsl_license=1');
        test('License XML generation', '<rsl xmlns="https://rslstandard.org/rsl">', xmlResponse);
        
        // Test 10: Post RSL injection
        const postResponse = await fetch('/2025/09/12/hello-world/');
        test('Post RSL injection', 'type="application/rsl+xml"', postResponse);
        
        console.log('========================');
        console.log(`Test Results: ${testsPassed}/${testsTotal} passed`);
        
        if (testsPassed === testsTotal) {
            console.log('🎉 All tests passed! RSL server is working correctly.');
            console.log('');
            console.log('📊 Test Summary:');
            console.log('   - JWT token generation and validation: ✅');
            console.log('   - RSL XML output (HTML, robots.txt, RSS): ✅');
            console.log('   - API endpoints and error handling: ✅');
            console.log('   - WordPress Playground integration: ✅');
        } else {
            console.log(`⚠️  ${testsTotal - testsPassed} tests failed.`);
            process.exit(1);
        }
        
    } catch (error) {
        console.error('💥 Test suite failed:', error.message);
        process.exit(1);
    }
}

// Run tests
runRSLTests();