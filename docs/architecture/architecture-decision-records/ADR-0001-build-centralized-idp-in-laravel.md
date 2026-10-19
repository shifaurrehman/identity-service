# ADR-0001: Build a centralized OAuth 2.0/OIDC identity provider in Laravel

**Status:** Accepted (user decision, 2026-10-15) · **Supersedes:** none

## Context

The project needs authentication reusable across Laravel APIs, React SPAs, and React Native apps, deployed independently, with no per-project re-implementation. The primary stakeholder is explicitly building this to *learn* backend architecture and security. Candidate approaches: (A) build a centralized IdP in Laravel; (B) a shared auth engine with per-app user databases; (C1) self-host a mature IdP (Keycloak); (C2) use a hosted IdP (Auth0/Cognito). Full comparison: [02 — Architecture](../../02-architecture-and-design-decisions.md).

## Decision

Build the identity provider **inside this Laravel 13 application** as a centralized service speaking OAuth 2.0 + OpenID Connect (Authorization Code + PKCE; ROPC and Implicit never). Authorization stays in client applications.

## Alternatives considered

| Alternative | Why rejected |
|---|---|
| B: Shared engine, per-app user DBs | Reuses code, not identity: no SSO, no central revocation, every app still owns credential risk — fails the stated reuse requirement structurally |
| C1: Self-hosted Keycloak | Battle-tested OIDC, but JVM memory footprint does not fit free-tier hosting, adds an unfamiliar ops stack, and reduces the project to integration work — contrary to its purpose |
| C2: Hosted IdP (Auth0/Cognito) | Least risk, but vendor cost/lock-in and near-zero learning for auth internals; rejected given the project's goals |

## Consequences

**Positive:** one identity, SSO, central revocation and lockout; standards-based clients for every platform; full control and maximal learning; free-tier friendly (plain PHP app).

**Negative / risks accepted:** we own password storage, token security, and protocol attack surface. Mitigations (binding): strict standards compliance (RFC 6749, RFC 7636, RFC 9700, OIDC Core/Discovery) instead of invented behavior; per-phase threat models against OWASP ASVS; a mandatory security-validation gate (Phase 11) before production use.

**Escape hatch:** because clients integrate via standard OIDC only, migrating to an external IdP later reconfigures clients rather than rewriting them — the integration surface is deliberately insurance against this decision aging badly.

## Compliance

- Protocol choices: [02 — Architecture](../../02-architecture-and-design-decisions.md), [authentication-flows](../../api/authentication-flows.md)
- Security obligations: [06 — Threat model](../../06-security-threat-model.md)
- Implementation package for the protocol layer: [ADR-0002](ADR-0002-oauth-oidc-server-package.md)
