# Document System Design Spec

## Status
- Draft v1
- Date: 2026-06-01
- Scope: Core Files + Documents domain + domain-owned attachment metadata for project workflows

## Goals
- Centralize binary file storage concerns in a Core Files domain.
- Centralize document lifecycle orchestration in the Documents domain.
- Keep business-context metadata with owning domains (Submittals, RFIs, Change Orders, and future domains).
- Keep users in project context for project-tab workflows.
- Support consistent attachment UX and filtering across domains.

## Non-Goals
- Build a full enterprise DMS with OCR, indexing pipelines, and legal hold.
- Replace every standalone domain detail page immediately.
- Introduce cross-domain shared pivot metadata tables.

## Design Principles
- Single responsibility by domain boundary.
- Domain ownership of business metadata (hard boundary).
- Contract-first service integration.
- Project-context-first navigation for project tab actions.
- Backward-safe rollout with targeted tests at each phase.

## High-Level Architecture

### Core Files Domain
Owns physical file concerns only.

Contracts:
- App\Core\Files\Contracts\FileStorageContract
- App\Core\Files\Contracts\FilePathNormalizerContract

Implementations:
- App\Core\Files\Services\LaravelFileStorage
- App\Core\Files\Services\DefaultFilePathNormalizer

Provider:
- App\Core\Files\Providers\FilesServiceProvider

Responsibilities:
- Persist uploaded files to disk.
- Move/delete physical files.
- Normalize folder path segments.
- No business permission or domain metadata logic.

### Documents Domain
Owns document records and orchestration.

Contracts:
- App\Domains\Documents\Contracts\DocumentOrchestratorContract
- App\Domains\Documents\Contracts\DocumentSharingContract
- App\Domains\Documents\Contracts\ProjectDocumentLibraryContract

Implementations:
- App\Domains\Documents\Services\DocumentService
- App\Domains\Documents\Services\DocumentShareService
- App\Domains\Documents\Services\ProjectDocumentLibrary

Provider:
- App\Domains\Documents\Providers\DocumentsServiceProvider

Responsibilities:
- Upload/replace/move/delete document records and linked files.
- Share/expire/revoke documents.
- Provide project-scoped read/query APIs.
- Enforce source-of-truth allowlists for project attachment selection.

### Owning Business Domains
Each domain owns attachment metadata via its pivot table.

Current owners:
- Submittals: submittal_documents
- RFIs: rfi_documents
- Change Orders: change_order_documents

Responsibilities:
- Define allowed metadata enums (role/status/etc).
- Validate metadata and sync pivot payloads.
- Provide domain-specific filters/search behavior.

## Data Model

### documents (base)
Core columns (representative):
- id
- owner_scope
- owner_id
- title
- original_name
- mime_type
- size_bytes
- storage_disk
- storage_path
- folder_path (nullable)
- created_by_id
- timestamps

Indexes:
- owner_scope + owner_id
- owner_scope + owner_id + folder_path

### Domain pivot metadata
All pivots use domain foreign key + document_id, plus metadata.

Common metadata pattern:
- document_role
- document_status
- revision (nullable)
- discipline (nullable)
- timestamps

Index pattern:
- owner_fk + document_role + document_status

## Service Contracts and Behavioral Guarantees

### FileStorageContract
Guarantees:
- Store returns canonical storage path.
- Move returns updated storage path.
- Delete is idempotent (safe when file missing).

### FilePathNormalizerContract
Guarantees:
- Reject/trim invalid path separators.
- Collapse duplicate separators.
- Preserve user intent for meaningful folder nesting.

### DocumentOrchestratorContract
Guarantees:
- uploadUserDocument/uploadProjectDocument create DB record and file atomically from caller perspective.
- replaceFile preserves document identity while rotating storage path.
- moveDocument updates folder path/storage path consistently.
- deleteDocument removes both DB record and physical file.
- validationRules returns authoritative upload constraints.

