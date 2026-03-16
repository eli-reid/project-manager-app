# Tasks UI Gap Analysis (Prototype vs Current)

Date: 2026-03-15
Scope: Compare `project-manager` prototype tasks UX against `project-manager-app` current UX.
Audience split: `Users` and `Admins`.

## User UI Missing Items

1. No non-admin tasks experience in current app routes/components.
- Prototype includes user/mobile task routes and screens.
- Current app tasks domain is mounted under admin routes only.

2. No mobile user task workflow parity.
- Prototype has mobile project task index/create/edit/status update flows.
- Current app has no tasks-specific user/mobile domain UI equivalent.

3. No user-facing CSV template download/import for tasks.
- Prototype supports CSV template and CSV import from project task UI.
- Current app task actions do not expose CSV import/export.

4. No user drag-and-drop reorder for task trees.
- Prototype supports sortable task/subtask ordering with reorder endpoint.
- Current app does not provide reorder UX or corresponding task route.

5. No inline task rename flow in user task rows.
- Prototype supports inline rename with async patch.
- Current app relies on full edit page/actions.

## Admin UI Missing Items

1. Admin task details endpoint/page parity missing.
- Prototype admin routes include a task details page and API helpers.
- Current app admin task routes are index/create/edit only.

2. Admin reorder endpoint/UX missing on tasks list.
- Prototype admin supports reorder route.
- Current app admin routes do not include reorder action.

3. Admin template "apply to project" action missing.
- Prototype has apply-template-to-project route.
- Current app template routes stop at create/edit/delete listing workflows.

4. Admin category quick-store endpoint missing.
- Prototype supports quick category creation endpoint.
- Current app uses full create/edit forms only.

5. Advanced category selector UX missing in task form.
- Prototype selector includes category metadata and quick-create modal.
- Current app task form uses standard dropdowns without inline category creation.

6. Expanded task analytics panel missing.
- Prototype includes weighted progress, billable/ready-to-invoice summaries, and category-level signals.
- Current app project tasks tab currently surfaces basic counts and hierarchy actions.

## Areas Already Added in Current App (Not Missing)

1. Project-level hierarchical task tree with category collapse/expand.
2. Quick add task/category in project tasks tab.
3. Copy task, copy category, and copy category tasks actions.
4. Task/category/template admin index and forms.

## Suggested Priority

P1
1. User/mobile tasks surface (list + status update) for non-admin users.
2. CSV import/export parity.
3. Reorder endpoint + drag-and-drop UX.

P2
1. Template apply-to-project workflow.
2. Quick category create endpoint + enhanced selector.
3. Inline rename in task rows.

P3
1. Admin task detail page/API parity.
2. Extended analytics panel parity.

## Source References

Prototype examples:
- `project-manager/routes/web/projects.php`
- `project-manager/routes/admin/tasks.php`
- `project-manager/resources/views/components/project/tasks-tab.blade.php`
- `project-manager/resources/views/components/project/tasks-hierarchical-view.blade.php`
- `project-manager/resources/views/components/project/task-row.blade.php`
- `project-manager/resources/views/components/project/task-progress.blade.php`
- `project-manager/resources/views/components/task-category-select.blade.php`

Current app examples:
- `project-manager-app/app/Domains/Tasks/Providers/TasksServiceProvider.php`
- `project-manager-app/app/Domains/Tasks/Routes/admin.php`
- `project-manager-app/app/Domains/Tasks/Resources/Views/livewire/admin/tasks/index.blade.php`
- `project-manager-app/app/Domains/Tasks/Resources/Views/livewire/admin/tasks/form.blade.php`
- `project-manager-app/app/Domains/Projects/Resources/Views/livewire/admin/projects/show.blade.php`
- `project-manager-app/app/Domains/Projects/Resources/Views/livewire/admin/projects/_task-category-tree-row.blade.php`
