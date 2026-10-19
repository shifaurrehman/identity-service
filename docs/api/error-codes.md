# Error Codes

> **Status:** proposed — awaiting review · **Last updated:** 2026-10-15 · Codes are the `code` extension member of our [problem+json envelope](../05-api-contracts-and-error-handling.md#error-envelope--rfc-9457-problem-details). **Stability contract:** once a phase ships a code, its meaning and status mapping do not change; new codes may be added.

## Format

`<domain>.<reason>` — lowercase, dot-separated. Domains: `common`, `auth`, `mfa`, `oidc`. Clients must branch on `code` (machine-stable), never on `detail` (human text, locale-able).

## Taxonomy

### `common` — framework-level failures

| Code | HTTP | Meaning | Introduced in |
|---|---|---|---|
| `common.invalid_request` | 400 | Malformed request (bad JSON, wrong content type) | Phase 1 |
| `common.validation_failed` | 422 | Field validation failed; `errors` object present | Phase 1 |
| `common.not_found` | 404 | Unknown route or resource | Phase 1 |
| `common.method_not_allowed` | 405 | Route exists, method doesn't | Phase 1 |
| `common.rate_limited` | 429 | Rate limit exceeded; `Retry-After` header present | Phase 1 |
| `common.server_error` | 500 | Unhandled error; generic detail only | Phase 1 |
| `common.service_unavailable` | 503 | Health-check degraded (e.g. DB unreachable) | Phase 1 |
| `common.conflict` | 409 | Conflicting state for an idempotent-ish operation | Phase 6 onward |

### `auth` — credential, session, and token failures

| Code | HTTP | Meaning | Introduced in |
|---|---|---|---|
| `auth.invalid_credentials` | 401 | Login failed; **identical for unknown email / wrong password / unverified-account-policy hits** (AUTH-010) | Phase 3 |
| `auth.account_suspended` | 403 | Account status blocks the action (explicit only where unavoidable — see note) | Phase 3 |
| `auth.token_expired` | 401 | Access token lifetime exceeded | Phase 4/8 |
| `auth.token_invalid` | 401 | Malformed/unknown/wrong-audience token | Phase 4/8 |
| `auth.refresh_reuse_detected` | 401 | A rotated refresh token was replayed — family revoked; user must re-authenticate | Phase 4 |
| `auth.session_revoked` | 401 | Server-side session invalidated by a security event | Phase 4 |
| `auth.email_not_verified` | 403 | Action requires a verified email (used only where the response may safely reveal this about *oneself*) | Phase 6 |
| `auth.reset_token_invalid` | 400 | Reset token unknown, expired, or already used (indistinguishable) | Phase 5 |

> **Enumeration note:** codes that could differ between "account exists" and "account doesn't" are deliberately collapsed. Anything reachable *without already being authenticated as that account* must not leak existence.

### `mfa` — second-factor failures (Phase 9)

| Code | HTTP | Meaning |
|---|---|---|
| `mfa.challenge_required` | 401 | Valid credentials, but a second factor must be completed |
| `mfa.invalid_code` | 401 | Wrong/expired TOTP or recovery code |
| `mfa.enrollment_required` | 403 | Policy requires enrolling a factor before continuing |

### `oidc` — protocol-level failures (Phase 8)

OAuth endpoints respond per **RFC 6749 §5.2** (form-encoded `error=...` values), **not** problem+json. These codes are for our JSON surfaces describing protocol state:

| Code | HTTP | RFC 6749 equivalent | Meaning |
|---|---|---|---|
| `oidc.invalid_client` | 401 | `invalid_client` | Unknown client or bad secret on JSON surfaces |
| `oidc.invalid_redirect_uri` | 400 | `invalid_request` (no redirect!) | Redirect URI not exactly registered — error shown at the IdP, **never redirected** |
| `oidc.invalid_scope` | 400 | `invalid_scope` | Scope not allowed for this client |
| `oidc.consent_required` | 401 | `consent_required` | User must approve the third-party client |

## Rules

1. Every error response is problem+json with a `code` from this file (or an RFC 6749 form-error on `/oauth/*` endpoints).
2. `detail` is safe for end users; anything diagnostic goes to logs keyed by `request_id`.
3. Adding a code = editing this file + the OpenAPI spec in the same phase.
4. Renaming/removing is a breaking change: requires `/api/v2` or a deprecation window.
