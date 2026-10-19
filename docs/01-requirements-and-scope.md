# 01 — Requirements and Scope

> **Status:** proposed — awaiting review · **Last updated:** 2026-10-15 · Requirement IDs here are the product-level source of truth; phase documents trace back to them.

## In scope (v1)

1. Central user directory: one identity per person, shared across registered client applications.
2. First-party credential authentication on the IdP itself: registration, login, logout, session management.
3. Password lifecycle: hashing, policy, reset/recovery, rotation.
4. Email verification.
5. Token infrastructure: access tokens, refresh tokens with rotation and revocation.
6. Client application registry: registering trusted apps, redirect URIs, client types, allowed scopes.
7. OAuth 2.0 + OIDC: Authorization Code + PKCE for public clients, confidential client support, discovery, `userinfo`, logout.
8. MFA: TOTP with recovery codes.
9. Audit logging of security-relevant events, with retention policy.
10. Operational baseline: health checks, rate limiting, structured logging, deployment documentation for the chosen free-tier hosts.

## Out of scope (v1), with rationale

| Excluded | Rationale | Revisit trigger |
|---|---|---|
| Multi-tenancy / organizations | Single directory suffices; doubles data model and test surface | First B2B client requirement |
| Social login (Google/Apple/etc.) | Adds provider-callback surface before core is proven | Post-v1, as an identity *linking* feature |
| Passkeys / WebAuthn | Excellent future default; requires mature core first | Post-MFA phase |
| SAML, enterprise federation | Different protocol family, heavy XML tooling | Explicit enterprise demand |
| Per-application permission management | Authorization belongs to client apps by design ([ADR-0001](architecture/architecture-decision-records/ADR-0001-build-centralized-idp-in-laravel.md)) | Never — architectural boundary |
| Admin UI | v1 administers via Artisan commands and seeders; UI is polish | After Phase 10 |
| Profile management (avatars, preferences) | Not an authentication concern | Product decision, post-v1 |

## Functional requirements

Each requirement gets a stable ID. Phase documents map their phase-level requirements to these.

### Identity and registration

| ID | Requirement |
|---|---|
| AUTH-001 | Users register with an email address and password; email uniqueness is enforced case-insensitively. |
| AUTH-002 | Registration responses never reveal whether an email is already registered (anti-enumeration). |
| AUTH-003 | Passwords are hashed with a modern adaptive algorithm (bcrypt/argon2id via Laravel defaults) and never logged, serialized, or returned. |
| AUTH-004 | Password policy follows NIST SP 800-63B: length-first, blocklist-aware; no forced periodic rotation, no composition rules. |
| AUTH-005 | A new account starts unverified; verification is required before sensitive actions (defined per phase). |

### Login and sessions

| ID | Requirement |
|---|---|
| AUTH-010 | Login requires email + password; failure responses are identical whether the email exists, is wrong, or the password is wrong. |
| AUTH-011 | Login is rate-limited per account and per source (throttling), with escalating lockout windows. |
| AUTH-012 | The IdP maintains server-side sessions for its own login/consent pages; logout invalidates them. |
| AUTH-013 | Security-sensitive changes (password change, MFA change) invalidate other sessions and refresh tokens. |

### Tokens

| ID | Requirement |
|---|---|
| AUTH-020 | Access tokens are short-lived (single-digit minutes), signed asymmetrically, and validated by clients via published keys (JWKS). |
| AUTH-021 | Refresh tokens rotate on every use; presenting an already-rotated token revokes the entire token family (reuse detection). |
| AUTH-022 | Tokens are revocable globally (user logout-everywhere) and per-client. |
| AUTH-023 | Token lifetimes and signing keys are configurable by environment variable **names** (values never committed). |

### Recovery and verification

| ID | Requirement |
|---|---|
| AUTH-030 | Password reset uses single-use, expiring tokens delivered by email; responses are enumeration-safe. |
| AUTH-031 | Email verification uses expiring signed links or codes; verification state is queryable by clients. |
| AUTH-032 | Completing a password reset revokes existing sessions and refresh tokens. |

### MFA

