# Mobile PWA Feature Checklist

This checklist tracks the mobile PWA rollout for `project-manager-app` only.

Scope for Phase 1:
- Dashboard
- Timecards
- Projects
- Dailies
- Stock Orders
- Documents

Non-negotiable design constraints:
- All mobile/PWA pages must use the shared mobile shell.
- Primary navigation is bottom tabs plus a `More` drawer.
- Minimum touch target is `44x44`.
- Mobile UI uses higher contrast and larger touch targets by default.
- Domains must use shared mobile primitives instead of custom page shells.
- Offline queued writes are allowed for Timecards only in MVP.

---

## 1. Platform Foundation

### 1.1 Shared Shell
- [x] Add shared mobile layout shell in `resources/views/layouts/mobile.blade.php`
- [x] Add safe-area utilities in `resources/css/app.css`
- [x] Add shared mobile bottom navigation component
- [x] Add mobile dashboard entry route
- [x] Add mobile dashboard view that reuses dashboard sections/widgets
- [ ] Add global mobile page primitives for list/detail/form pages
- [ ] Add shared mobile empty state component
- [ ] Add shared mobile loading state component
- [ ] Add shared mobile error state component

### 1.2 PWA Core
- [x] Add `public/manifest.json`
- [x] Add `public/sw.js`
- [x] Add `public/offline.html`
- [x] Add `resources/js/pwa.js`
- [x] Add `resources/js/mobile.js`
- [x] Import mobile/PWA modules from `resources/js/app.js`
- [x] Add PWA meta tags to shared head partial
- [ ] Verify installability in browser dev tools
- [ ] Verify standalone launch behavior on device/home screen
- [ ] Add branded install CTA placement rules

### 1.3 Global Mobile Behavior
- [x] Add back-navigation helper for mobile shell
- [x] Add haptic hook support for mobile actions
- [x] Add update notification behavior for service worker refreshes
- [x] Add online/offline toast notifications
- [ ] Add persistent offline banner in shared mobile shell
- [ ] Add queued action indicator for offline submissions

---

## 2. Route Conformance

Canonical target convention:
- `mobile/{domain}` is the long-term preferred path shape
- Named routes should follow `{domain}.mobile.*`

### 2.1 Audit and Normalize
- [ ] Audit all existing `Routes/mobile.php` files for prefix inconsistencies
- [ ] Decide and document compatibility redirects for legacy mobile URLs
- [ ] Normalize all Phase 1 domains to a single mobile route convention
- [ ] Add route-level tests for every Phase 1 mobile entry route

### 2.2 Current Domain Route Status
- [x] Dashboard mobile route exists
- [x] Timecards mobile routes exist
- [x] Dailies mobile routes exist
- [x] Projects mobile routes exist
- [ ] Stock Orders mobile routes upgraded from scaffold to real screens
- [ ] Documents mobile routes upgraded from scaffold to real screens

---

## 3. Navigation and IA

### 3.1 Primary Mobile Navigation
- [x] Add bottom navigation with `Home`, `Projects`, `Timecards`, `Dailies`, and `More`
- [x] Make bottom nav permission-aware
- [x] Add `More` drawer with secondary links
- [ ] Move all bottom-nav destinations to normalized mobile routes only
- [ ] Add active-state rules for all mobile destinations
- [ ] Add domain switcher entry from dashboard cards or header actions

### 3.2 Secondary Navigation
- [ ] Add mobile page header variants for list/detail/form flows
- [ ] Add standard back behavior fallback rules per domain
- [ ] Add form-level sticky action bar pattern
- [ ] Add mobile quick actions pattern for primary domain actions

---

## 4. Shared Design System Contract

### 4.1 Layout Rules
- [x] Shared mobile shell exists
- [ ] All Phase 1 mobile pages use the shared mobile shell
- [ ] No Phase 1 domain renders a custom mobile shell
- [ ] Content spacing scale is documented and enforced
- [ ] Safe-area spacing is applied to all sticky/fixed mobile chrome

### 4.2 Component Rules
- [ ] Shared mobile section header component exists
- [ ] Shared mobile list row/card component exists
- [ ] Shared mobile status badge mapping exists
- [ ] Shared mobile action bar component exists
- [ ] Shared mobile filter/search row exists
- [ ] Shared mobile form section component exists

### 4.3 Accessibility and Ergonomics
- [ ] All action targets meet `44x44` minimum
- [ ] Contrast passes mobile dark/light readability targets
- [ ] Focus states are visible for all interactive controls
- [ ] Loading/disabled/pressed states are explicitly styled
- [ ] Screen-reader labels exist for navigation and icon-only actions

---

## 5. Domain Rollout

