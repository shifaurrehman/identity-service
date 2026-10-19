# ADR-0004: Free-tier deployment — Supabase Postgres + a free PHP host

**Status:** Accepted (user decision, 2026-10-15) · **Supersedes:** none · **Detailed runbook:** Phase 12

## Context

The user selected free-tier hosting: managed Postgres on **Supabase** (free plan) for the database and a free, reliable host for the Laravel application. This shapes security-relevant design, not just cost.

## Decision

1. **Database:** Supabase PostgreSQL free tier. Supabase is used **purely as a PostgreSQL server** — its built-in auth (GoTrue), storage, and APIs are deliberately unused; enabling them is out of scope and a documented don't. Neon is the fallback candidate (also free-tier Postgres) if Supabase's limits bite.
2. **Application host:** a free PHP/Laravel-capable host — candidates evaluated and picked in Phase 12 (Laravel Cloud free/development tier, Render, Koyeb). The choice must satisfy: HTTPS by default, env/secret management, and a supported way to run queue workers + scheduler (or a documented fallback to sync queue).
3. **Local development stays SQLite-first** (zero-setup), with migrations kept PostgreSQL-portable (NFR-010); the Phase 12 pipeline runs migrations against Postgres to prove parity.

## Consequences — the constraints that shape design

| Free-tier constraint | Design obligation |
|---|---|
| **Ephemeral filesystem** (deploy containers are replaced; local files vanish) | No security-relevant state on disk: signing keys and `APP_KEY` come from env/secret storage; sessions and cache are **database-backed** (never file driver in production); uploaded files are not part of v1 |
| **Project auto-pause after inactivity** (Supabase free tier) | First-request latency spikes are accepted; health check distinguishes "app up" from "DB reachable" so pause-wakeups are visible; documented wake procedure |
| **No SSH / no shell on many free hosts** | All operations (migrations, client registration, key rotation) must be possible via deploy hooks, Artisan-through-build-step, or the host's console — a Phase 12 requirement, tested before cutover |
| **Modest CPU/RAM** | Keep request paths lean (indexed lookups, queued mail); no in-process heavy crypto beyond password hashing |
| **Single small instance** | Stateless requests (NFR-004); horizontal scale is a non-goal on this tier |

## Alternatives considered

- **Keycloak on a free VM/container:** rejected on memory footprint (see [ADR-0001](ADR-0001-build-centralized-idp-in-laravel.md)).
- **SQLite in production:** rejected — free-tier ephemeral storage cannot durably host the primary datastore; SQLite remains the dev/test engine.
- **Paying for a small VPS:** would relax the filesystem constraint; rejected for now (cost), revisitable at any time — this ADR's design obligations are all good practice regardless.

## Secrets policy (binding)

All secrets (`APP_KEY`, `DB_PASSWORD`, mail provider key, OAuth signing keys when introduced) are set via the host's secret mechanism **by name**, never committed, never printed in docs or logs. Rotation procedures are documented in Phases 8 and 12.
