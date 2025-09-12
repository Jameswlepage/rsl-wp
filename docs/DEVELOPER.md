# Developer Guide & API Reference

## Key Classes

- `RSL_License` – CRUD + XML generation
- `RSL_Admin` – admin UI, AJAX, Gutenberg panel
- `RSL_Frontend` – HTML `<script>` injection + Link headers
- `RSL_Robots` – robots.txt integration
- `RSL_RSS` – feed integration and `/feed/rsl-licenses/`
- `RSL_Media` – XMP/sidecar embedding for media
- `RSL_Server` – rewrite rules, REST, `.well-known/rsl`, crawler auth

## Hooks & Filters

- `rsl_crawler_ua_needles` – filter crawler UA detection array
- `rsl_supported_post_types` – extend post types for Gutenberg meta

## Pattern Matching

- Absolute pattern → matched against full URL.
- Server-relative pattern (starts with `/`) → matched against request path+query.
- Wildcard `*` expands to `.*`; trailing `$` anchors the end.

## REST API

### GET /wp-json/rsl/v1/licenses
List active licenses.

### GET /wp-json/rsl/v1/licenses/{id}
Get one license by ID.

### POST /wp-json/rsl/v1/validate
Validate which licenses cover a URL/path.

**Body:** `{ "content_url": "https://example.com/page" }` or `"/images/*"`

**Response:**
```json
{
  "valid": true,
  "licenses": [{ "id": 1, "name": "...", "content_url": "...", "xml_url": "..." }]
}
```

## Discovery Endpoints

* `.well-known/rsl/` → JSON with site info and endpoints
* `/rsl-license/{id}/` → `application/rsl+xml`
* `/feed/rsl-licenses/` → RSS of licenses

## Local Development

- Use `.wp-playground.json` included in repo.
- Or WP-CLI: install/activate, then visit `/?rsl_feed=1`, `robots.txt`, and any page source to see `<script type="application/rsl+xml">`.