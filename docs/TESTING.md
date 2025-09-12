# Testing Guide

## Quick Test Script

Run the automated test suite:

```bash
# Start WordPress Playground (auto-mounts plugin)
npx @wp-playground/cli server --auto-mount

# In another terminal, run tests against the playground URL
./tests/rsl-server-test.sh http://127.0.0.1:9400

# Or with verbose output
./tests/rsl-server-test.sh http://127.0.0.1:9400 true
```

## Manual Testing

### Free License Token Flow
```bash
# Generate token
curl -X POST "http://127.0.0.1:9400/wp-json/rsl-olp/v1/token" \
  -H "Content-Type: application/json" \
  -d '{"license_id": 1, "client": "test-client"}'

# Validate token  
curl -X POST "http://127.0.0.1:9400/wp-json/rsl-olp/v1/introspect" \
  -H "Content-Type: application/json" \
  -d '{"token": "YOUR_TOKEN_HERE"}'
```

### WooCommerce Integration (when WooCommerce active)
```bash
# Request checkout URL for paid license
curl -X POST "http://127.0.0.1:9400/wp-json/rsl-olp/v1/token" \
  -H "Content-Type: application/json" \
  -d '{"license_id": 2, "create_checkout": true}'

# After payment, get token with order key
curl -X POST "http://127.0.0.1:9400/wp-json/rsl-olp/v1/token" \
  -H "Content-Type: application/json" \
  -d '{"license_id": 2, "wc_order_key": "ORDER_KEY_HERE"}'
```

### Crawler Authentication
```bash
# As crawler without token (should get 401 if license has server_url)
curl -A "GPTBot" "http://127.0.0.1:9400/protected-content"

# With valid token
curl -A "GPTBot" \
  -H "Authorization: License YOUR_TOKEN_HERE" \
  "http://127.0.0.1:9400/protected-content"
```

## Test Coverage

The automated test suite covers:
- JWT token generation and validation
- RSL XML output in HTML, robots.txt, and RSS
- API endpoints and error handling  
- Crawler authentication framework
- WooCommerce integration hooks
- External server compatibility