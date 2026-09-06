# Plans Domain - Data Model

All tables use ULID primary keys and `foreignUlid` foreign keys, guarded by `Schema::hasTable`,
matching the pattern in `app/Domains/Stock/Database/Migrations/2026_03_27_180100_create_stock_orders_table.php`.

Migrations live in `app/Domains/Plans/Database/Migrations` and are loaded by `PlansServiceProvider`
via `loadMigrationsFrom`.

## Entity Relationships

```
Project 1---* PlanSet 1---* PlanSheetRevision *---1 PlanSheet 1---* PlanAnnotation
                                   |                     ^              |
                                   |                     |              *
                                   *---* PlanLink --------+       PlanAnnotationComment
Asset 1---* PlanSet (source PDF, via AssetReference 'plans')
```

A `PlanSheet` is the durable identity. A `PlanSheetRevision` is one page of one `PlanSet` bound to
that sheet. Exactly one revision per sheet carries `is_current = true`.

## 1. `plan_sets`

The uploaded delivery unit.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | ulid, pk | |
| `project_id` | foreignUlid -> projects | cascadeOnDelete |
| `name` | string | e.g. "Architectural - Permit Set" |
| `discipline` | string, nullable | Architectural, Structural, MEP |
| `issued_at` | date, nullable | Issue date printed on the set |
| `source_asset_id` | foreignUlid -> assets, nullable | nullOnDelete |
| `status` | string | `pending`, `splitting`, `rendering`, `ready`, `failed` |
| `page_count` | integer, default 0 | Total pages detected |
| `processed_page_count` | integer, default 0 | Incremented atomically for progress UI |
| `error_message` | text, nullable | Failure detail recorded by jobs |
| `uploaded_by_id` | foreignUlid -> users, nullable | nullOnDelete |
| timestamps, softDeletes | | |

Indexes: `[project_id, status]`, `[project_id, discipline]`, `created_at`.

## 2. `plan_sheets`

The durable sheet identity within a project.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | ulid, pk | |
| `project_id` | foreignUlid -> projects | cascadeOnDelete |
| `sheet_number` | string, nullable | `A-201`. Null until detected or set manually |
| `title` | string, nullable | "Second Floor Plan" |
| `discipline` | string, nullable | Denormalized from the set for filtering |
| `sort_index` | integer, default 0 | Manual ordering override |
| `current_revision_id` | ulid, nullable | Denormalized pointer for fast index queries |
| timestamps, softDeletes | | |

Indexes: unique `[project_id, sheet_number]`, plus `[project_id, discipline]` and
`[project_id, sort_index]`.

The unique index tolerates multiple nulls in both SQLite and MySQL, which is required because
sheets exist before detection runs. `PlanSheetMatcher` must still guard against races when
assigning a number.

## 3. `plan_sheet_revisions`

One page of one set, bound to a sheet.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | ulid, pk | |
| `plan_sheet_id` | foreignUlid -> plan_sheets | cascadeOnDelete |
| `plan_set_id` | foreignUlid -> plan_sets | cascadeOnDelete |
| `revision_label` | string, nullable | "Rev C", "Delta 2" |
| `page_number` | integer | 1-based page within the source PDF |
| `thumbnail_path` | string, nullable | Disk path to the ~320px webp |
| `preview_path` | string, nullable | Disk path to the ~2000px webp |
| `tile_manifest` | json, nullable | Zoom levels and tile grid when tiling is enabled |
| `width` | integer, nullable | Rendered preview pixel width |
| `height` | integer, nullable | Rendered preview pixel height |
| `rotation` | smallint, default 0 | 0/90/180/270 user correction |
| `text_layer` | longText, nullable | Extracted page text, used for search and detection |
| `detected_sheet_number` | string, nullable | Raw detection output |
| `detected_title` | string, nullable | Raw detection output |
| `detection_confidence` | decimal(5,4), nullable | 0..1 |
| `detection_source` | string, nullable | `text-layer`, `ocr`, `manual` |
| `status` | string | `pending`, `rendered`, `failed` |
| `error_message` | text, nullable | |
| `is_current` | boolean, default false | Exactly one true per sheet |
| `published_at` | timestamp, nullable | When it became current |
| timestamps | | |

Indexes: `[plan_sheet_id, is_current]`, `[plan_set_id, page_number]`, `status`.

Detection writes to `detected_*`. A user correction writes to `plan_sheets.sheet_number` with
`detection_source = 'manual'`, so re-running detection never clobbers a human decision.

## 4. `plan_annotations`

