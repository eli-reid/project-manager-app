# Plans Domain - Overview and Locked Decisions

## Problem Statement

Today a drawing set uploaded to a project is a single `Document` row rendered as one tile in the
Plans tab (`app/Domains/Documents/Livewire/Admin/Projects/PlansTab.php`). A 250-page architectural
set is one link that opens a browser PDF viewer. Field users cannot jump to sheet `A-201`, cannot
see what changed between issues, cannot mark up a sheet, and cannot follow a detail callout to its
target sheet.

The goal is Fieldwire-equivalent plan room behavior: every page becomes an addressable sheet with
a thumbnail, a detected sheet number, revision history, markup, hyperlinks, and comparison.

## Locked Decisions

Confirmed with the product owner on 2026-09-06.

| # | Decision | Rationale |
| --- | --- | --- |
| 1 | New bounded domain `app/Domains/Plans` | Documents stays a generic file library. Sheets, revisions, annotations, and links are a distinct concern with their own lifecycle. Avoids growing Documents into a god-domain. |
| 2 | Rasterizer must be swappable between Poppler and Imagick | Deployment targets differ. A driver contract lets dev use one and production the other without code change. |
| 3 | Sheet-number detection uses the PDF text layer first; OCR deferred | `smalot/pdfparser` is already installed and most modern sets are vector PDFs with a real text layer. Tesseract is added in the final phase for scanned sets. |
| 4 | Annotations stored as vector JSON overlay | Editable, per-author, filterable, resolvable, and cheap to store. Burning into the PDF happens only on export. |
| 5 | Mobile is online-only, mobile-optimized | Offline pinning needs IndexedDB tile caching plus conflict resolution. Deferred. `public/sw.js` also deliberately avoids caching navigation HTML. |

## Key Architecture Decisions

### Derivatives are not Assets

A 400-page set would create 1200+ `Asset` rows plus matching `AssetReference` rows if every
thumbnail, preview, and tile were an asset. Instead:

- The **source PDF** is a real `Asset` with an `AssetReference('plans', <plan_set_id>, 'source')`.
- **Page derivatives** are plain files at
  `plans/{project_id}/{plan_set_id}/{page}/{thumb|preview|tile_z_x_y}.webp`,
  with paths recorded on `plan_sheet_revisions`.
- Derivatives are served by a policy-checked `PlanImageController`, never from the public disk.
  Plans are project-confidential.

### Sheets are stable identities

`plan_sheets` holds the durable identity (project + sheet number). `plan_sheet_revisions` holds
each issued version. Uploading a revised set matches detected sheet numbers against existing
sheets and appends a revision instead of creating duplicates. This is what makes revision history
and comparison possible.

### Snappiness is a design constraint, not a polish phase

Pan, zoom, and drawing are 100% client-side Alpine plus `<canvas>`. Livewire is touched only to
persist, via async renderless actions, so a server round trip never re-renders the canvas. This is
specified up front in `05_UI_WEB.md` because retrofitting it later means rewriting the viewer.

## Glossary

| Term | Meaning |
| --- | --- |
| **Plan set** | One uploaded PDF containing many pages. The delivery unit ("Architectural, issued 2026-04-01"). |
| **Sheet** | A durable logical drawing identified by sheet number (`A-201`) within a project. |
| **Revision** | One version of a sheet, originating from one page of one plan set. |
| **Derivative** | A generated raster image of a page: thumbnail, preview, or tile. |
| **Hotspot** | A rectangular region on a sheet that links to another sheet. |
| **Title block** | The region (usually bottom-right) containing sheet number and title. |

## Scope Boundaries

**In scope for v1**

Page splitting, sheet index, viewer with pan/zoom, revisions, comparison, annotations, sheet links,
sheet-number detection from the text layer, desktop and mobile surfaces, swappable rasterizer.

**Explicitly out of scope for v1**

Offline sheet pinning, real-time multi-user annotation presence, deep RFI/task integration beyond
an optional `linked_task_id`, DWG/DXF/Revit ingestion, punch lists, BIM or 3D, sheet-level
e-signatures, automatic revision-cloud detection.

## Conventions This Domain Must Follow

Verified against the existing codebase and `AGENTS.md`.

- Domain providers are auto-discovered by `app/Domains/Providers/DomainServiceProvider.php`.
- Permissions use const arrays plus `all()`, registered via DI in
  `boot(PermissionRegistryContract $permissionRegistry)`. Never resolve the registry with
  `$this->app->make(...)` inside provider methods.
- Route-facing pages are Livewire-first using `Route::livewire(...)`. Using
  `Route::get(..., Component::class)` causes invalid invokable action bootstrap failures.
- Domain views live in `app/Domains/Plans/Resources/Views/livewire/**`, not `resources/views`.
- Migrations use `ulid('id')->primary()` and `foreignUlid(...)->constrained()`, guarded by
  `Schema::hasTable`.
- Every `@foreach` in a Livewire view needs `wire:key`. This repo has known morphing bugs without it.
- Run `vendor/bin/pint --dirty --format agent` after PHP edits.
