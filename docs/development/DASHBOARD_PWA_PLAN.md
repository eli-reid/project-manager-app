# Dashboard Widget Registry + PWA Mobile Plan

Registry-driven dashboard using the same pattern as `ReportRegistry` — domains push definitions via `boot()`, the template loops. The existing Announcement widget is Widget 0 (live reference). Three new widgets prove the pattern end-to-end before any more are built.

---

## Part A — Dashboard Widget Registry

### Sprint 1 — Registry Infrastructure (1–2 days)

1. Create `app/Core/Dashboard/Services/DashboardWidgetRegistry.php` — direct mirror of `app/Domains/Reports/Services/ReportRegistry.php`. Definition shape:
   ```
   {key, component, section, sort, span (full|half|third), ability, title, description}
   ```
   - Methods: `registerDefinitions(array)`, `forSection(string)`, `all(): array`
   - Duplicate keys → `Log::warning` + skip (same as `ReportRegistry`)

2. Register as singleton via a new `DashboardServiceProvider` under `app/Core/Dashboard/Providers/`. Add to `bootstrap/providers.php`.

3. Refactor `resources/views/dashboard.blade.php` — replace the two placeholder `<div>`s with a Blade loop. Span rules:
   - `full` → `col-span-3`
   - `half` → `col-span-2`
   - `third` → `col-span-1`
   - Each widget rendered with `@livewire($widget['component'], key: $widget['key'])` wrapped in `@can($widget['ability'])`

4. Register the existing Announcement widget inside `AnnouncementServiceProvider::boot()` — proves the pattern before writing new code:
   ```php
   key: 'core.announcements'
   section: 'primary', sort: 10, span: 'half'
   ability: 'viewAny App\Core\Announcement\Models\Announcement'
   component: 'app.core.announcement.livewire.dashboard.widget'
   ```

5. Feature test: `tests/Feature/Dashboard/DashboardWidgetRegistryTest.php`
   - Asserts registry returns registered widgets in sort order
   - Asserts duplicate key is rejected with log warning
   - Asserts `forSection()` filters correctly

**Verification**: `php artisan test --compact --filter=DashboardWidgetRegistry`

---

### Sprint 2 — Three Pilot Widgets (2–3 days)

*Depends on Sprint 1.*

#### Widget 1 — Timecards: My Current Week

| Property | Value |
|----------|-------|
| File | `app/Domains/Timecards/Livewire/Dashboard/Widget.php` |
| View | `app/Domains/Timecards/Resources/Views/livewire/dashboard/widget.blade.php` |
| Section | `personal` |
| Span | `half` |
| Sort | `20` |
| Key | `timecards.my-week` |
| Ability | `timecards.view-own` |
| Registers in | `TimecardsServiceProvider::boot()` |

**Shows**: Auth user's timecard(s) for the current week — status badge (draft/submitted/approved/rejected), total hours logged, week date range, CTA to open/submit.

**Query**: `Timecard::query()->where('user_id', auth()->id())->currentWeek()->with('entries')->get()`

---

#### Widget 2 — Projects: Active Summary

| Property | Value |
|----------|-------|
| File | `app/Domains/Projects/Livewire/Dashboard/Widget.php` |
| View | `app/Domains/Projects/Resources/Views/livewire/dashboard/widget.blade.php` |
| Section | `operations` |
| Span | `half` |
| Sort | `10` |
| Key | `projects.active-summary` |
| Ability | `projects.view-any` |
| Registers in | `ProjectsServiceProvider::boot()` |

**Shows**: Active project count with status breakdown (active/on-hold/pending). Managers/admins see all projects; employees see only their assigned projects. Single "View All" link.

**Query**: `Project::query()->active()` scoped by `projects.view-all` permission check.

---

#### Widget 3 — Scheduler: Task Health *(admin only)*

| Property | Value |
|----------|-------|
| File | `app/Core/Scheduler/Livewire/Dashboard/Widget.php` |
| View | `app/Core/Scheduler/Resources/Views/livewire/dashboard/widget.blade.php` |
| Section | `admin` |
| Span | `full` |
| Sort | `10` |
| Key | `scheduler.task-health` |
| Ability | `scheduledtasks.view-any` |
| Registers in | `SchedulerServiceProvider::boot()` |

**Shows**: Compact table of active scheduled tasks — name, last run, next run, status (running/idle/failed). Reuses `ScheduledTaskStatusService` for status resolution.

**Query**: `ScheduledTask::query()->active()->with('status')->orderBy('next_run_at')->get()`

---

**For all three widgets**: Register component alias in the domain's `ServiceProvider::registerUIComponents()` using `Livewire::component('{dotted.alias}', Widget::class)`.

**Feature tests**: `tests/Feature/Dashboard/DashboardWidgetsTest.php`
- Each widget renders without N+1 (assert query count ≤ expected)
- Unauthorized user cannot see the widget (ability gate tested)
- Widget renders correct data for the authenticated user fixture

**Verification**: `php artisan test --compact --filter=DashboardWidgets`

---

### Sprint 3 — Dashboard UX Polish (1 day)

*Parallel-safe after Sprint 1.*

- Fixed section render order: `primary → personal → operations → alerts → admin`
- Sections with zero visible widgets for the current user are entirely omitted (no empty rows)
- Global empty state card for users who can see no widgets: "Nothing to show here yet"

---

## Part B — PWA + Mobile Shell

### Sprint 4 — Mobile Layout Shell (2–3 days)

*Can start in parallel after Sprints 1–2 are stable.*

