# Security Policy

- Report vulnerabilities privately via repository security advisories.
- Stored data: license configurations only (no tokens, no secrets).
- Output: strictly escapes XML attributes and text nodes.
- Auth:
  - 401 challenges are **only** issued to crawler user-agents.
  - The demo token validator accepts non-empty tokens. Integrators should replace with a real validator against their license server.