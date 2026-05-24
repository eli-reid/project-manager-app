# Consolidated Architecture Spec (Draft)

## Status
- Draft baseline approved by product direction on May 24, 2026.
- This document is the single source of truth for architecture decisions while migration from `project-manager` to `project-manager-app` continues.

## Decision Set
- Mobile route convention: `mobile/{domain}`.
- UI implementation mode: Livewire-first for new and migrated route-facing screens.
- Authorization model: policy-first and permission-first, with admin middleware as a coarse boundary only.
- Security baseline: treat security verification as in-progress until pending fuzzing and endpoint checks are completed.
- Mobile UX model: dedicated mobile shell and explicit mobile route surfaces per domain.
- Documentation hygiene: remove corrupted or mixed-content docs and keep one topic per document.

## 1. System Scope and Boundaries
- `app/Core/{System}` contains cross-cutting platform systems only: auth, users/RBAC, settings, security, scheduler, notification infrastructure.
- `app/Domains/{Feature}` contains business feature modules.
- Business logic belongs in services/actions; route handlers and Livewire components remain thin orchestrators.

## 2. Boot and Provider Contracts
- Domain provider discovery must be deterministic across environments.
- Registry collisions must be non-destructive and observable (first registration wins + warning log).
- Domain providers must not assume cross-domain boot order.
- Cross-domain boot work must be deferred through `app()->booted(...)`.
- Permission registration should use dependency injection in provider boot methods.

## 3. Routing Architecture
- User routes: authenticated least-privilege groups.
- Admin routes: `admin.*` naming and explicit permission middleware.
- Mobile routes: canonical `mobile/{domain}` path shape with named routes `{domain}.mobile.*`.
- Legacy mobile URL patterns may be redirected for compatibility during transition only.
- API endpoints are primarily read/query support endpoints unless full write APIs are explicitly required.

## 4. UI Architecture
- New or migrated CRUD and workflow screens are Livewire-first.
- Blade-only route-facing pages are not the target pattern for new development.
- Shared UI pieces should be extracted when reused by two or more pages.
- Mobile is a first-class UX surface with shared shell, navigation contract, and mobile primitives.

## 5. Mobile and PWA Architecture
- Shared shell is mandatory for mobile/PWA pages.
- Primary navigation is bottom tabs plus More drawer.
- Minimum tap target is `44x44`.
- Offline queued writes in MVP are allowed for Timecards only.
- Other domains should provide offline-safe read behavior where feasible.

## 6. Authorization and Security
- Use policy checks on sensitive read and write actions.
- Avoid role-name checks in route handlers and components.
- Preserve middleware boundaries and CSP-safe inline script behavior.
- Preserve rate limits and auth boundaries for public and shared routes.
- Security test posture remains open until pending test matrices are completed.

## 7. Performance Standards
- Eager-load relation-heavy screens to avoid N+1 queries.
- Load expensive shared data once at the highest practical level and pass downward.
- Keep dashboard and widget queries bounded and purpose-built.
- Performance-sensitive flows should include targeted query-count or regression assertions.

## 8. Data and Migration Standards
- Use ULID keys and existing relationship conventions.
- Follow safe migration patterns used by this repository.
- Add factories for migrated models used in tests.
- Avoid introducing ad hoc legacy tables when normalized domain schema exists.

## 9. Quality Gates
- Minimum domain test set:
  - Authorized access path.
  - Forbidden unauthorized path.
  - Create/update/delete happy paths as applicable.
  - At least one non-admin permission-path test.
- Run targeted test suites for touched domains.
- Run formatting and re-run targeted tests after formatting.

## 10. Delivery Model
- Follow wave-based migration sequencing from platform foundation through admin hardening.
- Each wave requires explicit scope, dependency notes, and done criteria.
- Tracker documents remain operational plans; this document is the architecture contract.

## 11. Mobile Subdomain Compatibility
The selected `mobile/{domain}` route convention is compatible with a future mobile subdomain.

Implementation strategy when needed:
- Keep domain route names stable (`{domain}.mobile.*`) and generate URLs via named routes.
- Add a dedicated route group bound to mobile host constraints (for example, `m.project-manager-app.test`) while preserving route names.
- Maintain temporary redirects from path-based mobile URLs to subdomain URLs during migration.
- Keep shared mobile shell and middleware behavior unchanged so host migration is mostly routing and URL-generation work.

## 12. Canonical References
- `docs/development/FEATURE_DOMAIN_MIGRATION_CHECKLIST.md`
- `docs/development/MOBILE_PWA_FEATURE_CHECKLIST.md`
- `docs/development/TIMECARDS_IMPLEMENTATION_TRACKER.md`
- `docs/development/STOCK_ORDERS_IMPLEMENTATION_CHECKLIST.md`
- `docs/completed-features/BOOT_ORDER_HARDENING.md`
- `docs/completed-features/CORE_MIGRATION_MATRIX.md`
