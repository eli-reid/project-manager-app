# Assets Architecture and Documents Migration

## Status
- Draft v1
- Scope: `app/Core/Assets` + `app/Domains/Documents` + all future file-storing domains
- Supersedes: `DOCUMENT_SYSTEM_DESIGN_SPEC.md` (branch `bn`)

## Problem

The application currently has two parallel file systems:

1. **`Domains/Documents`** (in production). Owns `documents`, `document_shares`,
   `document_internal_shares`. Calls `Storage::disk()` and `$file->store()` directly.
   Has real authorization (`DocumentPolicy`), real sharing (password, expiry,
   download caps), and Settings-driven configuration.
2. **`Core/Assets`** (partially built). Owns `assets`, `asset_shares`. Has clean
   storage contracts but no ownership, no authorization, no working share service,
   and hardcoded validation rules.

Neither is a superset of the other. Cutting over to Assets as it stands would drop
authorization entirely.

## Design Direction

Assets is **not** a thin wrapper around `Storage`. That would be redundant with
Laravel's filesystem and would not earn its own domain.

Assets owns what `Storage` cannot do:

- **File identity** — a stable ID for a blob, referenceable by many domains.
- **Deduplication** — identical content stored once, via `content_hash`.
- **Access-controlled delivery** — one streaming/download/preview route for the
  entire application.
- **Share links** — token, expiry, password, download caps and counters.
- **Lifecycle** — retention, expiry, orphan collection.
- **Audit** — who accessed which blob, when.

Assets owns the **mechanism**. Owning domains own the **policy**. Assets never
decides whether a user may see a file; it asks the domain that owns the reference.

## Boundaries

### `Core/Assets` owns
- The `assets` table (blob facts only — no business metadata, no owner columns).
- The `asset_references` table (the ownership edge).
- The `asset_shares` table (public share links).
- Storage adapters, path normalization, content hashing, dedupe.
- The single delivery route for downloads, previews, and share links.
- Signed and temporary URL generation.
- Default upload validation rules.
- The resolver registry.

### Every consuming domain owns
- Its own metadata table holding an `asset_id`.
- Its `AssetAccessResolver` implementation (typically delegating to its policy).
- Its own UI, filters, and business rules.
- Any domain-specific override of upload validation rules.

### `Domains/Documents` becomes a consumer
It stops being a second file system and becomes the "user and project document
library" feature.

**Keeps:** `title`, `description`, `owner_scope`, `owner_id`, `visibility`,
`folder_path`, `replace_mode`, soft deletes, `document_internal_shares`,
`DocumentPolicy`, `DocumentPermissions`, and all `documents.*` settings.

**Gives up to Assets:** `storage_path`, `storage_disk`, `stored_name`,
`mime_type`, `file_size`, `extension`, and `document_shares`.

## Data Model

### `assets`
The blob. Has no owner.

```
id                ulid, pk
original_name     string
mime_type         string, nullable
size_bytes        unsigned big int, nullable
storage_disk      string
storage_path      string
content_hash      string, nullable
created_by_id     foreign ulid -> users, nullOnDelete
timestamps

unique (storage_disk, content_hash)
```

The unique constraint is what makes dedupe possible: re-uploading identical
content on the same disk returns the existing row instead of writing a second copy.

### `asset_references`
Who points at a blob. This is the ownership edge, and the unit of authorization.

```
id                big increments
asset_id          ulid -> assets, cascade delete
referencer_type   string   (registry key, NOT a class name)
referencer_id     string
role              string, nullable ('primary', 'thumbnail', 'attachment')
created_by_id     foreign ulid -> users, nullOnDelete
timestamps

unique (asset_id, referencer_type, referencer_id, role)
index  (referencer_type, referencer_id)
```

`referencer_type` is a stable registry key such as `documents`, `submittal`, or
`payroll-profile`. It is deliberately not a fully-qualified class name so that
namespace refactors do not invalidate stored rows.

### `asset_shares`
Absorbs the full behaviour of the current `document_shares` table.

```
id                big increments
asset_id          ulid -> assets, cascade delete
token             string, unique
password_hash     string, nullable
expires_at        timestamp, nullable, indexed
max_downloads     unsigned int, nullable
download_count    unsigned int, default 0
is_active         boolean, default true
access_notes      text, nullable
created_by_id     foreign ulid -> users, nullOnDelete
timestamps
soft deletes
```

## Authorization

An asset has no intrinsic owner — it has references. Therefore:

> A user may access an asset if **any** resolver for **any** of its references
> grants access.

