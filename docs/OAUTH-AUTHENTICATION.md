# OAuth 2.0 Client Credentials Guide

RSL Licensing implements **OAuth 2.0 Client Credentials Grant only** - the simplest OAuth flow designed for API authentication. This is **not a full OAuth 2.0 authorization server** but rather a focused implementation for machine-to-machine authentication.

## What This Is (and Isn't)

### ✅ What RSL Implements
- **OAuth 2.0 Client Credentials Grant** (RFC 6749 Section 4.4)
- **Token Introspection** (RFC 7662) 
- **JWT tokens** with standard claims
- **Basic authentication** for client credentials
- **Standard error responses**

### ❌ What RSL Does NOT Implement
- Authorization Code Grant (user login flows)
- Implicit Grant (browser-based flows) 
- Resource Owner Password Grant
- Refresh tokens
- OAuth scopes (beyond license permissions)
- User consent screens
- Authorization endpoints

### Why This Approach?

RSL focuses on **content licensing**, not user authentication. The Client Credentials Grant is perfect because:
- **Machine-to-machine**: AI companies authenticate directly, no user interaction
- **Simple**: Just API keys with OAuth-standard formatting
- **Secure**: Industry-standard credential validation
- **Focused**: No unnecessary OAuth complexity

## Overview

- **Free licenses**: No authentication required - maximum accessibility
- **Paid licenses**: Require client credentials (glorified API keys)
- **Standard compliance**: Follows relevant parts of RFC 6749 and RFC 7662

## Client Registration

### WordPress Admin Interface

1. **Navigate to RSL Licensing**
   - Go to WordPress Admin → RSL Licensing → OAuth Clients
   - Click "Add New Client"

2. **Create Client**
   - Enter a descriptive name (e.g., "OpenAI Crawler", "Anthropic Claude")
   - Click "Create Client"
   - **Copy the credentials immediately** - the secret is shown only once

3. **Provide to AI Company**
   - Securely transmit the `client_id` and `client_secret`
   - Recommend secure channels (encrypted email, password managers, etc.)

### Programmatic Registration

If you have many clients to register, you can use the WordPress database directly:

```php
// Example: Register client programmatically
$oauth_client = RSL_OAuth_Client::get_instance();
$client = $oauth_client->create_client('My AI Company', [
    'grant_types' => 'client_credentials',
    'redirect_uris' => [] // Not used for client credentials
]);

// Store these securely
$client_id = $client['client_id'];
$client_secret = $client['client_secret']; // Only available during creation
```

## Authentication Flow

RSL uses **only the Client Credentials Grant** - essentially **API keys with OAuth formatting**:

```
AI Company                                  RSL License Server
     |                                              |
     |-- Basic Auth (client_id:client_secret) ---->|
     |<-- JWT Access Token or Error ---------------|
     |                                              |
     |-- Use Token: "Authorization: Bearer ..." -->|
     |<-- Licensed Content -------------------------|
```

**This is much simpler than full OAuth** - no user redirects, consent screens, or authorization codes. Just direct API authentication.

### Step 1: Token Request

**Endpoint**: `POST /wp-json/rsl-olp/v1/token`

**Headers**:
```
Authorization: Basic <base64(client_id:client_secret)>
Content-Type: application/json
```

**Body**:
```json
{
  "license_id": 123,
  "resource": "https://example.com/content",
  "client": "my-company-identifier"
}
```

**Parameters**:
- `license_id` (required): ID of the license to purchase/access
- `resource` (required): The exact URL you want to license
- `client` (optional): Human-readable client identifier

### Step 2: Handle Response