### ProjectDocumentLibraryContract
Guarantees:
- listProjectAccessible returns user/project-accessible documents.
- allowedDocumentIdsForProject returns safe subset for pivot sync.
- folderPathsForProject provides distinct folders for UI filtering.

### DocumentSharingContract
Guarantees:
- create share with optional expiry.
- update expiry.
- revoke share.

## Runtime Flows

### Upload in project context
1. Domain UI requests upload through DocumentOrchestratorContract.
2. DocumentService validates and normalizes folder path.
3. FileStorageContract stores file.
4. Documents row is created/updated.
5. Owning domain syncs selected document ids + metadata into pivot.

### Replace file
1. Domain action calls replaceFile(document, uploadedFile).
2. New file stored, old file deleted/moved per policy.
3. documents.storage_path and related metadata updated.
4. Pivot metadata remains intact.

### Attach existing documents
1. Domain requests allowedDocumentIdsForProject(project, selectedIds).
2. Domain builds pivot payload with validated metadata.
3. Domain syncs payload to its pivot table.

## UI and Navigation Rules
- Project tab actions should route through admin.projects.show with tab query context.
- Review/Create modes in project tabs should use mode + id query flags where needed.
- Component state (mode/id/url derivation) should live in Livewire classes, not blade request parsing blocks.
- Project-tab view path convention:
  - Resources/Views/livewire/admin/projects/project-tab.blade.php

## Security and Authorization
- Authorize all view/create/update/review actions in owning domain policies.
- Never trust incoming selected document ids without project allowlist filtering.
- Never expose storage internals or secret paths in UI.
- Respect document ownership scope when reading lists.

## Observability and Audit
- Log key document lifecycle events (upload/replace/move/delete/share).
- Include actor id, project id (if present), document id, and operation result.
- Keep logs free of secret values.

## Performance Considerations
- Eager-load relationships for tab tables.
- Use index-backed filters for document metadata pivots.
- Use counts via withCount or targeted aggregate queries for tab badges.
- Keep project-tab list queries capped (limit/page).

## Testing Strategy
- Contract binding tests for Core Files and Documents services.
- Feature tests for each owning domain attachment workflow.
- Project-tab route context tests asserting links remain on admin.projects.show with tab context.
- Metadata persistence and filter behavior tests per domain.
- Boundary tests to detect raw cross-domain document querying in non-Documents Livewire layers.

## Rollout Plan

### Phase 1: Foundation
- Core Files contracts + adapters + provider.
- Documents contracts + service bindings.
- folder_path support and path normalization.

### Phase 2: Domain Adoption
- Submittals metadata + filters + tests.
- RFIs metadata + tests.
- Change Orders metadata + tests.

### Phase 3: Hardening
- Replace service locator usage with DI in project-facing components.
- Standardize project-tab Livewire view conventions.
- Add/maintain boundary guard tests.

### Phase 4: Expansion
- Migrate additional domains to domain-owned document metadata pivots.
- Add standardized project-tab review/create modes where needed.

## Extension Guidelines for New Domain
1. Add domain pivot migration with composite key and metadata columns.
2. Add relationship + allowed metadata constants in domain model.
3. Use ProjectDocumentLibraryContract for selectable documents.
4. Sync pivot metadata in domain component/service.
5. Add domain feature tests:
   - attach
   - edit metadata
   - filter/query behavior
   - project-tab navigation context

## Risks and Mitigations
- Risk: Route/context sprawl from tab mode params.
  - Mitigation: keep mode parsing in Livewire class properties and central helpers per tab component.
- Risk: Metadata divergence across domains.
  - Mitigation: shared naming conventions + per-domain allowed constants + test templates.
- Risk: Unauthorized attachment by id tampering.
  - Mitigation: always pass through allowedDocumentIdsForProject before sync.

## Open Decisions
- Whether all domains should support full inline project-tab review mode versus link-through to domain detail pages.
- Whether to introduce a small shared trait for project-tab mode/query parsing.
- Whether to add immutable audit tables for legal/compliance document events.
