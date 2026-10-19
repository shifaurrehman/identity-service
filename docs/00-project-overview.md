# 00 — Project Overview

> **Status:** proposed — awaiting review · **Last updated:** 2026-10-15

## Vision

Every new project currently re-implements registration, login, password reset, email verification, and token handling. This project builds **one centralized authentication service** that is:

- **Independently deployable** — it runs as its own application, with its own database, and nothing else depends on its internals.
- **Reusable** — new applications consume it through standard protocols instead of re-writing auth logic.
- **Standards-based** — it speaks OAuth 2.0 and OpenID Connect, so clients integrate with off-the-shelf libraries rather than a proprietary API contract.
- **Educational** — the primary stakeholder is learning backend architecture and security, so documentation explains *why* before *how*, and no capability is delegated to magic.

### Consuming applications (planned)

| Client | Type | Authentication flow |
|---|---|---|
| Laravel REST API / backend | Confidential client | OIDC Authorization Code + PKCE (server-side), or Client Credentials for service-to-service |
| React single-page app | Public client | OIDC Authorization Code + PKCE, browser |
| React Native app (iOS/Android) | Public client | OIDC Authorization Code + PKCE, system browser + redirect |
| Future clients | Any platform | Same standard flows — that is the point of using OIDC |

### Explicit non-goals (for v1)

- **Business authorization.** The service answers *who is this user?* — each application answers *what may they do here?*. It will never enforce application-specific permissions.
- **Multi-tenancy / organizations.** One user directory. The schema avoids decisions that would block adding organizations later (see [ADR-0003](architecture/architecture-decision-records/ADR-0003-single-tenant-with-org-extension-path.md)).
- **Social login, SAML, passkeys/WebAuthn.** Documented as future extensions; deliberately out of the first pass so the core is solid.
- **User profile management.** Only identity attributes needed for authentication (email, name, status, security metadata).

## Current repository assessment

Inspected 2026-10-15 — this describes what **exists today**, before any of the proposed work:

| Area | Finding |
|---|---|
| Framework | Laravel 13.34.0 (`laravel/framework ^13.17`), PHP constraint `^8.3`, runtime PHP 8.4 |
| Auth packages | **None.** No Sanctum, Passport, Fortify, Breeze, Jetstream, or Socialite. `config/auth.php` is stock (single `web` session guard, Eloquent `users` provider) |
| Routes | [routes/web.php](../routes/web.php) only — 3 tutorial GET routes (`/user`, `/getUsers`, `/getUserDetails/{name}`) backed by a hard-coded [UserController](../app/Http/Controllers/UserController.php). **No `routes/api.php`**, no CORS configuration |
| Database | Stock migrations only: `users`, `password_reset_tokens`, `sessions`, cache, jobs tables. SQLite locally (`database/database.sqlite`, migrated). Stock `User` model, `UserFactory`, `DatabaseSeeder` |
| Tests | Pest 5.3 with stock example tests only. The feature test hits `/` which no longer exists (route was replaced) — it currently fails with 404; fixing it is [Phase 1](phases/phase-01-foundation.md) work |
| Frontend | Stock Tailwind 4 / Vite 8 setup; unused by the auth service beyond the IdP's own login/consent pages |
| Ops | No CI, no Docker, no deployment configuration. `.env` exists with `APP_KEY` set; no `docs/` existed before this documentation |
| Git | Single commit on `main`, clean tree |

**Conclusion:** effectively a clean slate. No architectural decisions need to be unwound; the service can be shaped deliberately from Phase 1.

## Glossary

Terms are defined once here and used with these meanings everywhere. Where a concept has a React Native analogy, it is noted — the primary stakeholder comes from that world.

| Term | Definition |
|---|---|
| **Authentication (AuthN)** | Proving *who* someone is (credentials, MFA). The core job of this service. |
| **Authorization (AuthZ)** | Deciding *what* an authenticated actor may do. Belongs to each client application, not this service. Like checking a user's role in your app's navigation guard — done locally, per app. |
| **Identity** | The durable record of a user (email, status, security metadata) that survives sessions and tokens. |
| **Identity Provider (IdP)** | A service that authenticates users and asserts their identity to other systems. What this project builds. |
| **Relying Party (RP)** | An application that delegates authentication to an IdP. Your Laravel/React/RN apps are RPs. |
| **Session** | Server-side state for an authenticated browser, keyed by a cookie. Like server-side app state with the client holding only a reference key. |
| **Access token** | Short-lived credential presented to APIs on each request. Planned: a JWT (~10 min) so client backends can validate it locally without calling home. |
| **Refresh token** | Longer-lived credential used *once* to obtain new access tokens. Rotated on every use; stored securely on the client (Keychain/Keystore on mobile). |
| **JWT (JSON Web Token)** | A signed, self-describing token ([RFC 7519](https://datatracker.ietf.org/doc/html/rfc7519)). Signed ≠ encrypted — anyone can read it, nobody can forge it. Like a signed props object. |
| **JWKS** | JSON Web Key Set — the endpoint where an IdP publishes its public signing keys so clients can verify JWTs. |
| **OAuth 2.0** | An *authorization* delegation framework ([RFC 6749](https://datatracker.ietf.org/doc/html/rfc6749)); often used as the substrate for authentication via OIDC. |
| **OpenID Connect (OIDC)** | An identity layer on top of OAuth 2.0 that makes it an authentication protocol: ID tokens, `/userinfo`, discovery. |
| **PKCE** | Proof Key for Code Exchange ([RFC 7636](https://datatracker.ietf.org/doc/html/rfc7636)) — the app that starts a login proves it is the same app that receives the callback. Mandatory for all public clients here. |
| **Public vs confidential client** | Public: cannot keep a secret (SPA, mobile app). Confidential: a server backend that can (Laravel API client). |
| **SSO (Single Sign-On)** | One login at the IdP unlocks all connected applications. A *consequence* of centralizing identity, not a separate feature. |
| **Scope** | A granular string (`openid`, `profile`) limiting what an access token can do — coarse-grained, service-level; *not* application permissions. |
| **Token rotation** | Issuing a fresh refresh token each time one is used, so a stolen old token becomes detectable (reuse detection). |

## Source guidance

- Laravel 13 documentation: <https://laravel.com/docs/13.x> (authentication, passport, sanctum sections)
- OAuth 2.0: [RFC 6749](https://datatracker.ietf.org/doc/html/rfc6749) · PKCE: [RFC 7636](https://datatracker.ietf.org/doc/html/rfc7636) · Security BCP: [RFC 9700](https://datatracker.ietf.org/doc/html/rfc9700)
- OpenID Connect: [Core 1.0](https://openid.net/specs/openid-connect-core-1_0.html) · [Discovery 1.0](https://openid.net/specs/openid-connect-discovery-1_0.html)
- OWASP: [ASVS 4.0.3](https://owasp.org/www-project-application-security-verification-standard/), [Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- NIST digital identity password guidance: [SP 800-63B](https://pages.nist.gov/800-63-3/sp800-63b.html)
