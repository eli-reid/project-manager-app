# Plans Domain - Revisions, Annotations, Links, Comparison

The four headline features, in detail.

## 1. Revisions

### Model

A `PlanSheet` is a durable identity. Each `PlanSheetRevision` is one issued version. Exactly one
revision per sheet has `is_current = true`.

### How A Revision Is Created

Uploading a new plan set runs the normal pipeline. In `FinalizePlanSetJob`, `PlanSheetMatcher`
compares each page's `detected_sheet_number` against existing sheets in the project:

- **Match** - re-parent the revision to the existing sheet, set `is_current = true`, demote the
  previous current revision, stamp `published_at`. History accumulates.
- **No match** - keep the placeholder sheet and adopt the detected number as a new sheet.

Matching runs per sheet inside a transaction with `lockForUpdate` on the sheet row, so two sets
finishing simultaneously cannot both claim current.

### UI

The Revisions tab in the viewer's right panel lists every revision newest first, showing revision
label, source set name, issue date, and page number, with the current one marked. Users holding
`plans.publish-revision` can promote an older revision back to current - useful when a bad set is
uploaded by mistake.

Superseded revisions remain viewable and downloadable. Nothing is destroyed.

Sheets with more than one revision show a revision pill on the index so a field user can see at a
glance that a drawing has been reissued.

### Annotation Behavior Across Revisions

`plan_annotations.plan_sheet_revision_id` is nullable and that is deliberate:

- **Null** - markup follows the sheet and appears on whatever revision is current. Correct for
  a persistent note like "verify dimension with structural".
- **Set** - markup is pinned to that specific revision. Correct for a markup that describes
  something only true on that issue.

The markup tool defaults to sheet-following. A "pin to this revision" toggle sets the id.

## 2. Annotations

### Storage

Vector JSON, never burned into the PDF. Geometry is normalized 0..1 relative to page dimensions,
so markup stays aligned when DPI or preview width settings change.

```json
{
  "type": "arrow",
  "geometry": { "points": [[0.412, 0.233], [0.508, 0.301]] },
  "style": { "color": "#ef4444", "width": 3, "opacity": 1 }
}
```

Freehand pen strokes are simplified with Ramer-Douglas-Peucker before persisting to keep payloads
small on a 400-point stroke.

### Tool Set

| Tool | Desktop | Mobile | Notes |
| --- | --- | --- | --- |
| Pen | yes | yes | Freehand, simplified on save |
| Highlight | yes | no | Multiply blend |
| Rectangle | yes | no | |
| Ellipse | yes | no | |
| Arrow | yes | yes | |
| Line | yes | no | |
| Text | yes | yes | Inline editor |
| Cloud | yes | yes | Revision cloud, the construction convention |
| Measure | yes | no | Needs a per-sheet scale calibration |
| Stamp | yes | no | Approved, Rejected, Reviewed |

Measure requires calibration: the user draws a line on a known dimension and enters its real
length, stored per sheet. Without calibration the tool is disabled rather than reporting a wrong
number - a wrong measurement on a construction drawing is worse than no measurement.

### Layers Panel

Filter visible markup by author, type, status, and revision. Toggle the whole markup layer with
`M`. Counts are shown per author so a user can isolate their own notes.

### Lifecycle

Markup is `open` or `resolved`. Resolving keeps it visible but dimmed, and it can be reopened.
Threaded comments hang off each annotation via `plan_annotation_comments`.

An optional `linked_task_id` associates markup with a task. Stored as a plain ulid with no foreign
key constraint, because a hard FK would couple the Plans schema to the Tasks domain and violate the
boundary rule. Resolution happens through a thin service call.

### Authorization

Create requires `plans.annotate`. Editing or deleting requires being the author, or holding
`plans.manage-annotations`. Enforced in `PlanAnnotationPolicy`, not in the component.

### Persistence Flow

The client commits a stroke locally and immediately draws it, then fires an async renderless
Livewire action to persist. Failure surfaces a toast and marks the stroke as unsynced rather than
silently dropping it. The canvas is never re-rendered from the server response.

## 3. Links

Fieldwire's most-used navigation feature: tapping a detail callout jumps to the referenced sheet.

### Manual Links

With `plans.manage-links`, a user draws a rectangle on the sheet, picks a target sheet from the
jump palette, and optionally labels it. Stored in `plan_links` with a normalized hotspot.

### Auto-Detected Links

`DetectPlanLinksJob` scans the page text layer for tokens matching the sheet-number pattern that
also correspond to a real sheet in the project. Each becomes a `plan_links` row with
`auto_detected = true`.

Auto-detected hotspots render dashed and are functional but visually distinct. Confirming one
clears the flag. This avoids both the "silently wrong links" problem and the "hundreds of links to
review before any work" problem.

### Viewer Behavior

Hotspots render as an absolutely positioned overlay above the image and below the markup canvas.
Hovering shows the label and target sheet number. Clicking navigates with `wire:navigate`.

A client-side sheet history stack powers back and forward navigation, so a user who follows three
callouts can walk back out. This is separate from browser history to keep it fast and predictable.

## 4. Comparison

### Modes

| Mode | Behavior |
| --- | --- |
| Side by side | Two panes, synchronized pan and zoom |
| Overlay | B stacked on A with an opacity slider |
| Difference | `mix-blend-mode: difference`, A tinted red and B tinted blue so changes glow |

Difference mode is the one that finds real changes fast. Identical regions go black; anything that
moved shows as colored ghosting.

### Implementation

Entirely client-side compositing of two preview images. No server-side diff job in v1.

This is a deliberate simplification. Pixel-diffing on the server would require aligning sheets that
may be rendered at different sizes, handling rotation, and storing diff images - substantial work
for a marginal gain over blend-mode compositing, which is instant and needs no storage.

### Selection

Compare defaults to the current revision against the immediately previous one, which is the common
case. Users can pick any two revisions of the same sheet, or two different sheets entirely - useful
for comparing an architectural sheet against the structural sheet for the same area.

Requires `plans.compare`.

### Sync Behavior

In side-by-side, pan and zoom on either pane drives the other through a shared transform state.
A lock toggle allows independent movement when comparing different sheets whose content is not
aligned.
