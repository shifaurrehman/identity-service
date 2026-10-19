# Authentication Service — Documentation

**Status legend used across all documents:** `proposed` → `approved` → `implemented` → `verified`. Nothing is marked implemented or verified unless the codebase proves it. Every document carries a status line at the top.

## What this project is

A **centralized, independently deployable authentication service** built in Laravel 13 (PHP 8.4). It authenticates users once and issues standards-based tokens (OAuth 2.0 + OpenID Connect) to consuming applications — Laravel APIs, React SPAs, and React Native mobile apps — so authentication logic is written once and reused everywhere. Each client application keeps its own business authorization rules.

## Document map

| Document | Contents | Status |
|---|---|---|
| [00-project-overview.md](00-project-overview.md) | Vision, verified repo assessment, glossary | Proposed |
| [01-requirements-and-scope.md](01-requirements-and-scope.md) | Scope, functional requirements (`AUTH-xxx`), NFRs | Proposed |
| [02-architecture-and-design-decisions.md](02-architecture-and-design-decisions.md) | Architecture comparison, chosen design, trade-offs | Proposed |
| [03-phase-roadmap.md](03-phase-roadmap.md) | 13 phases, dependencies, approval gates | Proposed |
| [04-data-model-and-database-design.md](04-data-model-and-database-design.md) | Target schema, constraints, org-extension path | Proposed |
| [05-api-contracts-and-error-handling.md](05-api-contracts-and-error-handling.md) | API conventions, error envelope, rate limiting | Proposed |
| [06-security-threat-model.md](06-security-threat-model.md) | System-wide threat model, OWASP ASVS mapping | Proposed |
| [architecture/system-context.md](architecture/system-context.md) | System context (C4 level 1) | Proposed |
| [architecture/component-design.md](architecture/component-design.md) | Component design (C4 level 2) | Proposed |
| [architecture/sequence-diagrams.md](architecture/sequence-diagrams.md) | End-to-end authentication flow diagrams | Proposed |
| [api/openapi.yaml](api/openapi.yaml) | OpenAPI 3.1 specification (grows per phase) | Proposed |
| [api/authentication-flows.md](api/authentication-flows.md) | Top-level flow narratives | Proposed |
| [api/error-codes.md](api/error-codes.md) | Stable machine error-code taxonomy | Proposed |
| [phases/phase-01-foundation.md](phases/phase-01-foundation.md) | Phase 1 detailed specification | Proposed — awaiting review |
| phases/phase-02 … phase-13 | Detailed phase documents | Not started (gated, see [03-phase-roadmap.md](03-phase-roadmap.md)) |
| integration/laravel.md, react-web.md, react-native.md | Client integration guides | Not started (authored in Phase 13) |

## Architecture Decision Records

ADRs capture *why* a decision was made, which alternatives were considered, and what consequences we accepted. They are immutable once accepted — a change means a new ADR that supersedes the old one.

| ADR | Decision | Status |
|---|---|---|
| [ADR-0001](architecture/architecture-decision-records/ADR-0001-build-centralized-idp-in-laravel.md) | Build a centralized OAuth 2.0/OIDC identity provider in Laravel | Accepted (user decision, 2026-10-15) |
| [ADR-0002](architecture/architecture-decision-records/ADR-0002-oauth-oidc-server-package.md) | Which package implements the OAuth/OIDC server | Proposed — decided when Phase 8 is authored |
| [ADR-0003](architecture/architecture-decision-records/ADR-0003-single-tenant-with-org-extension-path.md) | Single tenant now, organization-ready schema | Accepted (user decision, 2026-10-15) |
| [ADR-0004](architecture/architecture-decision-records/ADR-0004-free-tier-deployment.md) | Free-tier deployment (Supabase Postgres + free host) | Accepted (user decision, 2026-10-15) |

## How to read this documentation

1. Start with [00-project-overview.md](00-project-overview.md) for the vision and vocabulary.
2. Read [02-architecture-and-design-decisions.md](02-architecture-and-design-decisions.md) before any phase document — phases assume its decisions.
3. Implementation proceeds one phase at a time from [03-phase-roadmap.md](03-phase-roadmap.md). A phase document is written, reviewed, and approved **before** that phase is implemented; the next phase document is not written until the current phase is approved.

## Conventions

- **Requirement IDs**: product-level requirements use `AUTH-xxx` (defined in [01-requirements-and-scope.md](01-requirements-and-scope.md)). Each phase defines its own prefixed requirements (e.g. `FOUND-xxx` in Phase 1) that trace back to `AUTH-xxx`.
- **Terminology** is defined once in the [glossary](00-project-overview.md#glossary) and used consistently.
- **Secrets**: environment variables are referenced **by name only** (`APP_KEY`, never its value). Credentials shown in examples are illustrative and clearly fake.
- **Diagrams** use Mermaid and describe the *proposed* architecture, not generic illustrations.
- **Sources**: security-critical claims link to official specifications (IETF RFCs, OpenID Foundation), OWASP guidance, or the Laravel 13 documentation.
- Framework versions stated in documents were verified on 2026-10-15 against the installed application: Laravel 13.34.0, PHP 8.4, Pest 5.3.

## Current implementation state

**Nothing in this repository's documentation is implemented yet.** The repository currently contains a near-stock Laravel 13 skeleton plus a small tutorial `UserController` — see the assessment in [00-project-overview.md](00-project-overview.md#current-repository-assessment).