### The contract

```php
interface AssetAccessResolver
{
    public function canView(User $user, Asset $asset, AssetReference $reference): bool;

    public function canDownload(User $user, Asset $asset, AssetReference $reference): bool;

    public function canShare(User $user, Asset $asset, AssetReference $reference): bool;
}
```

### The gatekeeper

`AssetGatekeeper` loads an asset's references, resolves each `referencer_type`
against the registry, and short-circuits on the first grant. **Deny by default**
when no reference resolves to a registered resolver.

This is what makes dedupe safe. Two domains sharing one physical blob never leak
access to each other, because access is evaluated per reference, not per blob.

### Registration

Domains register their resolver in their service provider:

```php
AssetReferencerRegistry::register('documents', DocumentAssetAccessResolver::class);
```

`DocumentAssetAccessResolver` delegates to the existing `DocumentPolicy`, so all
current logic — including `document_internal_shares` grants — carries over
unchanged.

## Delivery

One set of routes for the whole application, replacing the download handlers
currently duplicated across the Documents domain's `web.php`, `mobile.php`, and
`public-sharing.php`.

```
GET /assets/{asset}          gatekeeper -> stream download
GET /assets/{asset}/preview  gatekeeper -> inline, range-request capable
GET /s/{token}               share link, unauthenticated, share rules only
```

## Validation

Upload constraints stay **per-referencer**. Payroll profile photos must not
accept `.docx` merely because the documents library does.

- `Core/Assets` exposes a conservative default via `validationRules()`.
- Each domain may override with its own rules.
- Documents continues to read `documents.allowed_types` and
  `documents.max_file_size` from Settings.

## Migration Phases

### Phase 1 — Foundation (additive, no risk)
- Port `Core/Assets` from branch `bn` to `main`.
- Add `content_hash` and the dedupe constraint.
- Add `asset_references` table and model.
- Build `AssetReferencerRegistry`, `AssetAccessResolver`, `AssetGatekeeper`.
- Build the delivery routes.
- Wire `validationRules()` to Settings instead of hardcoded values.
- Rewrite `AssetUpload` for Livewire 4 (`$this->dispatch()`, not the removed
  Livewire 2 `dispatchBrowserEvent()`).

### Phase 2 — Documents delegates (low risk)
- Add `documents.asset_id`.
- `DocumentService` injects `AssetOrchestratorContract`; upload, replace, move,
  and delete all route through it.
- `Document` proxies `storage_path`, `storage_disk`, `mime_type`, and `file_size`
  to its asset via accessors, so existing routes and Blade views keep working.
- Register `DocumentAssetAccessResolver`.

### Phase 3 — Backfill (reversible)
- Run `documents:migrate-assets` in **link mode**: create one `asset` row and one
  `asset_references` row per document, without moving files on disk.
- Physical re-ingest is a separate, optional step. Never run with `--delete-old`
  until link mode is verified in production.

### Phase 4 — Sharing (medium risk: public URLs change)
- Port `document_shares` rows into `asset_shares`.
- Repoint the public share route.
- Keep the old route as a redirect for one release.

### Phase 5 — Cleanup (point of no return)
- Drop `storage_path`, `storage_disk`, `stored_name`, `mime_type`, `file_size`,
  and `extension` from `documents`.
- Drop `document_shares`.
- Only after the full test suite is green.

### Phase 6 — Expansion (incremental)
Onboard remaining file consumers one at a time: Submittals (already pivots on
documents), payroll profile photos, avatars, invoice scans.

## Testing Strategy

- Contract binding tests for every Assets contract.
- Gatekeeper deny-by-default test: an asset with no registered resolver is denied.
- Dedupe isolation test: two domains referencing one blob, each denied the other's
  access path.
- Documents policy parity tests: the existing `DocumentPolicyMatrixTest` must pass
  unchanged after Phase 2.
- Backfill idempotency: running `documents:migrate-assets` twice creates no
  duplicate references.
- Share link behaviour parity: expiry, password, download cap, and counter.

## Risks

| Risk | Mitigation |
|---|---|
| Authorization regression during cutover | Deny by default; keep `DocumentPolicyMatrixTest` as the parity gate |
| Public share URLs break | Redirect old routes for one release |
| Dedupe leaks access across domains | Authorize per reference, never per blob |
| Backfill corrupts storage | Link mode first; `--delete-old` never before verification |
| `documents` and `assets` drift during Phases 2-4 | Assets is sole writer from Phase 2; `documents` columns become read-only accessors |
