# Plans Domain - Migration and Rollout

## Deployment Prerequisites

The rasterizer needs a native dependency. This must be resolved before Phase 2 and verified on
every environment.

### Poppler (preferred)

| Environment | Install |
| --- | --- |
| Linux (Debian/Ubuntu) | `apt-get install poppler-utils` |
| Linux (RHEL/Alma) | `dnf install poppler-utils` |
| Windows dev (Herd) | Download a poppler build, extract, set `plans.poppler_bin_path` to its `bin` directory |
| Docker | Add `poppler-utils` to the image build |

Provides `pdftoppm` and `pdfinfo`.

### Imagick (alternative)

Requires the `imagick` PHP extension plus Ghostscript. On hardened hosts, ImageMagick's
`policy.xml` commonly disables the PDF delegate with a line like:

```xml
<policy domain="coder" rights="none" pattern="PDF" />
```

This must be relaxed to `rights="read"` or PDF rendering silently fails.
`plans:rasterizer-check` detects this condition explicitly rather than reporting a generic error.

### Shared hosting note

If the host allows neither Poppler nor Imagick with a PDF delegate, the server-render design is
not viable and the fallback is client-side PDF.js with no persisted thumbnails, no OCR source
images, and degraded behavior on large sets. Confirm this before committing to Phase 2.

## Feature Flag

`plans.enabled` defaults to `false`. While off:

- The new Plans project tab is hidden.
- Plans routes return 404.
- The existing Documents-based Plans tab remains registered and untouched.

This allows the domain to ship to production incrementally without user-visible change. The flag
is removed in Phase 10 once the feature is stable.

## Tab Cutover

The single riskiest moment in the rollout.

`app/Domains/Documents/Support/PlansProjectTab.php` and the new
`app/Domains/Plans/Support/PlansProjectTab.php` both use the tab key `plans`. Registering both
collides in `ProjectTabRegistry`.

Cutover sequence:

1. Ship the Plans domain with `plans.enabled = false`. Do not register the new tab yet.
2. Verify the pipeline in production against a test project.
3. In one change: remove `PlansProjectTab::class` from
   `DocumentsServiceProvider::registerProjectTabs()` and add it to
   `PlansServiceProvider::registerProjectTabs()`.
4. Enable `plans.enabled`.

Keep the Documents-domain tab class file until the next release rather than deleting it in the
same change, so a rollback is a one-line revert.

Add a test asserting the registry contains exactly one `plans` key. This is cheap and would catch
the collision before it reaches production.

## Legacy Import

Existing plans are `Document` records whose `folder_path` starts with `Plans`, created by
`app/Domains/Documents/Livewire/Admin/Projects/PlansTab.php`.

`php artisan plans:import-legacy {--project=} {--dry-run}`:

1. Finds project-owned documents in the `Plans` folder tree.
2. Creates a `PlanSet` per document, reusing the existing `asset_id` and adding a `plans`
   `AssetReference` alongside the existing `documents` one. The asset system supports multiple
   references per asset by design, so no file is copied.
3. Maps the document's plan-set folder segment to `PlanSet.discipline`.
4. Queues the normal split and render pipeline.

The original `Document` rows are left intact. Nothing is deleted or moved. If the import is wrong,
the fix is to delete the `PlanSet` rows and re-run.

Run with `--dry-run` first and review the summary. Import one project before running fleet-wide.

## Queue Capacity

Rendering is the heaviest background work this application will do. A 400-page set produces 400
`RenderPlanPageJob` executions, each spawning a subprocess or Imagick operation.

Considerations:

- The default queue connection is `database`. Ensure `retry_after` in `config/queue.php` exceeds
  the job `timeout` of 300s, otherwise jobs will be picked up twice while still running. The
  current default of 90 is **too low** and must be raised.
- Consider a dedicated `plans` queue so a large set does not starve notifications and other
  latency-sensitive jobs.
- In development, `composer dev` already runs `queue:listen`.
- In production, confirm a worker is supervised and monitor depth during the first large upload.

## Storage Growth

Derivatives materially increase storage use. A rough estimate per page at the default settings is
a few hundred KB for the preview plus a small thumbnail. A 400-page set can therefore add on the
order of 100 MB.

Plan for this:

- Monitor disk usage after the first production sets.
- `plans.derivative_format = webp` keeps files substantially smaller than PNG.
- Lower `plans.preview_width` if storage is constrained; the setting exists for this reason.
- `plans:prune-derivatives` reclaims orphaned directories.
- If moving to S3, set `plans.storage_disk` accordingly and confirm the delivery controller still
  streams through the application rather than issuing public URLs.

## Rollback Plan

| Situation | Action |
| --- | --- |
| Pipeline failing | Set `plans.enabled = false`. The old Documents tab is unaffected. |
| Tab collision | Revert the one-line registry change. |
| Rasterizer broken after a host change | Switch `plans.rasterizer_driver` to the other driver. No deploy needed. |
| Storage pressure | Lower `plans.preview_width`, disable tiles, run the prune command. |
| Bad legacy import | Delete the created `PlanSet` rows. Source documents and assets are untouched. |

Because the source PDFs are never modified and the original `Document` rows are preserved, no
rollback path risks data loss.

## Post-Launch Monitoring

- Failed `RenderPlanPageJob` count and the `plan_sheet_revisions.status = failed` count.
- Sets stuck in `rendering` beyond a reasonable window.
- Sheet-number detection hit rate, which drives whether the Phase 10 OCR work is justified.
- Derivative delivery 403 rate, which would indicate a policy misconfiguration.
- Viewer JavaScript errors via existing browser log tooling.
