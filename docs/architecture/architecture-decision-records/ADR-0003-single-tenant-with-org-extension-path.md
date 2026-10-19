# ADR-0003: Single tenant now, with an organization-ready schema

**Status:** Accepted (user decision, 2026-10-15) · **Supersedes:** none

## Context

The service could be a single shared user directory (all users, one tenant) or multi-tenant from day one (organizations, memberships, invitations, tenant-scoped isolation). The current consumers are the owner's own applications — B2C-shaped — but future applications may serve distinct organizations.

## Decision

**Single tenant for v1.** One central user directory; client applications are the first-class axis (the client registry), organizations are not modeled. The schema is designed so organizations can be added later **additively**.

## What "organization-ready" means concretely (and what we avoid)

| Rule now | Why it keeps the door open |
|---|---|
| ULID user identifiers, no assumptions about email uniqueness *per tenant* | Adding `organizations` + `organization_user` later needs a composite uniqueness (`org_id`, email), not a rewrite of the global unique index — a documented migration, not a breaking change |
| Every token/audit/session row carries `user_id` + `client_id`, never an implicit "current organization" | Tenant scoping later = filtering by membership join, no hidden global state to unwind |
| Authorization stays in client apps ([ADR-0001](ADR-0001-build-centralized-idp-in-laravel.md)) | Org-level permissions are an application concern; when orgs arrive, they arrive as *claims/scopes data*, not as an RBAC engine inside the IdP |
| No UI or API surface says "your organization" | Adding the concept later is purely additive |

## Alternatives considered

- **Multi-tenant from day one:** matches future B2B possibilities, but roughly doubles the data model, test matrix (cross-tenant isolation suites), and every phase's scope — before any real second tenant exists. Rejected for v1; revisit trigger is a concrete B2B client requirement.

## Consequences

- Faster path to a solid core; v1 docs and tests stay focused on authentication fundamentals.
- If/when organizations arrive: new tables + a uniqueness migration + optional claims — planned, not panicked.
- A user belonging to multiple organizations (asked in the roadmap questions) stays cleanly possible because membership is a join table, not a column on `users`.
