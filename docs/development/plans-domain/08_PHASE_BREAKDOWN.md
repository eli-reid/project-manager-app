# Plans Domain Implementation Checklist

Tracker for the Plans domain build. Follows the format of
`docs/development/DOCUMENTS_IMPLEMENTATION_CHECKLIST.md`.

Status: **Not started.**

## Scope Lock
- [ ] New bounded domain `app/Domains/Plans`, not an extension of Documents.
- [ ] Rasterizer swappable between Poppler and Imagick via settings.
- [ ] Sheet-number detection from the PDF text layer first; OCR deferred to Phase 10.
- [ ] Annotations stored as vector JSON overlay, burned in only on export.
- [ ] Mobile is online-only; no offline sheet pinning in v1.

## Confirmed Product Rules
- [ ] Source PDFs remain single `Asset` records; page derivatives are plain disk files.
- [ ] Derivatives are never served from the public disk.
- [ ] A sheet is a durable identity; re-uploading a set creates revisions, not duplicates.
- [ ] Exactly one revision per sheet is current at any time.
- [ ] Superseded revisions remain viewable and downloadable.
- [ ] Annotation geometry is normalized 0..1 so markup survives re-rendering.
- [ ] Markup defaults to following the sheet, with an opt-in pin to a specific revision.
- [ ] Auto-detected links render dashed until a user confirms them.

## Phase 0 - Rasterizer and Infrastructure (blocking gate)
- [ ] Add `PlanRasterizerContract`.
- [ ] Add `PopplerRasterizer` using `Process` with array arguments and an explicit timeout.
- [ ] Add `ImagickRasterizer` with a PDF delegate availability check.
- [ ] Add `NullRasterizer` bound in the testing environment.
- [ ] Add `PlanRasterizerManager` selecting the driver from settings.
- [ ] Add `plans:rasterizer-check` Artisan command.
- [ ] Verify both drivers on the Windows/Herd dev machine.
- [ ] Verify at least one driver on the target production host.
- [ ] Unit tests for driver selection and availability reporting.

**Gate: do not start Phase 2 until a 50-page PDF renders page 1 under both drivers.**

## Phase 1 - Domain Scaffold and Data Layer
- [ ] Create `PlansServiceProvider` with migrations, views, Livewire namespace, and routes.
- [ ] Add `PlanPermissions` and register via DI in `boot`.
- [ ] Add `app/Domains/Plans/config/settings.php` and register it.
- [ ] Add all seven migrations with ULID keys and composite indexes.
- [ ] Add models with `casts()` methods and relationships.
- [ ] Add factories, including a `rendered()` state for revisions.
- [ ] Add `PlansDemoSeeder`.
- [ ] Add `PlanSetPolicy`, `PlanSheetPolicy`, `PlanAnnotationPolicy`, `PlanLinkPolicy`.
- [ ] Register `PlanAssetAccessResolver` as the `plans` referencer.
- [ ] Tests: provider bindings, permission registration, settings registration, policy matrix.

## Phase 2 - Ingestion and Render Pipeline
- [ ] Add `PlanSetIngestionService`.
- [ ] Add `SplitPlanSetJob` with a max-pages guard.
- [ ] Add `RenderPlanPageJob` with tries, backoff, timeout, and `ShouldBeUnique`.
- [ ] Add `FinalizePlanSetJob` and `PlanSheetMatcher`.
- [ ] Add atomic `processed_page_count` progress tracking.
- [ ] Add `PlanImageController` with policy checks, ETag, and immutable cache headers.
- [ ] Add derivative routes for thumb, preview, and tile variants.
- [ ] Add `PurgePlanDerivativesJob` and `plans:prune-derivatives`.
- [ ] Tests: full pipeline with `NullRasterizer`, failure recording, max-pages guard,
      unauthorized derivative access returns 403, missing file returns 404.

## Phase 3 - Sheet Index UI (Web)
- [ ] Add `PlansProjectTab` in the Plans domain.
- [ ] Remove `PlansProjectTab` from `DocumentsServiceProvider::registerProjectTabs()`.
- [ ] Add `Livewire/Admin/Projects/PlansTab` with the upload panel.
- [ ] Add processing progress with polling that stops at `ready` or `failed`.
- [ ] Add `Livewire/Sheets/Index` with grid and list modes.
- [ ] Add search, discipline and set filters, and natural sheet-number sorting.
- [ ] Add infinite scroll via `wire:intersect`.
- [ ] Add empty, processing, and failed states.
- [ ] Tests: index rendering, search, permission-gated upload, registry contains one `plans` key.

