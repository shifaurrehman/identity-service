# ADR-0002: OAuth/OIDC server implementation package

**Status:** Proposed — decision deferred until Phase 8 is authored · **Supersedes:** none · **Blocks:** Phase 4 design finalization (token table shapes)

## Context

ADR-0001 commits us to speaking OAuth 2.0 + OIDC, but not to *how the protocol layer is implemented*. Options exist because the Laravel ecosystem has no complete first-party OIDC server:

1. **Laravel Passport** — first-party OAuth2 server (PKCE-capable). Per the Laravel 13 documentation, Passport is for applications needing "all the features provided by the OAuth2 specification", but it does **not** ship a complete OIDC layer (ID tokens, discovery document, `userinfo`) out of the box.
2. **`league/oauth2-server` + an OIDC extension** (e.g. the `openid-connect-server` extension family) — the underlying engine Passport itself uses; extensions add the OIDC pieces. More wiring, full control.
3. **Hand-rolled protocol implementation** — maximum learning, maximum risk; hard to justify given RFC 9700's attack-surface catalogue.

## Decision

**Not yet made — deliberately deferred.** It will be decided when the Phase 8 document is authored, using the evaluation criteria below. Phases 1–3 (foundation, identity model, registration/login) depend only on framework primitives and are unaffected.

## Evaluation criteria (agreed now)

A candidate must satisfy all **mandatory** criteria; ties broken by the optional ones:

| # | Criterion | Mandatory? |
|---|---|---|
| 1 | Enforces Authorization Code + PKCE (S256) for public clients; cannot be misconfigured into implicit/ROPC | ✅ |
| 2 | Supports OIDC Core: ID tokens, discovery (`/.well-known/openid-configuration`), `userinfo` | ✅ |
| 3 | Asymmetric signing keys with rotatable JWKS | ✅ |
| 4 | Actively maintained; compatible with Laravel 13 / PHP 8.4 (verified via `composer show` and packagist release dates at decision time) | ✅ |
| 5 | Refresh-token rotation and revocation semantics implementable on top (family revocation) | ✅ |
| 6 | Security track record (no unpatched advisories) | ✅ |
| 7 | Depth of learning exposure vs. amount of bespoke security-critical code we must write and own | ➖ tie-breaker |
| 8 | Schema fit with the [data model](../../04-data-model-and-database-design.md) | ➖ tie-breaker |

## Consequences of deferring

- Phase 4's token-table design is written against the *domain* schema (hashed refresh tokens, families) and explicitly notes where the chosen package may impose its own table names — a bounded, documented rework, not an architecture risk.
- No OAuth-protocol code is written before this ADR is accepted.

## References

- Laravel 13 docs — authentication/passport ecosystem overview (verified 2026-10-15): Sanctum preferred for first-party SPA/mobile; Passport when full OAuth2 is needed; neither is a complete OIDC provider alone.
- RFC 6749, RFC 7636, RFC 9700, OIDC Core/Discovery (links in [00 — Overview](../../00-project-overview.md#source-guidance)).
