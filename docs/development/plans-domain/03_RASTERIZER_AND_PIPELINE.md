# Plans Domain - Rasterizer and Processing Pipeline

## Why A Driver Abstraction

Rendering PDF pages to images requires a native binary or PHP extension. Availability differs
between the Windows/Herd development machine and the Linux production host. Locking the codebase
to one implementation would make the feature undeployable if that dependency is unavailable.

The contract also makes the pipeline testable: a `NullRasterizer` is bound in the testing
environment so the entire split/render flow runs in CI with no binaries installed.

## `PlanRasterizerContract`

`app/Domains/Plans/Contracts/PlanRasterizerContract.php`

```php
interface PlanRasterizerContract
{
    public function pageCount(string $absolutePdfPath): int;

    public function renderPage(
        string $absolutePdfPath,
        int $page,
        int $dpi,
        string $absoluteOutPath,
    ): void;

    public function isAvailable(): bool;

    public function name(): string;
}
```

## Drivers

All in `app/Domains/Plans/Services/Rasterizers/`.

### `PopplerRasterizer`

Shells out to `pdfinfo` and `pdftoppm` using `Symfony\Component\Process\Process`.

- `pageCount` runs `pdfinfo <file>` and parses the `Pages:` line.
- `renderPage` runs `pdftoppm -r {dpi} -f {page} -l {page} -png -singlefile <in> <outPrefix>`.
- Binary directory comes from the `plans.poppler_bin_path` setting.
- **Security requirement:** always construct `Process` with an array of arguments. Never build a
  shell string with interpolated paths. Set an explicit timeout so a malformed PDF cannot hang a
  worker indefinitely.

### `ImagickRasterizer`

Uses the `ext-imagick` PHP extension backed by Ghostscript.

- `pageCount` uses `Imagick::getNumberImages()` after `pingImage`.
- `renderPage` sets resolution before `readImage("{$path}[{$index}]")`, flattens alpha against
  white, and writes the target format.
- `isAvailable()` must check both `extension_loaded('imagick')` and that the `PDF` delegate is
  readable, because Ghostscript policy files commonly block PDF on hardened servers.

### `NullRasterizer`

Writes a small deterministic placeholder image and returns a fixed page count. Bound when
`App::environment('testing')`.

## `PlanRasterizerManager`

Extends Laravel's `Manager`. `getDefaultDriver()` reads the `plans.rasterizer_driver` setting.
The provider binds the contract to the resolved driver:

```php
$this->app->bind(
    PlanRasterizerContract::class,
    fn ($app) => $app->make(PlanRasterizerManager::class)->driver(),
);
```

Switching engines is therefore a settings change, not a deployment.

## `plans:rasterizer-check`

An Artisan command that reports availability and version for every driver, plus the currently
selected one. Used as the Phase 0 gate and for post-deploy verification. Its output is surfaced
read-only in the Plans settings screen so an admin can diagnose a broken host without SSH.

## Processing Pipeline

Entry point: `PlanSetIngestionService::ingest(Project $project, User $actor, UploadedFile $file, array $attributes): PlanSet`

1. Upload the source PDF through `AssetOrchestratorContract::upload()` with
   `new AssetReferenceTarget('plans', $planSet->id, 'source')`.
2. Create the `plan_sets` row with `status = pending`.
3. Dispatch `SplitPlanSetJob`.

All jobs live in `app/Domains/Plans/Jobs`, use the
`Dispatchable, InteractsWithQueue, Queueable, SerializesModels` trait set with promoted
constructor properties, and inject services into `handle()` - matching
`app/Domains/Invoices/Jobs/ProcessInvoicePdfJob.php`.

### `SplitPlanSetJob`

- Resolves page count via the rasterizer.
- Rejects sets exceeding the `plans.max_pages_per_set` guard, recording a failure.
- Creates one placeholder `plan_sheets` row and one `plan_sheet_revisions` row per page.
- Builds `Bus::batch([...RenderPlanPageJob])->then(FinalizePlanSetJob)->catch(...)`.
- Sets `status = rendering`.

### `RenderPlanPageJob`

One job per page - the unit of retry and parallelism.

- `public int $tries = 3;`
- `public function backoff(): array { return [10, 30, 60]; }`
- `public int $timeout = 300;`
- Implements `ShouldBeUnique` keyed on the revision id.
- Renders the thumbnail and preview, optionally tiles, then writes paths, width, height, and
  `status = rendered`.
- Increments `processed_page_count` atomically for the progress UI.
- On `Throwable`, records `status = failed` plus `error_message` on the revision, matching the
  repo's status-column failure pattern.

### `ExtractSheetMetadataJob`

- Pulls page text via `smalot/pdfparser`.
- Filters text objects to the title-block region, configurable via `plans.title_block_region`
  and defaulting to the bottom-right quadrant.
- Scores sheet-number candidates against a pattern such as
  `^[A-Z]{1,3}[-.]?\d{1,3}(\.\d+)?$`, preferring the largest text nearest the corner.
- Writes `detected_sheet_number`, `detected_title`, `detection_confidence`, and
  `detection_source = 'text-layer'`.

### `FinalizePlanSetJob`

Runs `PlanSheetMatcher` over the whole set:

- If `detected_sheet_number` matches an existing sheet in the project, re-parent the revision to
  that sheet, mark it current, and demote the previous current revision. This is what turns a
  re-upload into revision history.
- Otherwise keep the newly created placeholder sheet and adopt the detected number.
- Sets `plan_sets.status = ready` and fires a `PlanSetReady` event.

Matching runs in a transaction per sheet with a `lockForUpdate` on the target sheet row to avoid a
race when two sets finish simultaneously.

## Progress Reporting

The Plans tab polls with `wire:poll.2s` **only** while status is `pending`, `splitting`, or
`rendering`, and stops once `ready` or `failed`. Progress is
`processed_page_count / page_count`. Sheets appear in the index incrementally as they render
rather than waiting for the whole set.

## Derivative Storage Layout

```
plans/{project_id}/{plan_set_id}/{page_number}/thumb.webp
plans/{project_id}/{plan_set_id}/{page_number}/preview.webp
plans/{project_id}/{plan_set_id}/{page_number}/tile_{z}_{x}_{y}.webp
```

Disk is chosen by the `plans.storage_disk` setting, defaulting to `local`
(`storage/app/private`). The public disk must never be used.

## Derivative Delivery

`PlanImageController`, modeled on `app/Core/Assets/Http/Controllers/AssetDeliveryController.php`:

- Authorizes via `PlanSheetPolicy` before streaming anything.
- Returns 404 when the file is missing, 403 when unauthorized.
- Sends `Cache-Control: private, max-age=31536000, immutable` plus a strong `ETag`, because a
  derivative path never changes content once written.
- Supports the `preview`, `thumb`, and `tile` variants through one route with a validated
  variant parameter.

## Cleanup

Deleting a `PlanSet` soft-deletes the row and queues `PurgePlanDerivativesJob` to remove the
directory tree and release the source `AssetReference`. A scheduled
`plans:prune-derivatives` command removes orphaned directories older than the retention window,
mirroring `PruneInvoicePdfImportsCommand`.