Vector markup in normalized page coordinates.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | ulid, pk | |
| `plan_sheet_id` | foreignUlid -> plan_sheets | cascadeOnDelete |
| `plan_sheet_revision_id` | ulid, nullable | Null means the markup follows the current revision |
| `author_id` | foreignUlid -> users, nullable | nullOnDelete |
| `type` | string | `pen`, `highlight`, `rect`, `ellipse`, `arrow`, `line`, `text`, `cloud`, `measure`, `stamp` |
| `geometry` | json | Points/bounds normalized 0..1 relative to page size |
| `style` | json | color, width, opacity, fontSize |
| `content` | text, nullable | Text body for text/stamp types |
| `status` | string, default `open` | `open`, `resolved` |
| `linked_task_id` | ulid, nullable | Optional soft link to a task |
| `visibility` | string, default `project` | `project` or `private` |
| timestamps, softDeletes | | |

Indexes: `[plan_sheet_id, status]`, `[plan_sheet_id, plan_sheet_revision_id]`, `author_id`.

Normalized coordinates are essential: markup stays correct when the same sheet is re-rendered at a
different DPI or the preview width setting changes.

`linked_task_id` is intentionally a plain ulid with no FK constraint. A hard FK would couple the
Plans domain schema to the Tasks domain, violating the domain boundary rule in `AGENTS.md`.
Resolution happens through a thin service call.

## 5. `plan_annotation_comments`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | ulid, pk | |
| `plan_annotation_id` | foreignUlid -> plan_annotations | cascadeOnDelete |
| `author_id` | foreignUlid -> users, nullable | nullOnDelete |
| `body` | text | |
| timestamps, softDeletes | | |

## 6. `plan_links`

Clickable hotspots that navigate to another sheet.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | ulid, pk | |
| `from_revision_id` | foreignUlid -> plan_sheet_revisions | cascadeOnDelete |
| `target_sheet_id` | foreignUlid -> plan_sheets | cascadeOnDelete |
| `hotspot` | json | Normalized `{x, y, w, h}` |
| `label` | string, nullable | Display label on hover |
| `auto_detected` | boolean, default false | Rendered dashed until confirmed |
| `created_by_id` | foreignUlid -> users, nullable | nullOnDelete |
| timestamps | | |

Indexes: `from_revision_id`, `target_sheet_id`.

## 7. `plan_view_states`

Resume-where-you-left-off, per user per sheet.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | ulid, pk | |
| `user_id` | foreignUlid -> users | cascadeOnDelete |
| `plan_sheet_id` | foreignUlid -> plan_sheets | cascadeOnDelete |
| `zoom` | decimal(8,4) | |
| `center_x` | decimal(8,6) | Normalized |
| `center_y` | decimal(8,6) | Normalized |
| `last_viewed_at` | timestamp | |

Unique: `[user_id, plan_sheet_id]`.

This table is written on a debounce from the client and is safe to truncate at any time.

## Models

All in `app/Domains/Plans/Models`, using `HasUlids`, `HasFactory`, and `SoftDeletes` where the
table has `deleted_at`. Casts go in a `casts()` method, per repo convention.

| Model | Notable relations and helpers |
| --- | --- |
| `PlanSet` | `project()`, `sourceAsset()`, `revisions()`, `uploadedBy()`, `isReady()`, `progressPercent()` |
| `PlanSheet` | `project()`, `revisions()`, `currentRevision()`, `annotations()`, `incomingLinks()`, `scopeForProject()` |
| `PlanSheetRevision` | `sheet()`, `set()`, `links()`, `annotations()`, `scopeCurrent()` |
| `PlanAnnotation` | `sheet()`, `revision()`, `author()`, `comments()`, `scopeOpen()` |
| `PlanAnnotationComment` | `annotation()`, `author()` |
| `PlanLink` | `fromRevision()`, `targetSheet()`, `createdBy()` |
| `PlanViewState` | `user()`, `sheet()` |

Casts of note: `geometry`, `style`, `hotspot`, and `tile_manifest` cast to `array`;
`is_current` and `auto_detected` to `boolean`; `issued_at` to `date`; `published_at` and
`last_viewed_at` to `datetime`.

## Factories and Seeder

Every model gets a factory. `PlanSheetRevisionFactory` needs a `rendered()` state that fills
`thumbnail_path`, `preview_path`, `width`, and `height` so UI tests do not need real image files.
A `PlansDemoSeeder` creates one ready set with ~12 sheets for local development.

## Query Performance Notes

- The sheet index must never lazy-load. Query
  `PlanSheet::with('currentRevision')->forProject($id)` and rely on `current_revision_id`.
- Annotation counts on the index use `withCount('annotations')`, not loaded relations.
- The thumbnail rail pages through revisions with `select()` limited to id, page_number,
  thumbnail_path, and sheet_number. Never `SELECT *` on a table holding `text_layer` longText.