### 5.1 Dashboard
- [x] Mobile dashboard route exists
- [x] Mobile dashboard view exists
- [x] Mobile dashboard reuses widget sections
- [ ] Add mobile-specific dashboard action shortcuts
- [ ] Add domain switching affordances from dashboard
- [ ] Add dashboard offline-read verification

### 5.2 Timecards
- [x] Mobile index route exists
- [x] Mobile create route exists
- [x] Mobile show route exists
- [x] Mobile edit route exists
- [ ] Ensure all Timecards mobile pages use shared mobile shell/primitives
- [ ] Add offline submit queue for timecard submit only
- [ ] Add queued submit replay on reconnect
- [ ] Add Timecards offline UX messaging

### 5.3 Projects
- [x] Mobile index route exists
- [x] Mobile show route exists
- [ ] Ensure Projects mobile pages use shared mobile shell/primitives
- [ ] Add mobile-specific project detail hierarchy review
- [ ] Add mobile project quick actions pattern

### 5.4 Dailies
- [x] Mobile index route exists
- [x] Mobile create route exists
- [x] Mobile show route exists
- [x] Mobile edit route exists
- [ ] Ensure Dailies mobile pages use shared mobile shell/primitives
- [ ] Add report grouping/status presentation for mobile
- [ ] Add mobile-friendly daily report form flow review

### 5.5 Stock Orders
- [ ] Replace scaffold mobile route response with Livewire or view-backed index
- [ ] Add mobile detail route
- [ ] Add mobile create/edit flow if included in Phase 1
- [ ] Ensure Stock Orders mobile pages use shared mobile shell/primitives

### 5.6 Documents
- [ ] Replace scaffold mobile route response with real index screen
- [ ] Add mobile detail/download flow
- [ ] Ensure Documents mobile pages use shared mobile shell/primitives
- [ ] Review offline-safe read behavior for cached document metadata

---

## 6. Offline and Sync

### 6.1 Read-Only Offline Support
- [x] Offline fallback page exists
- [ ] Cache mobile dashboard shell for offline use
- [ ] Cache key list/detail reads for Phase 1 domains where appropriate
- [ ] Define stale-while-revalidate vs network-first policy per screen type

### 6.2 Queued Writes
- [ ] Define Timecards submit queue payload shape
- [ ] Add idempotency key strategy for offline replay
- [ ] Implement reconnect replay flow
- [ ] Add failure recovery UI for queued submissions
- [ ] Add test coverage for offline queue/replay path

---

## 7. QA and Verification

### 7.1 Automated Tests
- [x] Add dashboard PWA foundation feature tests
- [x] Add Projects mobile route coverage
- [ ] Add route coverage tests for Timecards mobile flows
- [ ] Add route coverage tests for Dailies mobile flows
- [ ] Add route coverage tests for Stock Orders mobile flows
- [ ] Add route coverage tests for Documents mobile flows
- [ ] Add shared-shell conformance checks for Phase 1 domains

### 7.2 Browser and Device QA
- [ ] Verify manifest loads correctly
- [ ] Verify service worker registers and updates correctly
- [ ] Verify offline fallback renders when disconnected
- [ ] Verify bottom nav behavior on iPhone and Android viewport sizes
- [ ] Verify safe-area behavior on notched devices
- [ ] Verify install prompt behavior on supported browsers

### 7.3 Release Readiness
- [ ] Confirm all Phase 1 domains have mobile entry points
- [ ] Confirm all Phase 1 pages use approved shell/components
- [ ] Confirm design constraints are documented for future domains
- [ ] Confirm no new mobile route uses the old inconsistent pattern without redirect coverage

---

## 8. Current Verified Implementation Snapshot

Verified in repo at time of writing:
- [x] Shared mobile shell added
- [x] PWA manifest/service worker/offline files added
- [x] PWA metadata added to shared head
- [x] Mobile dashboard route/view added
- [x] Projects mobile route wiring added
- [x] Dashboard-focused feature tests passing
- [x] Projects domain feature tests passing after mobile route wiring

Not yet complete:
- [ ] Phase 1 domain conformance to shared mobile primitives
- [ ] Stock Orders and Documents real mobile screens
- [ ] Full route normalization across all mobile domains
- [ ] Offline queued submit workflow for Timecards
- [ ] Device/browser PWA verification pass

---

## 9. Recommended Execution Order

- [ ] 1. Normalize remaining mobile route prefixes and add redirects if needed
- [ ] 2. Build shared mobile page primitives
- [ ] 3. Migrate Timecards and Dailies to the shared mobile shell/primitives
- [ ] 4. Migrate Projects to the shared mobile shell/primitives
- [ ] 5. Replace Stock Orders and Documents mobile scaffolds with real screens
- [ ] 6. Implement Timecards offline queued submit flow
- [ ] 7. Run browser/device PWA QA pass