**Success Response** (Free License or Payment Complete):
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "expires_at": "2024-01-01T12:00:00Z",
  "license_url": "https://example.com/rsl-license/123/"
}
```

**Payment Required Response**:
```json
{
  "checkout_url": "https://example.com/checkout/?add-to-cart=456"
}
```

**Error Response**:
```json
{
  "error": "invalid_client",
  "error_description": "Client authentication failed"
}
```

### Step 3: Use Access Token

Include the token in requests to licensed content:

```bash
curl -H "Authorization: Bearer eyJ0eXAiOiJKV1Q..." \
     https://example.com/licensed-content
```

## Token Introspection

Validate tokens using the introspection endpoint per RFC 7662.

**Endpoint**: `POST /wp-json/rsl-olp/v1/introspect`

**Request**:
```bash
curl -X POST https://example.com/wp-json/rsl-olp/v1/introspect \
  -H "Authorization: Basic $(echo -n 'client_id:client_secret' | base64)" \
  -H "Content-Type: application/json" \
  -d '{"token": "eyJ0eXAiOiJKV1Q..."}'
```

**Active Token Response**:
```json
{
  "active": true,
  "client_id": "rsl_abc123",
  "username": "openai-crawler", 
  "exp": 1672617600,
  "iat": 1672531200,
  "nbf": 1672531200,
  "aud": "example.com",
  "iss": "https://example.com",
  "jti": "550e8400-e29b-41d4-a716-446655440000",
  "license_id": 123,
  "scope": "train-ai"
}
```

**Inactive Token Response**:
```json
{
  "active": false
}
```

## Token Structure

RSL tokens are JSON Web Tokens (JWT) with the following claims:

```json
{
  "iss": "https://example.com",           // Issuer
  "aud": "example.com",                   // Audience (site hostname)
  "sub": "openai-crawler",                // Subject (client identifier)
  "jti": "550e8400-e29b-41d4-a716-446655440000", // JWT ID (for revocation)
  "iat": 1672531200,                      // Issued at
  "nbf": 1672531200,                      // Not before  
  "exp": 1672617600,                      // Expires at
  "lic": 123,                            // License ID
  "scope": "train-ai",                    // Permitted usage
  "pattern": "https://example.com/*"      // URL pattern coverage
}
```

## Token Revocation

Tokens are automatically revoked when:

- **WooCommerce order refunded** → All associated tokens revoked
- **WooCommerce order cancelled** → All associated tokens revoked
- **WooCommerce subscription cancelled** → All subscription tokens revoked  
- **WooCommerce subscription expired** → All subscription tokens revoked

Revoked tokens return `"active": false` from the introspection endpoint.

## Rate Limiting

API endpoints are rate limited to prevent abuse:

| Endpoint | Rate Limit | Scope |
|----------|------------|-------|
| `/token` | 30 requests/minute | Per client |
| `/introspect` | 100 requests/minute | Per client |
| `/session` | 20 requests/minute | Per client |

**Rate limit headers** are included in responses:
```
X-RateLimit-Limit: 30
X-RateLimit-Remaining: 25  
X-RateLimit-Reset: 1672531260
```

**Rate limit exceeded response**:
```json
{
  "code": "rate_limit_exceeded",
  "message": "Rate limit exceeded. Maximum 30 requests per minute allowed.",
  "data": {
    "status": 429,
    "headers": {
      "Retry-After": "60"
    }
  }
}
```

## Error Handling

RSL returns standard OAuth 2.0 errors:

| Error Code | Description | Common Causes |
|------------|-------------|---------------|
| `invalid_request` | Malformed request | Missing required parameters |
| `invalid_client` | Client authentication failed | Wrong credentials, inactive client |
| `invalid_license` | License invalid or unavailable | License not found, inactive |
| `invalid_resource` | Resource not covered by license | URL doesn't match license pattern |
| `unsupported_grant_type` | Grant type not supported | Only client_credentials supported |
| `server_error` | Internal server error | Check server logs |

## Security Best Practices

### For Site Owners

1. **Secure JWT Secret Storage**
   ```php
   // In wp-config.php
   define('RSL_JWT_SECRET', 'your-long-secure-random-string');
   ```

2. **HTTPS Only**
   - Never transmit credentials over HTTP
   - Redirect all licensing endpoints to HTTPS

3. **Regular Credential Rotation**
   - Rotate client secrets periodically
   - Especially for high-value licenses

4. **Monitor Rate Limits**
   - Watch for repeated violations
   - May indicate abuse or misconfigured clients

5. **CORS Configuration**
   ```php
   // Restrict CORS origins
   add_filter('rsl_cors_allowed_origins', function($origins) {
       return [
           'https://yoursite.com',
           'https://trusted-partner.com'
       ];
   });
   ```

### For AI Companies

1. **Secure Credential Storage**
   - Use environment variables or secure vaults
   - Never commit credentials to version control

2. **Implement Exponential Backoff**
   ```javascript
   async function requestWithBackoff(url, options, maxRetries = 3) {
       for (let i = 0; i < maxRetries; i++) {
           try {
               const response = await fetch(url, options);
               if (response.status === 429) {
                   const retryAfter = response.headers.get('Retry-After');
                   await sleep((retryAfter || Math.pow(2, i)) * 1000);
                   continue;
               }
               return response;
           } catch (error) {
               if (i === maxRetries - 1) throw error;
               await sleep(Math.pow(2, i) * 1000);
           }
       }
   }
   ```

3. **Token Caching**
   - Cache tokens until expiration
   - Don't request new tokens unnecessarily

4. **Graceful Error Handling**
   ```javascript
   try {
       const token = await getAccessToken();
       // Use token...
   } catch (error) {
       if (error.code === 'rate_limit_exceeded') {
           // Wait and retry
       } else if (error.code === 'invalid_client') {
           // Check credentials
       }
   }
   ```

## Testing Your Integration

### Test OAuth Flow

1. **Create test client** in WordPress admin
2. **Encode credentials**:
   ```bash
   echo -n 'client_id:client_secret' | base64
   ```

3. **Test token request**:
   ```bash
   curl -X POST https://yoursite.com/wp-json/rsl-olp/v1/token \
     -H "Authorization: Basic <encoded_credentials>" \
     -H "Content-Type: application/json" \
     -d '{"license_id": 1, "resource": "https://yoursite.com/"}'
   ```

4. **Test token introspection**:
   ```bash
   curl -X POST https://yoursite.com/wp-json/rsl-olp/v1/introspect \
     -H "Authorization: Basic <encoded_credentials>" \
     -d '{"token": "<received_token>"}'
   ```

### Common Test Scenarios

1. **Free License** - Should return token immediately
2. **Paid License (unpaid)** - Should return checkout URL  
3. **Paid License (paid)** - Should return access token
4. **Invalid Resource** - Should return `invalid_resource` error
5. **Rate Limiting** - Make 31 requests to trigger rate limit

## Monitoring & Analytics

### WordPress Admin Dashboard

The RSL admin interface provides:
- **Active OAuth clients** list
- **Token usage statistics** 
- **Rate limit violation logs**
- **Revenue from licensing** (WooCommerce integration)

### Server Logs

Enable debug logging in wp-config.php:
```php
define('RSL_DEBUG', true);
```

This logs:
- Authentication attempts
- Token issuance/revocation
- Rate limit violations  
- Payment completions

### Webhook Integration

RSL triggers WordPress actions you can hook into:

```php
// Monitor successful authentications
add_action('rsl_oauth_client_authenticated', function($client_id) {
    // Log successful auth
});

// Monitor token revocations  
add_action('rsl_token_revoked', function($jti, $reason) {
    // Log revocation reason (refund, cancellation, etc.)
});

// Monitor rate limit violations
add_action('rsl_rate_limit_exceeded', function($endpoint, $client_id) {
    // Alert on repeated violations
});
```

---

**For additional support, see the main [README.md](../README.md) or visit [RSL Standard Documentation](https://rslstandard.org/docs/).**