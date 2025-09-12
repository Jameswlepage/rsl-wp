# RSL Server Implementation Test Plan

## Quick Test Commands

### 1. Test Free License Flow
```bash
# Create a free license with server_url pointing to built-in server
# Then test token generation:

curl -X POST "http://yoursite.test/wp-json/rsl-olp/v1/token" \
  -H "Content-Type: application/json" \
  -d '{"license_id": 1, "client": "test-client"}'

# Expected response:
# {
#   "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
#   "expires_at": "2025-09-11T22:30:00+00:00", 
#   "license_url": "http://yoursite.test/rsl-license/1/"
# }
```

### 2. Test Token Validation
```bash
# Use token from step 1:
curl -X POST "http://yoursite.test/wp-json/rsl-olp/v1/introspect" \
  -H "Content-Type: application/json" \
  -d '{"token": "YOUR_TOKEN_HERE"}'

# Expected response:
# {
#   "active": true,
#   "payload": {
#     "iss": "http://yoursite.test",
#     "aud": "yoursite.test",
#     "sub": "test-client",
#     "lic": 1,
#     "scope": "all",
#     "pattern": "/",
#     "exp": 1726096200
#   }
# }
```

### 3. Test Crawler Authentication
```bash
# Request protected content as a crawler without token:
curl -A "GPTBot" "http://yoursite.test/protected-content"

# Expected: 401 with WWW-Authenticate header pointing to token endpoint

# Request with valid token:
curl -A "GPTBot" \
  -H "Authorization: License YOUR_TOKEN_HERE" \
  "http://yoursite.test/protected-content"

# Expected: 200 (content served)
```

### 4. Test WooCommerce Purchase Flow
```bash
# For paid license, request checkout URL:
curl -X POST "http://yoursite.test/wp-json/rsl-olp/v1/token" \
  -H "Content-Type: application/json" \
  -d '{"license_id": 2, "create_checkout": true}'

# Expected response:
# {
#   "checkout_url": "http://yoursite.test/checkout/?add-to-cart=123"
# }
```

## Implementation Features

✅ **JWT Token System**
- Firebase/php-jwt library with HS256 fallback
- Configurable TTL (default 1 hour)  
- Proper audience and pattern validation

✅ **WooCommerce Integration**
- Auto-creates hidden virtual products for paid licenses
- Supports purchase (one-time) payment flow
- Subscription support with WooCommerce Subscriptions
- Currency validation against store settings

✅ **External Server Support**
- Detects when license points to external server
- Returns 409 with server_url for client redirection
- Maintains compatibility with RSL Collective and other providers

✅ **Security Features**
- Crawler-only authentication challenges
- JWT signature validation
- Token expiration and audience checks
- URL pattern matching validation

## Manual Testing Checklist

- [ ] Install plugin and activate
- [ ] Create free license with server_url = `http://yoursite.test/wp-json/rsl-olp/v1`
- [ ] Test free token generation via `/token` endpoint
- [ ] Test token validation via `/introspect` endpoint
- [ ] Test crawler authentication with valid/invalid tokens
- [ ] With WooCommerce: test paid license checkout URL generation
- [ ] Test external server detection (license with external server_url)
- [ ] Verify human visitors are never blocked