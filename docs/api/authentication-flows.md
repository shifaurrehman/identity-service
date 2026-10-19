# Authentication Flows — Narratives

> **Status:** proposed — awaiting review · **Last updated:** 2026-10-15 · Sequence diagrams: [sequence-diagrams.md](../architecture/sequence-diagrams.md). Step-level parameters, TTLs, and error branches are specified in Phase 8 (protocol flows), Phase 4 (refresh), Phase 3 (login).

## Flow A — First-party login on the IdP (its own pages)

1. A client application sends the user to `/oauth/authorize` with its client ID, exact redirect URI, scopes, `state`, `nonce`, and PKCE `code_challenge`.
2. The IdP shows its login page (Blade, session + CSRF protected). The user submits credentials; Phase 9 adds TOTP when enrolled.
3. First-party clients proceed silently; third-party clients see a consent screen (AUTH-055).
4. The IdP redirects back with a single-use, short-lived authorization code; the client backend (confidential) or the app itself (public, with `code_verifier`) exchanges it at `/oauth/token` for access + ID + refresh tokens.

**Failure modes (detailed in Phase 8):** unknown client or non-exact redirect URI → error page at the IdP, never a redirect (open-redirect defense); wrong credentials → same page, same message, throttled.

## Flow B — React SPA sign-in (public client)

Authorization Code + PKCE (S256). Access token held in memory only; refresh handled per the [integration guide](../05-api-contracts-and-error-handling.md) authored in Phase 13 (BFF-style httpOnly cookie is the recommended default, rotating refresh token as the alternative with documented trade-offs). `state` and `nonce` generated per attempt and verified on return.

## Flow C — React Native sign-in (public client)

Same protocol as B, via the **system browser** (ASWebAuthenticationSession on iOS, Custom Tabs on Android) — never an embedded WebView. Redirect via registered app scheme; refresh token in Keychain/Keystore; reinstall/device-loss handled by re-authentication (codes are single-device). Deep-link security, dev-vs-prod client registration, and network-failure UX are specified in Phase 13's `react-native.md`.

## Flow D — Token refresh and rotation

Refresh tokens are single-use: each exchange returns a new one in the same *family*. Replaying a consumed token revokes the family and audits the event (`auth.refresh_reuse_detected`) — the safe failure mode is re-authentication, never continued access. Specified in Phase 4.

## Flow E — Logout / single sign-out

Client-side: revoke tokens at `/oauth/revoke` (idempotent). IdP-side: RP-initiated logout ends the IdP session; `post_logout_redirect_uri` is exact-match validated. Security events (password change, MFA change, account suspension) revoke sessions and token families globally. Specified in Phases 4 and 8.

## Flow F — Service-to-service (machine identity)

Client Credentials grant: a confidential backend exchanges `client_id` + `client_secret` for an access token scoped/audience-limited to the target service. No user, no refresh token. Specified in Phases 7–8.

## Flow G — Account lifecycle events crossing flows

Password reset completion (Phase 5), MFA enrollment change (Phase 9), and suspension (Phase 2 status model) all trigger the same invalidation cascade: end IdP session(s), revoke refresh-token families, audit. Access tokens are allowed to age out within their short lifetime by design (NFR-005 trade-off, documented in Phase 4).