- Create `resources/views/layouts/mobile.blade.php` — shared mobile layout shell. Structure:
  - `<head>` with PWA meta tags + theme color, safe-area CSS variables
  - Sticky header with app name + back-button slot
  - Scrollable `<main>` slot
  - Bottom navigation bar: **Home / Timecards / Projects / More**
  - Reference: `d:/project-manager/resources/views/layouts/mobile-layout.blade.php`

- Wire existing `app/Domains/*/Routes/mobile.php` files to use `x-layouts::mobile` instead of ad hoc views.

- Add a `mobile` middleware alias in `bootstrap/app.php` if not already present, setting a response header or session flag so Blade can detect mobile context (`$isMobile`) for conditional rendering on shared views.

---

### Sprint 5 — PWA Foundation (2–3 days)

*Parallel with Sprint 4.*

- **`public/manifest.json`** — adapt from `d:/project-manager/public/manifest.json`. Include `name`, `short_name`, `start_url`, `display: standalone`, `theme_color`, `background_color`, icons (192px + 512px), and shortcuts for Timecards and Dashboard.

- **`public/sw.js`** — adapt from `d:/project-manager/public/sw.js`. Strategy:
  - Cache-first for static assets (Vite build hashes)
  - Network-first for API/HTML routes
  - Offline fallback to `public/offline.html` for navigation requests

- **`public/offline.html`** — minimal branded offline fallback with "You're offline" message and retry button.

- **`resources/js/pwa.js`** — adapted from `d:/project-manager/resources/js/pwa.js`. Handles:
  - Service worker registration
  - `beforeinstallprompt` capture
  - Install button trigger
  - SW update detection + toast prompt
  - Import in `resources/js/app.js`

- **PWA meta tags** in `resources/views/layouts/app.blade.php`:
  - `<link rel="manifest" href="/manifest.json">`
  - `<meta name="theme-color" content="...">`
  - `<meta name="apple-mobile-web-app-capable" content="yes">`
  - `viewport-fit=cover` for notch safety

**Verification**: Chrome DevTools → Application → Manifest (green ✓ installable), Lighthouse PWA audit score ≥ 90.

---

### Sprint 6 — Offline Workflows (3–5 days)

*Depends on Sprint 5.*

- **SW cache strategy for high-frequency reads**: cache timecards list page + current week data, dashboard HTML shell, and static assets on install/activate. Use `stale-while-revalidate` for timecard list; network-first for submission endpoints.

- **Background sync for timecard submit**: queue `timecard.submit` sync events locally when offline, replay on reconnect. Uses existing ULID as idempotency key. Scope to submit-only first — offline creation is deferred.

- **Offline banner** in mobile layout shell — Alpine.js one-liner:
  ```html
  <div x-data="{ online: navigator.onLine }"
       @online.window="online = true"
       @offline.window="online = false"
       x-show="!online"
       class="...">
      You're offline — changes will sync when reconnected.
  </div>
  ```

---

## Sprint Summary

| Sprint | Scope | Est. Days | Dependency |
|--------|-------|-----------|------------|
| 1 | Registry infra + announcement re-registration + dashboard refactor | 1–2 | Blocks Sprint 2 |
| 2 | 3 pilot widgets (Timecards, Projects, Scheduler) | 2–3 | Blocks Sprint 3 polish |
| 3 | Dashboard UX polish (section ordering, empty states) | 1 | Parallel after Sprint 1 |
| 4 | Mobile layout shell | 2–3 | Parallel with Sprint 2+ |
| 5 | PWA foundation (manifest, SW, install prompt) | 2–3 | Parallel with Sprint 4 |
| 6 | Offline workflows + background sync | 3–5 | After Sprint 5 |

**Total estimate**:
- Dashboard complete (Sprints 1–3): ~7–10 days
- Full PWA track (Sprints 4–6): ~12–18 days from start

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `resources/views/dashboard.blade.php` | Replace placeholders with registry loop |
| `app/Core/Announcement/Livewire/Dashboard/Widget.php` | Widget 0 — live template |
| `app/Core/Announcement/Providers/AnnouncementServiceProvider.php` | First provider to add registry call |
| `app/Domains/Reports/Services/ReportRegistry.php` | Mirror this structure for `DashboardWidgetRegistry` |
| `app/Domains/Timecards/Providers/TimecardsServiceProvider.php` | Widget 1 registration |
| `app/Domains/Projects/Providers/ProjectsServiceProvider.php` | Widget 2 registration |
| `app/Core/Scheduler/Providers/SchedulerServiceProvider.php` | Widget 3 registration |
| `bootstrap/providers.php` | Register `DashboardServiceProvider` singleton |
| `resources/views/layouts/app.blade.php` | Add PWA meta tags + manifest link |
| `d:/project-manager/resources/views/layouts/mobile-layout.blade.php` | Mobile shell reference |
| `d:/project-manager/public/manifest.json` | Manifest reference |
| `d:/project-manager/public/sw.js` | Service worker reference |
| `d:/project-manager/resources/js/pwa.js` | Install prompt + update UX reference |

---

## Architecture Decisions

- **Registry location**: `app/Core/Dashboard/` (infrastructure, not a domain feature)
- **Dashboard shape**: one shared route, role-aware per widget — no separate admin/user dashboards initially
- **Widget query ownership**: each domain widget loads its own data — no central dashboard controller
- **PWA scope**: installability + offline reads + timecard background sync (offline writes deferred for other domains)
- **Span vocabulary**: `full` (col-span-3) / `half` (col-span-2) / `third` (col-span-1) — three columns on desktop, collapses to 1 on mobile
- **First 3 widgets**: Timecards My Week (personal), Projects Active Summary (operations), Scheduler Task Health (admin)
- **PWA reference**: adapt proven pieces from `project-manager` rather than designing from scratch
