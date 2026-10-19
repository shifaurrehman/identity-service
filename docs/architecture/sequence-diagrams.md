# Sequence Diagrams — End-to-End Flows

> **Status:** proposed — awaiting review · **Last updated:** 2026-10-15 · These describe the **target design**; detailed per-step specifications (parameters, error branches, TTLs) live in the phase documents, primarily Phase 8. Narratives: [authentication-flows.md](../api/authentication-flows.md).

## 1. React SPA / browser — Authorization Code + PKCE

```mermaid
sequenceDiagram
    participant U as User (browser)
    participant SPA as React SPA
    participant IDP as Auth Service
    U->>SPA: clicks "Sign in"
    SPA->>SPA: generate code_verifier + code_challenge (S256)
    SPA->>IDP: GET /oauth/authorize?client_id&redirect_uri&scope=openid profile&state&nonce&code_challenge
    IDP->>IDP: validate client, redirect_uri, scopes
    alt not authenticated at IdP
        IDP->>U: login page (session cookie set)
        U->>IDP: credentials (+ MFA from Phase 9)
        IDP->>U: consent screen if third-party client
    end
    IDP->>SPA: 302 redirect_uri?code&state
    SPA->>SPA: verify state matches
    SPA->>IDP: POST /oauth/token (code + code_verifier)
    IDP->>IDP: verify code, PKCE, client; single-use consume
    IDP-->>SPA: access_token (JWT) + id_token + refresh_token
    SPA->>SPA: validate id_token (issuer, audience, nonce)
    Note over SPA: access token in memory; refresh token handled per integration guide
```

Security notes: `state` binds the callback to this flow instance (CSRF on the client); `nonce` binds the ID token to this flow instance; `code_verifier` never leaves the SPA until the token exchange — the interceptor would need both the code and the verifier.

## 2. React Native — system browser + redirect

```mermaid
sequenceDiagram
    participant U as User
    participant RN as React Native app
    participant BR as System browser<br/>(ASWebAuthenticationSession / Custom Tabs)
    participant IDP as Auth Service
    U->>RN: taps "Sign in"
    RN->>RN: generate verifier, challenge, state
    RN->>BR: open /oauth/authorize?...
    BR->>IDP: (same authorize/login/consent as flow 1)
    IDP-->>BR: 302 myscheme://callback?code&state
    BR-->>RN: dismiss browser, deliver redirect
    RN->>RN: verify state; parse code
    RN->>IDP: POST /oauth/token (code + verifier)
    IDP-->>RN: tokens
    RN->>RN: store refresh token in Keychain/Keystore;<br/>access token in memory
```

Differences from SPA: redirect via app scheme/asset link (validated against the client's registered URIs), the embedded-browser **anti-pattern** is avoided (system browser shares no cookies/JS with the app), and token storage is platform-backed secure storage.

## 3. Refresh rotation with reuse detection

```mermaid
sequenceDiagram
    participant C as Client app
    participant IDP as Auth Service
    C->>IDP: POST /oauth/token (refresh_token=R1)
    IDP->>IDP: hash lookup; R1 valid & unused
    IDP->>IDP: mark R1 used; issue R2 (same family);<br/>optionally new access token
    IDP-->>C: access_token + refresh_token=R2
    Note over C: attacker replays stolen R1 later...
    C->>IDP: POST /oauth/token (refresh_token=R1)
    IDP->>IDP: R1 already used → reuse detected
    IDP->>IDP: revoke ENTIRE token family (+ audit event)
    IDP-->>C: 401 auth.refresh_reuse_detected
    Note over C: legitimate R2 is now dead too →<br/>user re-authenticates (safe failure mode)
```

## 4. Logout and global session revocation

```mermaid
sequenceDiagram
    participant U as User
    participant C as Client app
    participant IDP as Auth Service
    U->>C: logout
    C->>IDP: POST /oauth/revoke (access or refresh token)
    IDP->>IDP: idempotent revocation + audit
    opt single-sign-out (Phase 8 scope decision)
        U->>IDP: OIDC RP-initiated logout /logout<br/>(id_token_hint + post_logout_redirect_uri)
        IDP->>IDP: end IdP session; (front/back-channel notify clients)
        IDP-->>U: redirect to post-logout page (exact-match URI)
    end
    Note over IDP: password change / MFA change (Phases 5, 9) revoke all sessions + token families
```

## 5. Service-to-service — Client Credentials

```mermaid
sequenceDiagram
    participant S as Client backend (service)
    participant IDP as Auth Service
    S->>IDP: POST /oauth/token<br/>grant_type=client_credentials + client_id + client_secret
    IDP->>IDP: verify secret (hashed lookup), enabled client, allowed scopes
    IDP-->>S: access_token (JWT, audience = target service)
    S->>S: call downstream API with Bearer token;<br/>downstream validates via JWKS
    Note over S: no refresh token, no user involved — machine identity only
```

## Where each flow is specified in detail

| Flow | Endpoints finalized in | Client-side guide |
|---|---|---|
| Authorization Code + PKCE (SPA, RN) | Phase 8 | Phase 13 (`react-web.md`, `react-native.md`) |
| Refresh rotation | Phase 4 | Phase 13 |
| Logout / single sign-out | Phases 4, 8 | Phase 13 |
| Client credentials | Phases 7, 8 | Phase 13 (`laravel.md`) |
