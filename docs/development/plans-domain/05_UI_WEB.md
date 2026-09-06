# Plans Domain - Web UI

All route-facing pages are Livewire-first. Components live in `app/Domains/Plans/Livewire/**`,
views in `app/Domains/Plans/Resources/Views/livewire/**`. The namespace is registered with
`Livewire::addNamespace('plans', classNamespace: 'App\Domains\Plans\Livewire')`.

## Performance Contract

This is the part most likely to be got wrong, so it is specified before the components.

| Rule | Reason |
| --- | --- |
| Pan, zoom, and drawing are 100% client-side Alpine plus `<canvas>` | A Livewire round trip per mouse move would be unusable |
| Persistence uses `wire:click.async` / `#[Async]` with `.renderless` | Saving markup must not re-render and reset the canvas |
| Thumbnail rail is an `@island` | Rail paging must not re-render the viewer |
| Neighbor pages preloaded via `new Image()` on navigation | Next/prev feels instant |
| Thumbnail shown scaled up immediately, swapped for preview on load | Perceived load time drops to near zero |
| `wire:navigate` between sheets, `.preserve-scroll` on the rail | Avoids full page reloads and scroll jumps |
| Right-hand panel `lazy`, compare panel `defer` | Keeps first paint light |
| `wire:key` on every loop | This repo has documented morphing bugs without it |
| Index queries eager-load `currentRevision` and select narrow columns | `text_layer` is longText and must never be selected for lists |

## Components

### `Livewire/Admin/Projects/PlansTab`

The project tab panel. Responsibilities:

- Plan set uploader. Reuse the Alpine upload panel UX already proven in
  `app/Domains/Documents/Resources/Views/livewire/admin/projects/plans-tab.blade.php`
  (collapsible panel, drag target, progress bar, auto-title from filename).
- Processing progress per set, polling only while a set is not `ready` or `failed`.
- Set list with discipline, issue date, page count, and status.
- Embeds the sheet index.

Capability flags are computed once in `mount()` into public bools
(`$canUploadPlans`, `$canUpdatePlans`, `$canDeletePlans`, `$canAnnotate`) rather than calling
`hasPermission` inside the Blade loop, matching the existing `PlansTab` approach.

### `Livewire/Sheets/Index`

The scrolling page-preview index. This is the Fieldwire-like surface the feature is named for.

- Responsive thumbnail grid: 2 columns on small, up to 6 on extra-large.
- Toggle between grid and a compact list showing sheet number, title, revision, and markup count.
- Search across sheet number and title, debounced 300ms.
- Filters: discipline chips, plan set, "has markup", "revised since" date.
- Sort: sheet number natural order, page order, or recently viewed.
- Paging via `wire:intersect` infinite scroll rather than pagination links, so scrolling a
  300-sheet set feels continuous.
- Each tile shows the sheet number badge, title, a revision pill when more than one revision
  exists, and a markup count badge.

Natural sheet-number ordering matters: a plain string sort puts `A-10` before `A-2`. Sorting uses
a computed `sort_index` populated during matching, not a raw string sort.

### `Livewire/Sheets/Viewer`

Full-page route, deep-linkable:

```php
Route::livewire('projects/{project}/plans/{sheet}', Viewer::class)->name('plans.sheets.show');
```

Three-region layout:

- **Left rail** - virtualized thumbnail strip of sibling sheets, current sheet highlighted,
  wrapped in `@island` so rail interaction never re-renders the canvas.
- **Center** - the canvas stage. Wheel and trackpad zoom toward the cursor, drag to pan,
  double-click to zoom, fit-width and fit-page buttons, rotation control.
- **Right panel** - tabbed: Details, Revisions, Markup, Links. Loads `lazy`.

Toolbar: zoom controls, rotate, markup tools, revision selector, compare button, download,
export, and a full-screen toggle.

Keyboard shortcuts:

| Key | Action |
| --- | --- |
| Left / Right, PgUp / PgDn | Previous / next sheet |
| `+` / `-` / `0` | Zoom in / out / fit |
| `Cmd`+`K` or `Ctrl`+`K` | Sheet jump palette |
| `F` | Full screen |
| `M` | Toggle markup layer |
| `C` | Compare mode |
| `Esc` | Exit tool or full screen |

### Sheet Jump Palette

A modal that filters sheets by number or title as you type, keyboard-navigable, with Enter to
navigate. Sheet list is passed once as a lightweight array so filtering is instant and
client-side. This is the primary navigation method for users who know the sheet they want.

### `Livewire/Sheets/Compare`

Covered in `07_FEATURES_REVISIONS_ANNOTATIONS_LINKS_COMPARE.md`.

## Canvas Implementation Notes

The stage is a container with a CSS `transform: translate(x, y) scale(z)` applied to a stack:

1. Base image layer - `<img>` showing thumbnail then preview, or a tile grid when tiling is on.
2. Link hotspot layer - absolutely positioned divs in normalized coordinates.
3. Annotation layer - a `<canvas>` sized to the base image, redrawn on transform change.
4. Active drawing layer - a second canvas for the in-progress stroke only, so the committed
   markup canvas is not cleared on every pointer move.

Using CSS transform for pan and zoom keeps it on the compositor and avoids re-rasterizing on every
frame. Annotation redraw is throttled with `requestAnimationFrame`.

All annotation geometry is converted to normalized 0..1 coordinates before persisting and back to
pixels on load, so markup survives DPI and preview-width changes.

## Styling

Tailwind v4 utilities, matching existing admin surfaces: `rounded-xl`, `border-zinc-200`,
`bg-white`, `shadow-sm`, with `dark:` variants throughout (`dark:border-zinc-700`,
`dark:bg-zinc-900`). Flux free-edition components for buttons, inputs, fields, dropdowns, modals,
badges, and tooltips. The viewer chrome uses the darker treatment already used by
`resources/views/components/ui/pdf-viewer.blade.php` so full-screen viewing does not glare.

Use `gap` utilities for sibling spacing rather than margins.

## Empty and Error States

| State | Treatment |
| --- | --- |
| No sets uploaded | Illustration plus primary upload action, permission-gated |
| Set processing | Progress bar, page counter, sheets appearing incrementally |
| Set failed | Error callout with `error_message` and a retry action |
| Individual page failed | Tile shows a warning badge and a per-page retry |
| No search results | "No sheets match" with a clear-filters action |