| ID | Requirement |
|---|---|
| AUTH-040 | TOTP is supported as a second factor, enabled per user, with recovery codes generated at enrollment. |
| AUTH-041 | MFA enrollment and verification are separate, audited steps; recovery codes are shown exactly once. |

### Client applications and OAuth/OIDC

| ID | Requirement |
|---|---|
| AUTH-050 | Client applications are registered with: name, type (public/confidential), exact redirect URIs, allowed scopes, and an enable/disable flag. |
| AUTH-051 | All public-client flows use Authorization Code + PKCE (S256); the implicit and ROPC grants are not implemented. |
| AUTH-052 | Redirect URIs are matched exactly (no wildcard/prefix matching); mismatches fail closed. |
| AUTH-053 | The service exposes OIDC discovery (`/.well-known/openid-configuration`), `userinfo`, and standards-compliant token/revocation endpoints. |
| AUTH-054 | Client secret leakage or suspected compromise is handled by secret rotation without deleting the client. |
| AUTH-055 | User consent is required for third-party clients; first-party clients may skip the consent screen. |

### Audit and administration

| ID | Requirement |
|---|---|
| AUTH-060 | Security-relevant events (login success/failure, token issuance/revocation, password/MFA changes, client changes) are recorded in an append-only audit log. |
| AUTH-061 | Audit entries never contain plaintext credentials, full tokens, or unnecessary PII. |

## Non-functional requirements

| ID | Category | Requirement | Why it matters | How it is verified |
|---|---|---|---|---|
| NFR-001 | Security | All transport is HTTPS in staging/production; HSTS at the proxy layer | Tokens over plaintext are captureable | Phase 12 deployment checklist |
| NFR-002 | Security | Secrets only via environment; never in code, logs, or docs | Leakage → total compromise | `grep`-based checks in review checklist; CI in Phase 12 |
| NFR-003 | Performance | Auth endpoints p95 < 300 ms on free-tier hardware; health check p95 < 50 ms | Login latency is user-facing and a DoS signal | Phase 11 load smoke-test |
| NFR-004 | Scalability | Stateless API requests (session state in DB, not local files) so workers can scale horizontally | Free hosts run small/dynamic workers | Architecture review; Phase 12 config |
| NFR-005 | Reliability | No single endpoint failure blocks all clients (token validation is local via JWKS, no per-request introspection) | IdP outage shouldn't take down client APIs | Design enforced in Phase 8 review |
| NFR-006 | Availability | Deployment with zero-downtime expectation deferred; documented cold-start behavior on free tier accepted | Free-tier constraint, documented honestly | Phase 12 runbook |
| NFR-007 | Maintainability | Pint formatting, Pest tests, thin controllers, domain services hold business logic | Long-lived codebase | Phase 1 architecture tests onward |
| NFR-008 | Observability | Every API response carries a request ID; auth events are logged structurally | Incidents need correlation | Phase 1 middleware tests; Phase 10 |
| NFR-009 | Privacy | Minimal PII (email, name); audit retention window defined; deletion path for user data | GDPR-aligned hygiene | Phase 10 retention job |
| NFR-010 | Compatibility | Dev on SQLite; migrations portable to PostgreSQL (Supabase) without rewrites | Free-tier DB choice ([ADR-0004](architecture/architecture-decision-records/ADR-0004-free-tier-deployment.md)) | CI runs migration suite on Postgres in Phase 12 |
| NFR-011 | Testability | Every functional requirement maps to at least one automated test | Requirements without tests are wishes | Traceability maintained in phase docs |

## Constraints and assumptions

**Constraints (accepted):** free-tier hosting (ephemeral filesystem, project auto-pause) — [ADR-0004](architecture/architecture-decision-records/ADR-0004-free-tier-deployment.md); single service deployment; Laravel 13 / PHP 8.4.

**Assumptions (stated, not silently held):**

1. Email delivery is available for verification/reset mail (a free transactional mail provider; local dev uses the `log` mailer). Exact provider chosen in Phase 5.
2. First-party UI pages (login, consent) are server-rendered Blade, served by this same application.
3. Usernames are email addresses only (no separate username) for v1.
4. Each client application has at most one redirect URI set per environment initially; multiple URIs are supported but discouraged.
5. The service is internet-facing; there is no intranet assumption.