## Phase 4 - Sheet Viewer (Web)
- [ ] Add the full-page viewer route with `Route::livewire(...)`.
- [ ] Add the Alpine canvas stage with CSS-transform pan and zoom.
- [ ] Add the thumbnail rail as an `@island`.
- [ ] Add the right panel with lazy loading.
- [ ] Add keyboard shortcuts and the sheet jump palette.
- [ ] Add neighbor preloading and progressive thumbnail-to-preview swap.
- [ ] Add `plan_view_states` resume with debounced persistence.
- [ ] Tests: Livewire viewer state, plus a browser test for zoom and next-sheet with
      `assertNoJavaScriptErrors()`.

## Phase 5 - Metadata Extraction and Matching
- [ ] Add `ExtractSheetMetadataJob` using `smalot/pdfparser`.
- [ ] Add title-block region filtering and candidate scoring.
- [ ] Add configurable sheet-number pattern.
- [ ] Add manual override UI for sheet number and title.
- [ ] Add bulk renumber and reorder tools.
- [ ] Ensure manual edits set `detection_source = 'manual'` and survive re-detection.
- [ ] Tests: extraction from a fixture PDF, matcher creates a revision rather than a duplicate
      sheet, manual override wins over detection.

## Phase 6 - Revisions and Comparison
- [ ] Add the revision history panel.
- [ ] Add publish and roll back current revision, gated on `plans.publish-revision`.
- [ ] Add revision pills on the sheet index.
- [ ] Add `Livewire/Sheets/Compare`.
- [ ] Add side-by-side, overlay, and difference modes.
- [ ] Add synchronized pan and zoom with a lock toggle.
- [ ] Tests: publishing flips `is_current` for exactly one revision, compare permission gating,
      browser test of all three compare modes.

## Phase 7 - Annotations
- [ ] Add the canvas drawing layer and active-stroke layer.
- [ ] Add the desktop tool set.
- [ ] Add normalized geometry conversion both directions.
- [ ] Add stroke simplification for pen.
- [ ] Add async renderless persistence with unsynced-state handling.
- [ ] Add the layers and filter panel.
- [ ] Add resolve and reopen.
- [ ] Add threaded comments.
- [ ] Add measure calibration, disabled until calibrated.
- [ ] Add optional `linked_task_id` via a thin cross-domain service call.
- [ ] Tests: authorization matrix for author versus manager, geometry round-trip,
      markup follows the sheet across revisions, browser test of draw, persist, and reload.

## Phase 8 - Links
- [ ] Add the manual hotspot editor.
- [ ] Add `DetectPlanLinksJob`.
- [ ] Add the hotspot overlay with hover labels.
- [ ] Add navigation with a client-side sheet history stack.
- [ ] Add confirm action for auto-detected links.
- [ ] Tests: link CRUD authorization, auto-detected candidates flagged, navigation resolves the
      correct target.

## Phase 9 - Mobile Views
- [ ] Add `Livewire/Mobile/Sheets/Index`.
- [ ] Add `Livewire/Mobile/Sheets/Viewer`.
- [ ] Add pinch, double-tap, and drag gestures with Pointer Events.
- [ ] Add swipe navigation restricted to the fit-zoom state.
- [ ] Add the bottom toolbar and reduced markup set.
- [ ] Add device-appropriate derivative sizing and DPR capping.
- [ ] Add mobile routes and the `plans.` prefix mapping.
- [ ] Confirm no `public/sw.js` changes were introduced.
- [ ] Tests: mobile component tests, route mapping, iPhone-viewport browser tests.

## Phase 10 - OCR, Export, and Polish
- [ ] Add `SheetMetadataExtractorContract` with the same swappable driver pattern.
- [ ] Add `TesseractOcrExtractor` as a fallback when the text layer is empty.
- [ ] Add PDF export with burned-in markup.
- [ ] Add batch sheet download.
- [ ] Add tile generation for very large sheets.
- [ ] Add `plans:import-legacy` for existing Plans-folder documents.
- [ ] Performance pass on a 300+ page set.
- [ ] Remove the `plans.enabled` dark-launch flag once stable.

## Deferred Backlog
- [ ] Offline sheet pinning with IndexedDB tile caching and sync-on-reconnect.
- [ ] Real-time multi-user annotation presence.
- [ ] Deep RFI and punch-list integration.
- [ ] DWG, DXF, and Revit ingestion.
- [ ] Automatic revision-cloud detection and change highlighting.
- [ ] Sheet-level e-signature and approval workflows.
- [ ] Per-sheet access restrictions below project level.
