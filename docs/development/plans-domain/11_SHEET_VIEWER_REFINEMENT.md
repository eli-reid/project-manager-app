# Sheet Viewer Refinement (Metadata Editing, Notes, Zoom/Pan, Capture, Revision-by-Date)

Status: **Implemented.** This document records the concrete refinement work done on top of the
baseline viewer described in `05_UI_WEB.md` and `07_FEATURES_REVISIONS_ANNOTATIONS_LINKS_COMPARE.md`,
and calls out every place the implementation deviates from, or narrows, those documents.

## Scope

Eight requirements were delivered:

1. Role-gated editing of sheet metadata (sheet number, title, discipline) from the viewer.
2. Notes/markups on a sheet, each `public` or `private`, with a toggle and filter UI.
3. Smooth, mouse-controlled zoom and pan that never triggers a Livewire round-trip or a layout
   resize.
4. Rectangle-select "copy region as image" to the OS clipboard.
5. Revisions ordered and labeled by the plan set's real-world issue/title-block date, not upload
   time.
6. Viewing notes pinned to a specific revision while browsing a newer revision ("show notes from
   all revisions" vs "this revision only").
7. Plan (issue) date surfaced in the metadata block.
8. Fullscreen no longer exits when zoom/pan state changes.

## Architecture Summary

- **Server (Livewire) owns**: authorization, persistence, the annotation/marker payload sent to
  the client, metadata editing, revision list/ordering, and note-filter state
  (`app/Domains/Plans/Livewire/Sheets/Viewer.php`).
- **Client (Alpine + Canvas) owns**: all continuous interaction — pan, zoom, drag-to-pan,
  zoom-to-cursor, drawing/hit-testing markers, rectangle capture, and the native browser
  Fullscreen API — so none of it depends on a network round-trip
  (`app/Domains/Plans/Resources/js/sheet-viewer.js`).
- View: `app/Domains/Plans/Resources/Views/livewire/sheets/viewer.blade.php`.

This matches the design doc's stated intent ("The viewer does all pan, zoom, and drawing
client-side in Alpine and canvas, touching Livewire only to persist") but the concrete toolset was
narrowed — see "Deviations from the original design doc" below.

### Domain boundary: JS lives inside the Plans domain

Per explicit instruction, the new Alpine module is **not** in the shared `resources/js` tree. It
lives at `app/Domains/Plans/Resources/js/sheet-viewer.js`, mirroring the existing
`app/Domains/Plans/Resources/Views` convention. This is the first domain in the repo with a
`Resources/js` folder; the pattern is now a precedent for other domains that need
domain-owned client behavior.

Vite still requires a single entry point (`resources/js/app.js`), so that file imports the module
via a relative path:

```js
// resources/js/app.js
// Plans-domain client behavior lives in its own domain folder; this is the
// one place a cross-domain relative import is required by Vite's single-entry model.
import '../../app/Domains/Plans/Resources/js/sheet-viewer.js';
```

Verified with `npm run build`: the built bundle contains the `planSheetViewer` Alpine component,
confirming Vite resolves the cross-directory module graph correctly.

Alpine itself is never imported as an npm package — it ships bundled inside Livewire's own JS
assets. The module registers itself the same way any Alpine plugin does:

```js
document.addEventListener('alpine:init', () => {
    Alpine.data('planSheetViewer', planSheetViewer);
});
```

## Requirement-by-Requirement Notes

### 1. Role-gated metadata editing

- `Viewer::canEditMetadata` (computed property) gates an "Edit details" toolbar button on
  `plans.update` via the existing `PlanSheetPolicy`.
- `openMetadataEditor()` / `saveMetadata()` populate and validate a `flux:modal` form
  (sheet number, title, discipline). Sheet-number uniqueness within the plan set is validated
  server-side; duplicate numbers are rejected with a flash error.
- No new authorization surface was introduced — this reuses the existing `plans.update`
  permission and policy already defined for the domain.

### 2. Public/private notes with toggle and filter

- `PlanAnnotation` gained `VISIBILITY_PUBLIC` / `VISIBILITY_PRIVATE` constants and a
  `scopeVisibleTo($query, $user)` scope.
- `PlanAnnotationPolicy::view()` — a private note is visible to its author or to any user with
  `plans.manage-annotations` (e.g. a project manager); everyone else cannot see it at all. This
  reflects the clarified design intent: private notes are not fully invisible to managers, only to
  other regular collaborators.
- The viewer exposes three independent filters bound with `wire:model.live`:
  `showPublicNotes`, `showPrivateNotes`, `showResolvedNotes`. A `Viewer::updated()` hook refreshes
  the client marker payload (`annotationMarkers`) whenever any of the three change, so the canvas
  overlay stays in sync without a manual "apply filter" step.
- Creating a note lets the author pick `public` vs `private` visibility in the composer
  (`flux:select`, since it compiles to a plain native `<select>` and is safe to drive from Alpine).

### 3. Smooth mouse-controlled zoom/pan, no layout resize

- Pan/zoom is implemented purely with a CSS transform (`translate() scale()`) on a canvas/image
  stack, driven by Alpine state (`zoom`, `panX`, `panY`) — never by resizing the surrounding DOM,
  so there is no layout thrash and no Livewire round-trip per frame.
- Mouse wheel zooms to the cursor position (the point under the cursor stays fixed on screen while
  zoom changes), and dragging (when the "pan" tool is active, or with space/middle-mouse) pans
  using raw pointer deltas.
- View state (`zoom`, plus a center-fraction pan position) is persisted to the server via a
  debounced call to `persistViewState()` so returning to a sheet restores the last view — this is
  the one deliberately-throttled network touch-point, decoupled from the continuous interaction.

### 4. Rectangle-select "copy as image"

- A dedicated "capture" tool lets the user drag a rectangle over the rendered sheet. On release,
  the module crops that rectangle out of the full-resolution sheet image via an offscreen
  `<canvas>`, converts it to a PNG blob (`canvas.toBlob`), and writes it to the OS clipboard with
  `navigator.clipboard.write([new ClipboardItem({'image/png': blob})])`.
- This only works same-origin; the sheet images are served via the existing `plans.images` route
  (`PlanImageController`), which is same-origin with the app, so no CORS work was needed.
- A small toast confirms success/failure (clipboard write can be rejected by the browser without
  a user gesture, or if the permission is denied).
- **No external library was introduced for this** — it is entirely the native Canvas + Clipboard
  APIs, per the "ask before adding a library" constraint. No such request was needed anywhere in
  this refinement.

### 5. Revisions ordered by date, not upload time

- `PlanSheetRevision::effectiveDate()` resolves the revision's real-world date: the owning plan
  set's issued/title-block date when present, falling back to the revision's `created_at` only
  when no such date exists.
- `Viewer::orderedRevisions` (computed) and the revision-comparison Livewire component
  (`Compare.php`) both order by this effective date rather than upload/insert order, and the UI
  labels each revision with that date.

### 6. Notes by revision

- `notesScope` toggles between `current` (only notes pinned to the revision being viewed) and
  `all` (every note across every revision of the sheet, so older notes remain visible while looking
  at a newer revision).
- `Viewer::historicalAnnotations` / `currentRevisionAnnotations` split the query accordingly, and
  each rendered note marker/detail view shows which revision it was created against.

### 7. Plan date in the metadata block

- The metadata panel's "Details" tab now shows the plan set's issue/title-block date alongside
  sheet number, title, and discipline, sourced from the same `effectiveDate()` used for revision
  ordering (single source of truth for "what date represents this revision").

### 8. Fullscreen survives zoom/pan changes

- Fullscreen is implemented with the real browser Fullscreen API
  (`element.requestFullscreen()` / `document.exitFullscreen()`), not a CSS-only fixed-position
  fallback keyed off Livewire/Alpine state.
- Because native fullscreen state lives entirely in the browser (outside any Livewire-diffed DOM
  or Alpine-reactive property), no re-render, zoom, or pan action can accidentally cause the
  browser to exit fullscreen. This fixes the bug architecturally rather than by special-casing
  which actions are allowed to re-render while fullscreen is active.

## Deviations From the Original Design Doc

- `07_FEATURES_REVISIONS_ANNOTATIONS_LINKS_COMPARE.md` describes a fuller CAD-style annotation
  toolset (arbitrary polylines/clouds, multiple markup shapes, etc.). This refinement implements
  the tools actually requested: a point/pin note, a rectangle note, and the rectangle capture tool.
  The client-side rendering architecture (two stacked canvases: one for committed markers, one for
  the in-progress draft) is intentionally left extensible for adding more shapes later without a
  rearchitecture.
- The existing `center_x` / `center_y` / `zoom` columns on the per-user view-state table are
  reused with a reinterpreted meaning (viewport-center fraction under the new CSS-transform model,
  instead of whatever the previous minimal viewer wrote to them). No migration was required since
  the column types/ranges (0..1 floats plus a zoom float) are unchanged — only the client-side
  semantics of what the numbers mean changed.

## Testing

- `app/Domains/Plans/Tests/Feature/PlanSheetViewerTest.php` — new, self-contained feature test
  covering: metadata-edit authorization and duplicate-number validation; annotation-create
  authorization; public/private visibility across author, other user, and manager; combined
  visibility + resolved filtering; revision ordering by plan-set date vs. upload order;
  notes-by-revision scoping; plan-date display; and update/delete/resolve authorization.
- Verified via `php artisan test --compact app/Domains/Plans` that no existing Plans-domain test
  regressed. Two pre-existing, unrelated failures remain in the repository
  (`PlanPipelineJobsTest`'s metadata-extraction job, and `Documents\Tests\Feature\PlansProjectTabTest`
  / `DocumentsDomainScaffoldTest`) — confirmed present on a fully clean baseline (`git stash -u`)
  with none of this work applied, so they are pre-existing and out of scope for this change.
- `vendor/bin/pint --dirty --format agent` passes on all modified/created PHP files.
- `npm run build` succeeds and the built bundle contains the new Alpine component.

## Libraries

No new external libraries (JS or PHP) were introduced. Zoom/pan, canvas drawing, clipboard
capture, and fullscreen are all implemented with native browser APIs plus the app's existing
Alpine.js (bundled with Livewire) and Flux UI components.
