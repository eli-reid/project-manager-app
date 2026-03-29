# Documents Feature Implementation Checklist

## Scope Lock
- [x] Target repo is project-manager-app only.
- [x] project-manager is prototype reference only.
- [x] One canonical documents table with pivots is selected.

## Confirmed Product Rules
- [x] User-owned documents can be private or global.
- [x] Promoting to global does not transfer ownership.
- [x] User owners can demote global documents back to private.
- [x] Project documents are owned by exactly one project.
- [x] User-owned documents are not linkable to projects.
- [x] Project tab widget includes full CRUD.
- [x] V1 authorization baseline is project access plus permissions.
- [x] Replacement behavior is configurable with single-file replacement as default.

## Phase 1 - Data Layer
- [x] Add documents table migration.
- [x] Add document_user_owners pivot migration.
- [x] Add document_project_owners pivot migration.
- [x] Add indexes for owner scope and visibility.
- [x] Add Document model with owner and visibility scopes.
- [x] Add Document factory.
- [x] Optional hardening: add DB-level XOR ownership guard.

## Phase 2 - Domain Wiring
- [x] Add DocumentPermissions registry class.
- [x] Add DocumentPolicy (view/create/update/delete/promote/demote/manage-project).
- [x] Register policy in DocumentsServiceProvider.
- [x] Register migrations, views, and Livewire components in DocumentsServiceProvider.
- [x] Register admin/web/mobile/api route groups in DocumentsServiceProvider.

## Phase 3 - User and Global Livewire Surfaces
- [x] Add user My Documents Livewire CRUD component.
- [x] Add global documents Livewire listing component.
- [x] Add promote/demote user document actions.
- [x] Add web routes for user and global documents pages.

## Phase 4 - Project Tab Full CRUD
- [x] Add Admin Projects DocumentsTab Livewire component.
- [x] Add upload/edit/delete behavior for project-owned docs.
- [x] Add project document search in tab.
- [x] Integrate Documents tab and count into project show page.
- [x] Scope project tab queries to project-owned records only.

## Phase 5 - Settings and Storage Behavior
- [x] Add documents.replace_behavior setting with default replace.
- [x] Keep documents.enable_versioning for compatibility.
- [x] Implement replace behavior in DocumentService.
- [ ] Future: add version history browsing when keep-history is enabled.

## Phase 6 - Testing
- [x] Extend DocumentsSettingsTest for replace behavior key.
- [x] Add DocumentsDomainScaffoldTest for route auth and permissions.
- [x] Add project tab CRUD feature test.
- [x] Add user promote/demote Livewire interaction test.
- [x] Add explicit anti-link test ensuring user-owned docs cannot attach to projects.
- [x] Add policy matrix tests for mixed permission sets.

## Deferred Backlog
- [ ] Per-user and per-role access pivots for fine-grained sharing.
- [ ] Version-history data model and retrieval UI.
- [ ] Download tracking and audit logs.
- [ ] Bulk actions and retention policy workflows.

## Admin Enhancements
- [x] Add admin documents queue page.
- [x] Allow admins to delete any document.
- [x] Show document disk usage summaries for storage management.
