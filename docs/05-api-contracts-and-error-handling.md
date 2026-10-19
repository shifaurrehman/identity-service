# 05 — API Contracts and Error Handling

> **Status:** proposed — awaiting review · **Last updated:** 2026-10-15 · Machine-readable contract: [openapi.yaml](api/openapi.yaml) · Error codes: [error-codes.md](api/error-codes.md)

## Base URL and versioning

- All JSON endpoints live under `/api/v1` (e.g. `POST /api/v1/auth/login`). Versioning is URI-based: simple, cache-friendly, and visible in every log line.
- Breaking changes ship as `/api/v2` with a deprecation window; additive changes (new optional fields, new endpoints) do not bump the version.
- **Exception — OAuth 2.0/OIDC protocol endpoints** (`/oauth/authorize`, `/oauth/token`, `/oauth/revoke`, `/.well-known/openid-configuration`): their paths and error formats are dictated by the specifications (RFC 6749 §5.2 form-encoded errors, OIDC Discovery paths) and are **not** wrapped in our envelope or versioned.

## Error envelope — RFC 9457 Problem Details

Errors use [RFC 9457](https://datatracker.ietf.org/doc/html/rfc9457) `application/problem+json` with one extension member, `code`, for stable machine handling:

```json
{
  "type": "https://auth.example.test/problems/invalid-credentials",
  "title": "Invalid credentials",
  "status": 401,
  "detail": "The email or password is incorrect.",
  "instance": "/api/v1/auth/login",
  "code": "auth.invalid_credentials",
  "request_id": "req_01JD6ZG3..."
}
```

| Member | Rule |
|---|---|
| `type` | Stable URL identifying the problem class; served as documentation later |
| `title` / `status` | Human summary / HTTP status, per RFC 9457 |
| `detail` | Safe for end users — never stack traces, SQL, file paths, or whether the *email* existed |
| `code` | **Our extension**: stable machine code from the [taxonomy](api/error-codes.md); clients branch on this, not on `detail` text |
| `request_id` | Correlates with server logs (NFR-008); also sent as `X-Request-Id` header |

Validation errors (422) add `errors` per RFC 9457 extension convention, mirroring Laravel's field/message shape:

```json
{
  "type": "https://auth.example.test/problems/validation-failed",
  "title": "Validation failed",
  "status": 422,
  "code": "common.validation_failed",
  "detail": "The given data was invalid.",
  "instance": "/api/v1/auth/register",
  "errors": { "email": ["The email must be a valid email address."], "password": ["The password must be at least 12 characters."] },
  "request_id": "req_01JD6ZG4..."
}
```

## Status code policy

| Status | Used for | Never used for |
|---|---|---|
| 200 | Successful reads/mutations returning a body | — |
| 201 | Resource creation (registration) | — |
| 204 | Success with no body (logout) | — |
| 400 | Malformed requests, invalid OAuth parameters | Authentication failures |
| 401 | Missing/invalid/expired credential or token | *Wrong password on login is 401 with `auth.invalid_credentials`*; wrong-password-during-token-refresh is `auth.refresh_reuse_detected` or `auth.token_invalid` |
| 403 | Valid identity, forbidden action (suspended account acting on tokens) | Client bugs (400) |
| 404 | Unknown route/resource; also used to avoid confirming existence | Enumeration-adjacent resources |
| 405 | Wrong method | — |
| 409 | Conflicting state (e.g. email already verified) | — |
| 422 | Validation failure | — |
| 429 | Rate limited (always with `Retry-After`) | — |
| 500 | Unhandled server error | Ever containing internals — generic problem+json, details only in logs |

## Authentication requirements on endpoints

- `Authorization: Bearer <access-token>` for protected endpoints; validation rules (issuer, audience, expiry, signature via JWKS) are defined in Phase 8 and applied by middleware.
- The IdP's own first-party pages (login, consent) use Laravel sessions + CSRF protection — they are browser pages, not bearer-token APIs.
- Every endpoint's auth requirements are stated per-endpoint in its phase document and in the OpenAPI file.

## Rate limiting

- Applied at two dimensions as phases land: **per-IP** on all public endpoints (Phase 1 baseline) and **per-account** on credential endpoints (Phase 3, AUTH-011).
- Responses carry standard headers: `RateLimit-Limit`, `RateLimit-Remaining`, `RateLimit-Reset` ([RFC 9458-style](https://datatracker.ietf.org/doc/html/rfc9458) rate-limit fields), and 429 problems include `Retry-After`.
- Limits are configuration values referenced by **name** (`RATE_LIMIT_AUTH_PER_MIN` etc.), defined per phase.

## Idempotency

| Endpoint class | Behavior |
|---|---|
| Token revocation (`POST .../revoke`) | Idempotent — revoking an already-revoked/unknown token returns success (RFC 7009 §2.2) so clients can safely retry |
| Refresh (`POST .../token` with refresh grant) | **Deliberately not idempotent** — each use rotates the token; retrying with the old token triggers reuse detection |
| Registration | Not idempotent; duplicate emails answered enumeration-safely (AUTH-002) |
| Password reset submission | Idempotent from the caller's perspective — same neutral response regardless of account existence |

## Conventions

- Timestamps: ISO 8601 UTC (`2026-10-15T19:30:00Z`).
- IDs exposed to clients: ULID strings; internal bigint never exposed.
- Requests: `application/json`; `Accept: application/json` expected on API routes (routes without it get problem+json errors regardless — errors are always JSON on `/api/*`).
- CORS: configured with an explicit origin allow-list (by environment variable name, e.g. `FRONTEND_ORIGINS`); **CORS is browser-resource-access policy, never an authentication or authorization mechanism** — every endpoint enforces auth independently of origin checks.

## Contract lifecycle

1. Each phase document specifies its endpoints (method, URL, auth, schemas, errors, rate limits, side effects, audit events) per the phase template section G.
2. Endpoints are added to [openapi.yaml](api/openapi.yaml) in the same phase, marked with the phase that implements them.
3. Drift rule: if implementation and contract disagree, either the code or the contract is wrong — the discrepancy is raised, not papered over.
