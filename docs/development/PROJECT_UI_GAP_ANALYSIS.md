# Project UI Gap Analysis (Prototype vs Current)

Date: 2026-03-16
Scope: Compare `project-manager` prototype project UX against `project-manager-app` current UX.
Audience split: `Users` and `Admins`.

## User UI Missing Items

1. No non-admin project routes/pages in current app.
- Prototype has full user project routes (`projects.index/show/create/edit`) plus project-level actions.
- Current app Projects domain only mounts admin routes; user web/mobile route files are placeholders.

2. No mobile project index/show parity for field users.
- Prototype includes dedicated mobile project list/detail pages with user workflows.
- Current app does not expose project mobile routes/views in the Projects domain.

3. No user project lifecycle actions from project screens.
- Prototype includes user actions such as status updates, copy project, rename project, and archive entry points.
- Current app user-facing project action surface is absent.

4. No user document workflow from project UI.
- Prototype supports project document upload/list/delete from project pages.
- Current app lacks non-admin project document screens/actions.

5. No user project board (kanban) experience.
- Prototype has a project board with desktop/mobile pages, board CRUD, drag-and-drop project movement, and custom items.
- Current app has no equivalent user-facing project board routes/UI.

6. No user import/export utilities for projects.
- Prototype has CSV template/download/import and utility actions connected to project workflows.
- Current app does not expose equivalent project import/export paths for users.

## Admin UI Missing Items

1. Reduced admin project route coverage.
- Prototype admin routes include project show/edit plus financial analysis, labor analysis, access management, archive/unarchive, and time/labor entry actions.
- Current app admin project routes are currently limited to index/create/show/edit.

2. Missing admin project access management UI.
- Prototype has dedicated manage-access page with role assignment and mass foreman assignment actions.
- Current app has no equivalent admin project access screen.

3. Missing admin project financial/labor analysis pages.
- Prototype includes dedicated financial and labor analysis pages with print/export-ready summaries.
- Current app project show has basic overview/tasks/templates tabs only.

4. Missing admin archive/unarchive workflow at project level.
- Prototype supports archive/unarchive routes and project-state actions directly from admin project pages.
- Current app does not expose archive/unarchive in current project admin routes.

5. Missing admin project-centric quick actions.
- Prototype project show includes quick actions for add invoice, add labor entry, manage access, and financial report jumps.
- Current app admin show does not provide this broader operations toolbar.

## Areas Already Added in Current App (Not Missing)

1. Modern Livewire-based admin project show with tabs (`overview`, `tasks`, `templates`).
2. Admin project CRUD baseline (`index/create/show/edit`).
3. Stronger integrated project-task hierarchy management in project show tab.

## Suggested Priority

P1
1. Add non-admin web/mobile project surfaces (index/show minimum) and wire Projects domain web/mobile route mounting.
2. Implement admin manage-access workflow and role assignment parity.
3. Add admin archive/unarchive actions and route support.

P2
1. Add admin financial/labor analysis pages (or equivalent reporting drill-downs).
2. Restore user project document workflow and key status/update actions.
3. Add user project board (kanban) parity if still required by operations.

P3
1. Reintroduce project import/export utilities where needed.
2. Expand admin quick-action toolbar for project operations.

## Source References

Prototype examples:
- `project-manager/routes/web/projects.php`
- `project-manager/routes/web/project-board.php`
- `project-manager/routes/admin/projects.php`
- `project-manager/resources/views/projects/index.blade.php`
- `project-manager/resources/views/projects/show.blade.php`
- `project-manager/resources/views/projects/mobile-index.blade.php`
- `project-manager/resources/views/projects/mobile-show.blade.php`
- `project-manager/resources/views/admin/projects/index.blade.php`
- `project-manager/resources/views/admin/projects/show.blade.php`
- `project-manager/resources/views/admin/projects/manage-access.blade.php`
- `project-manager/resources/views/admin/projects/financial-analysis.blade.php`
- `project-manager/resources/views/admin/projects/labor-analysis.blade.php`

Current app examples:
- `project-manager-app/app/Domains/Projects/Providers/ProjectsServiceProvider.php`
- `project-manager-app/app/Domains/Projects/Routes/admin.php`
- `project-manager-app/app/Domains/Projects/Routes/web.php`
- `project-manager-app/app/Domains/Projects/Routes/mobile.php`
- `project-manager-app/app/Domains/Projects/Routes/api.php`
- `project-manager-app/app/Domains/Projects/Resources/Views/livewire/admin/projects/index.blade.php`
- `project-manager-app/app/Domains/Projects/Resources/Views/livewire/admin/projects/show.blade.php`
- `project-manager-app/app/Domains/Projects/Resources/Views/livewire/admin/projects/form.blade.php`
