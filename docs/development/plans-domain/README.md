# Plans Domain (Fieldwire-Style Plan Room) - Spec Index

Canonical specification set for the `app/Domains/Plans` bounded domain: splitting uploaded PDF
drawing sets into individually addressable sheets with a fast viewer, sheet index, revisions,
annotations, links, and comparison across desktop and mobile.

Status: **Draft - not yet implemented.**

## Read In This Order

| Doc | Purpose |
| --- | --- |
| `01_OVERVIEW_AND_DECISIONS.md` | Problem statement, locked decisions, scope boundaries, glossary |
| `02_DATA_MODEL.md` | All tables, columns, indexes, models, relationships |
| `03_RASTERIZER_AND_PIPELINE.md` | Swappable rasterizer drivers, queued split/render pipeline |
| `04_AUTHORIZATION_AND_SETTINGS.md` | Permissions, policies, asset resolver, domain settings |
| `05_UI_WEB.md` | Desktop sheet index, viewer, performance rules |
| `06_UI_MOBILE.md` | Mobile index and viewer, gestures, route mapping |
| `07_FEATURES_REVISIONS_ANNOTATIONS_LINKS_COMPARE.md` | The four headline features in detail |
| `08_PHASE_BREAKDOWN.md` | Phase-by-phase implementation checklist (the tracker) |
| `09_TESTING_AND_VERIFICATION.md` | Test strategy, gates, known repo test caveats |
| `10_MIGRATION_AND_ROLLOUT.md` | Legacy import, feature flag, deployment prerequisites |

## One-Paragraph Summary

Uploaded drawing sets remain single PDFs in `Core\Assets`. A queued pipeline rasterizes each page
into thumbnail and preview images stored as plain disk files (not `Asset` rows), recorded against
`plan_sheet_revisions`. Sheets are stable identities keyed by sheet number, so re-uploading a set
creates revision history rather than duplicates. The viewer does all pan, zoom, and drawing
client-side in Alpine and canvas, touching Livewire only to persist. Rasterization sits behind a
driver contract so Poppler and Imagick are interchangeable per environment.

## Hard Prerequisite

Phase 0 is a genuine gate. If neither Poppler nor Imagick can be installed on the production host,
the server-render design does not work and the fallback is client-side PDF.js with no persisted
thumbnails. Do not begin Phase 2 until `php artisan plans:rasterizer-check` passes on the target
server.
