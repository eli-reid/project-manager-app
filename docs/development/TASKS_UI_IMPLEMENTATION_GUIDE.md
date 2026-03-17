# Tasks UI Implementation Guide

**Date:** 2026-03-17  
**Based on:** `TASKS_UI_GAP_ANALYSIS.md`  
**Purpose:** Step-by-step instructions for every missing Task UI feature, ordered by priority.

---

## How to Read This Guide

Each feature follows this structure:

1. **What it is** — brief description of the feature and why it exists.
2. **Prototype reference** — exact files in `project-manager` to study.
3. **Current app touchpoints** — files in `project-manager-app` you will create or edit.
4. **Implementation steps** — numbered, sequential actions.
5. **Acceptance criteria** — how to know you are done.

---

## Priority 1 (P1) — Build These First

---

### P1-1 · User-Facing Task List with Status Update

**What it is:** Non-admin users need to view tasks for a project and update the status of tasks assigned to them. Today the Tasks domain is fully gated behind `admin` middleware — regular users see nothing.

**Prototype reference:**
- `project-manager/routes/web/projects.php` — `projects.tasks.*` route group
- `project-manager/app/Http/Controllers/ProjectTaskController.php` — `updateStatus()` method
- `project-manager/resources/views/projects/tasks/` — desktop views

**Current app touchpoints:**
- `app/Domains/Tasks/Routes/web.php` — add routes here (currently empty stub)
- `app/Domains/Tasks/Livewire/` — add new user-scoped Livewire components here
- `app/Domains/Tasks/Resources/Views/livewire/` — add user view templates here
- `app/Domains/Tasks/Providers/TasksServiceProvider.php` — register the new web route file
- `app/Domains/Projects/Livewire/Admin/Projects/Show.php` — the project show page already tabs into tasks; the same tab data needs to work for non-admin users too

#### Steps

**Step 1 — Add user task permissions**

Open `app/Domains/Tasks/Permissions/TaskPermissions.php`. Confirm
`VIEW` and `EDIT` constants exist. No change needed if they do — the policy
`TaskPolicy` already checks `tasks.view` and `tasks.edit`.

**Step 2 — Create the user task list Livewire component (PHP)**

```bash
php artisan make:livewire Tasks/ProjectTaskList --no-interaction
```

Move the generated class to:
`app/Domains/Tasks/Livewire/User/ProjectTaskList.php`

The component should:
- Accept a `$projectId` public property.
- On `mount()`: authorize `view` on the `Project`, then call `ProjectTaskHierarchyViewDataService` (already exists) to load the category tree and tasks.
- Expose a `updateStatus(string $taskId, string $status)` action that:
  1. Finds the task, checks `$this->authorize('update', $task)`.
  2. Validates `$status` is one of `Task::statuses()`.
  3. Updates `$task->status` and saves.
  4. Dispatches a `$this->dispatch('task-status-updated')` event to re-render.
- Expose a public `$statusFilter = ''` property with an `updatedStatusFilter()` lifecycle hook to re-query.

**Step 3 — Create the user task list Blade view**

Create:
`app/Domains/Tasks/Resources/Views/livewire/user/project-task-list.blade.php`

The view should:
- Use the app's Flux UI layout wrapper (`<flux:main>` / `<flux:container>`).
- Render the same collapsible category+task tree as the admin widget, but without admin-only actions (copy, delete branch, bulk create).
- Each task row shows: title, status badge (clickable dropdown for users who can edit), priority, due date, assigned-to avatar, estimated hours.
- Wire the status dropdown to `wire:change="updateStatus('{{ $task->id }}', $event.target.value)"`.

**Step 4 — Register the component in the service provider**

In `app/Domains/Tasks/Providers/TasksServiceProvider.php`, inside the `Livewire::component()` registrations block, add:

```php
Livewire::component('tasks.user.project-task-list', \App\Domains\Tasks\Livewire\User\ProjectTaskList::class);
```

**Step 5 — Add web routes**

Open `app/Domains/Tasks/Routes/web.php` and replace the placeholder comment with:

```php
use App\Domains\Tasks\Livewire\User\ProjectTaskList;
use App\Domains\Tasks\Models\Task;
use Illuminate\Support\Facades\Route;

Route::prefix('projects/{project}/tasks')
    ->name('projects.tasks.')
    ->middleware('can:view,project')
    ->group(function (): void {
        Route::get('/', ProjectTaskList::class)
            ->middleware('can:viewAny,'.Task::class)
            ->name('index');
    });
```

**Step 6 — Mount the web route file in the service provider**

In `TasksServiceProvider::boot()`, locate where `admin.php` is loaded. Below it, add:

```php
$this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
```

**Step 7 — Surface the task list for non-admin users on the project show page**

In `app/Domains/Projects/Livewire/Admin/Projects/Show.php`, the `tasks` tab is already built. Verify the tab link includes the non-admin route:

```php
// The existing tab is admin-only. For non-admin render, check if the current
// route is admin and conditionally embed the correct component.
```

As a simpler approach, keep the admin show page as-is and create a separate user-facing project show page that embeds `<livewire:tasks.user.project-task-list :project-id="$project->id" />`. Coordinate with the Projects domain team to add a `tasks` tab to any user project show page.

**Step 8 — Write a feature test**

```bash
php artisan make:test Tasks/UserProjectTaskListTest --pest --no-interaction
```

Test that an authenticated user with `tasks.view` can load `/projects/{project}/tasks`, and that `updateStatus` returns a 403 for users without `tasks.edit`. Test that a status change persists.

**Step 9 — Run tests and pint**

```bash
php artisan test --compact --filter=UserProjectTaskListTest
vendor/bin/pint --dirty --format agent
```

#### Acceptance Criteria
- A logged-in non-admin user with `tasks.view` can visit `/projects/{project}/tasks` and see the task hierarchy.
- A user with `tasks.edit` can update a task's status from the list without a page reload.
- A user without `tasks.view` gets a 403.
- No admin-only actions (copy/delete branch) are visible.

---

### P1-2 · Mobile Task Workflow (List + Create + Edit + Status Update)

**What it is:** Field workers use mobile browsers. The prototype has dedicated mobile views and routes. The current app has empty mobile route stubs.

**Prototype reference:**
- `project-manager/routes/web/projects.php` — `projects.mobile.tasks.*` route group
- `project-manager/resources/views/projects/tasks/mobile-index.blade.php`
- `project-manager/resources/views/projects/tasks/mobile-create.blade.php`
- `project-manager/resources/views/projects/tasks/mobile-edit.blade.php`

**Current app touchpoints:**
- `app/Domains/Tasks/Routes/mobile.php` — currently an empty stub
- `app/Domains/Tasks/Livewire/User/Mobile/` — create here (new folder)
- `app/Domains/Tasks/Resources/Views/livewire/user/mobile/` — views here

#### Steps

**Step 1 — Create `MobileProjectTaskList` Livewire component**

```bash
php artisan make:livewire Tasks/Mobile/ProjectTaskList --no-interaction
```

Move to: `app/Domains/Tasks/Livewire/User/Mobile/ProjectTaskList.php`

The mobile list should be simpler than desktop:
- Flat list of tasks (no deep hierarchy collapse).
- Filter by status (open/in-progress/done) via a `<flux:select>` dropdown.
- Each task is a card: title, project name, status badge, due date.
- Tapping a status badge fires `updateStatus()` inline (same logic as P1-1).

**Step 2 — Create `MobileProjectTaskForm` Livewire component**

```bash
php artisan make:livewire Tasks/Mobile/ProjectTaskForm --no-interaction
```

Move to: `app/Domains/Tasks/Livewire/User/Mobile/ProjectTaskForm.php`

Fields needed (mobile-optimized subset):
- Title (required)
- Status (select)
- Priority (select)
- Due date (date input)
- Notes (textarea)

On save, call `TaskPolicy@create` / `TaskPolicy@update` as appropriate.

**Step 3 — Create mobile Blade views**

`app/Domains/Tasks/Resources/Views/livewire/user/mobile/project-task-list.blade.php`  
`app/Domains/Tasks/Resources/Views/livewire/user/mobile/project-task-form.blade.php`

Reference the prototype mobile views (listed above) for the card-based layout. Use Flux UI `<flux:card>` and `<flux:badge>` components. Keep actions as large tap targets.

**Step 4 — Populate `mobile.php` routes**

```php
use App\Domains\Tasks\Livewire\User\Mobile\ProjectTaskList;
use App\Domains\Tasks\Livewire\User\Mobile\ProjectTaskForm;
use App\Domains\Tasks\Models\Task;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/projects/{project}/tasks')
    ->name('mobile.projects.tasks.')
    ->middleware('can:view,project')
    ->group(function (): void {
        Route::get('/', ProjectTaskList::class)
            ->middleware('can:viewAny,'.Task::class)
            ->name('index');
        Route::get('/create', ProjectTaskForm::class)
            ->middleware('can:create,'.Task::class)
            ->name('create');
        Route::get('/{task}/edit', ProjectTaskForm::class)
            ->middleware('can:update,task')
            ->name('edit');
    });
```

**Step 5 — Mount mobile routes in the service provider**

In `TasksServiceProvider::boot()`:

```php
$this->loadRoutesFrom(__DIR__.'/../Routes/mobile.php');
```

**Step 6 — Register Livewire components**

Add to the component registration block in `TasksServiceProvider`:

```php
Livewire::component('tasks.user.mobile.project-task-list', \App\Domains\Tasks\Livewire\User\Mobile\ProjectTaskList::class);
Livewire::component('tasks.user.mobile.project-task-form', \App\Domains\Tasks\Livewire\User\Mobile\ProjectTaskForm::class);
```

**Step 7 — Tests**

```bash
php artisan make:test Tasks/MobileProjectTaskTest --pest --no-interaction
```

Cover: list loads, create stores, edit updates, status update patches.

**Step 8 — Pint**

```bash
vendor/bin/pint --dirty --format agent
```

#### Acceptance Criteria
- `/mobile/projects/{project}/tasks` shows a card-based task list on small viewports.
- `/mobile/projects/{project}/tasks/create` lets a user create a task.
- Status can be updated inline from the card.

---

### P1-3 · CSV Template Download and CSV Import

**What it is:** Users can download a pre-populated CSV template, fill it in offline, then upload it to bulk-create tasks for a project.

**Prototype reference:**
- `project-manager/app/Http/Controllers/ProjectTaskController.php` — `downloadCsvTemplate()` and `importCsv()` methods
- Routes: `GET /projects/{project}/tasks/csv-template` and `POST /projects/{project}/tasks/import-csv`
- `tasks-tab.blade.php` — the download link and inline upload form

**Current app touchpoints:**
- New: `app/Domains/Tasks/Actions/DownloadCsvTemplateAction.php`
- New: `app/Domains/Tasks/Actions/ImportTasksCsvAction.php`
- Edit: `app/Domains/Tasks/Routes/web.php` — two new routes
- Edit: `app/Domains/Tasks/Livewire/Admin/Projects/TaskHierarchyWidget.php` — add CSV UI to admin widget
- Edit: `app/Domains/Tasks/Resources/Views/livewire/admin/projects/task-hierarchy-widget.blade.php` — add CSV buttons

#### Steps

**Step 1 — Create `DownloadCsvTemplateAction`**

```bash
php artisan make:class Domains/Tasks/Actions/DownloadCsvTemplateAction --no-interaction
```

The action returns a `StreamedResponse`. Use the prototype's column order:  
`Title, Description, Category Code, Assigned To (email), Due Date (YYYY-MM-DD), Priority, Status, Estimated Hours, Parent Task Title, Notes`

Include two example rows (one root task, one sub-task referencing the first by title). Add a UTF-8 BOM (`\xEF\xBB\xBF`) for Excel compatibility.

```php
public function __invoke(Project $project): StreamedResponse
{
    return response()->streamDownload(function () use ($project): void {
        $handle = fopen('php://output', 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Title', 'Description', 'Category Code', 'Assigned To', 'Due Date', 'Priority', 'Status', 'Estimated Hours', 'Parent Task Title', 'Notes']);
        fputcsv($handle, ['Example Task', 'A sample task', '', '', '', 'medium', 'not_started', '2', '', '']);
        fputcsv($handle, ['Example Sub-task', 'A child task', '', '', '', 'low', 'not_started', '1', 'Example Task', '']);
        fclose($handle);
    }, "tasks-template-{$project->id}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
}
```

**Step 2 — Create `ImportTasksCsvAction`**

```bash
php artisan make:class Domains/Tasks/Actions/ImportTasksCsvAction --no-interaction
```

The action accepts a `Project` and an `UploadedFile`. Logic (adapted from prototype):

1. Validate the file: `mimes:csv,txt`, `max:2048`.
2. Open the file; strip BOM if present.
3. Read the first row as headers; normalize to lowercase/trim.
4. Pre-cache: `TaskCategory::where('project_id', $project->id)->get()->keyBy('code')` — avoids N+1. Also cache `User::all()->keyBy('email')` and existing tasks for parent lookup.
5. Loop remaining rows:
   - Resolve `category_id` from `Category Code` column.
   - Resolve `assigned_to` from `Assigned To` (email) column.
   - Resolve `parent_task_id` by title match (use the pre-cached task collection).
   - Map `Priority` and `Status` column values to the constants in `Task::PRIORITY_*` / `Task::STATUS_*`.
   - Call `Task::create([...])`.
6. Return `['created' => $count, 'errors' => $errors]`.

**Step 3 — Add a `CsvImportController` or add routes directly to the Livewire component**

Since the current app uses Livewire, the cleanest approach is to add a traditional route that handles the download/upload outside Livewire (Livewire file upload can be used too, but a route is simpler for the download):

Add to `app/Domains/Tasks/Routes/web.php`:

```php
use App\Domains\Tasks\Actions\DownloadCsvTemplateAction;

Route::get('/projects/{project}/tasks/csv-template', function (Project $project, DownloadCsvTemplateAction $action) {
    Gate::authorize('view', $project);
    return $action($project);
})->name('projects.tasks.csv-template');
```

For import, add a Livewire file upload property to `TaskHierarchyWidget` or create a dedicated `CsvImportModal` Livewire component:

```bash
php artisan make:livewire Tasks/Admin/Projects/CsvImportModal --no-interaction
```

Move to: `app/Domains/Tasks/Livewire/Admin/Projects/CsvImportModal.php`

Properties: `#[Validate('file|mimes:csv,txt|max:2048')] public $csvFile;`

Action `import()`:
1. `$this->validate()`.
2. Call `ImportTasksCsvAction`.
3. Flash results, dispatch `$this->dispatch('tasks-imported')` to trigger the widget to reload.

**Step 4 — Add CSV UI to the admin widget**

In `task-hierarchy-widget.blade.php`, in the actions toolbar (existing "Add Category", "Add Task" buttons area), add:

```html
<a href="{{ route('projects.tasks.csv-template', $project) }}" class="...">
    Download CSV Template
</a>

<flux:button wire:click="$dispatch('open-modal', {name: 'csv-import'})" size="sm">
    Import CSV
</flux:button>
```

Below the main content, embed:

```html
<livewire:tasks.admin.projects.csv-import-modal :project-id="$project->id" />
```

**Step 5 — Register the new Livewire component in the service provider.**

**Step 6 — Tests**

```bash
php artisan make:test Tasks/CsvImportExportTest --pest --no-interaction
```

Test: template download returns 200 with `text/csv` content type; import with a valid CSV creates tasks; import with bad data returns validation errors; a user without `tasks.create` gets 403 on import.

**Step 7 — Pint**

```bash
vendor/bin/pint --dirty --format agent
```

#### Acceptance Criteria
- Admin widget shows "Download CSV Template" and "Import CSV" actions.
- Clicking download streams a CSV file with correct headers and example rows.
- Uploading a valid CSV creates tasks; results flash a success message with the count.
- Invalid rows show inline errors; valid rows in the same file still import.

---

### P1-4 · Drag-and-Drop Task Reorder

**What it is:** Users can drag task rows to change their `sort_order` within a category or parent task scope.

**Prototype reference:**
- `tasks-hierarchical-view.blade.php` — `data-task-ordering` / `data-reorder-url` attributes, linked JS
- `routes/web/projects.php` — `POST /projects/{project}/tasks/reorder`
- `ProjectTaskController@reorder` — validates ownership, updates `sort_order` in a transaction

**Current app touchpoints:**
- New: `app/Domains/Tasks/Http/Actions/ReorderTasksAction.php`
- Edit: `app/Domains/Tasks/Routes/web.php` — add reorder POST route
- Edit: `app/Domains/Tasks/Livewire/Admin/Projects/TaskHierarchyWidget.php` — add a `reorderTasks()` Livewire action
- Edit: `app/Domains/Tasks/Resources/Views/livewire/admin/projects/task-hierarchy-widget.blade.php` — add SortableJS and data attributes

#### Steps

**Step 1 — Create `ReorderTasksAction`**

```bash
php artisan make:class Domains/Tasks/Actions/ReorderTasksAction --no-interaction
```

Signature:

```php
public function __invoke(Project $project, ?string $parentTaskId, array $tasks): void
{
    // $tasks = [['id' => 'ulid', 'sort_order' => 1], ...]
    // 1. Validate all task IDs belong to $project (prevents IDOR)
    $ids = collect($tasks)->pluck('id');
    $validCount = Task::whereIn('id', $ids)
        ->where('project_id', $project->id)
        ->count();
    if ($validCount !== $ids->count()) {
        throw ValidationException::withMessages(['tasks' => 'Invalid task IDs.']);
    }
    // 2. Update in a transaction
    DB::transaction(function () use ($tasks): void {
        foreach ($tasks as $item) {
            Task::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }
    });
}
```

**Step 2 — Add a reorder API route**

Add to `app/Domains/Tasks/Routes/api.php` (or `web.php` under an AJAX-friendly prefix):

```php
Route::post('/projects/{project}/tasks/reorder', function (
    Request $request,
    Project $project,
    ReorderTasksAction $action
): JsonResponse {
    Gate::authorize('update', $project);
    $validated = $request->validate([
        'parent_task_id' => 'nullable|string',
        'tasks' => 'required|array',
        'tasks.*.id' => 'required|string',
        'tasks.*.sort_order' => 'required|integer|min:0',
    ]);
    $action($project, $validated['parent_task_id'] ?? null, $validated['tasks']);
    return response()->json(['message' => 'Order updated.']);
})->name('projects.tasks.reorder');
```

**Step 3 — Add SortableJS via npm**

```bash
npm install sortablejs
```

Create `resources/js/task-sortable.js`:

```js
import Sortable from 'sortablejs';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-task-sortable]').forEach((el) => {
        const reorderUrl = el.dataset.reorderUrl;
        const parentTaskId = el.dataset.parentTaskId ?? null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        new Sortable(el, {
            handle: '[data-drag-handle]',
            animation: 150,
            onEnd({ to }) {
                const tasks = [...to.children].map((row, index) => ({
                    id: row.dataset.taskId,
                    sort_order: index,
                }));
                fetch(reorderUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ parent_task_id: parentTaskId, tasks }),
                });
            },
        });
    });
});
```

Import in `resources/js/app.js`:

```js
import './task-sortable';
```

**Step 4 — Add data attributes and drag handles to the tree row partial**

In `_task-category-tree-row.blade.php`, find the `<tbody>` or `<div>` that wraps tasks within a category and add:

```html
<tbody
    data-task-sortable
    data-reorder-url="{{ route('projects.tasks.reorder', $project) }}"
    data-parent-task-id=""
>
```

Add a drag handle cell to each task row:

```html
<td data-drag-handle class="cursor-grab text-gray-400">
    <flux:icon.bars-2 />
</td>
```

**Step 5 — Build assets**

```bash
npm run build
```

**Step 6 — Tests**

```bash
php artisan make:test Tasks/ReorderTasksTest --pest --no-interaction
```

Test: valid reorder updates `sort_order` for all tasks; task IDs from a different project return 403/422; unauthenticated request returns 401.

**Step 7 — Pint**

```bash
vendor/bin/pint --dirty --format agent
```

#### Acceptance Criteria
- Task rows show a drag handle icon.
- Dragging a task row to a new position persists the new `sort_order` to the database without a page reload.
- Tasks outside the current project are rejected by the server.

---

## Priority 2 (P2) — Build These Second

---

### P2-1 · Template Apply-to-Project Workflow

**What it is:** An admin selects a task template and applies it to a project, bulk-creating tasks from the template's `template_tasks` JSON, optionally assigning all tasks to one user.

**Prototype reference:**
- `routes/admin/tasks.php` — `POST /admin/task-templates/{taskTemplate}/apply-to-project`
- `Admin\TaskTemplateController@applyToProject` — validates `project_id` and optional `assigned_to`, then calls `$taskTemplate->applyToProject($project, $user)`
- `TaskTemplate` model — `applyToProject()` method loops `template_tasks` JSON and creates `Task` records

**Current app touchpoints:**
- Edit: `app/Domains/Tasks/Models/TaskTemplate.php` — add `applyToProject()` method
- New: `app/Domains/Tasks/Actions/ApplyTemplateToProjectAction.php`
- Edit: `app/Domains/Tasks/Routes/admin.php` — add a POST route under `task-templates`
- Edit: `app/Domains/Tasks/Livewire/Admin/TaskTemplates/Index.php` — add an `applyToProject()` action triggering a modal
- Edit: `app/Domains/Tasks/Resources/Views/livewire/admin/task-templates/index.blade.php` — add "Apply to Project" button and modal

#### Steps

**Step 1 — Add `applyToProject()` to `TaskTemplate` model**

In `app/Domains/Tasks/Models/TaskTemplate.php`:

```php
public function applyToProject(Project $project, ?User $assignedTo = null): void
{
    foreach ($this->template_tasks as $template) {
        Task::create([
            'project_id'   => $project->id,
            'title'        => $template['title'],
            'description'  => $template['description'] ?? null,
            'priority'     => $template['priority'] ?? Task::PRIORITY_MEDIUM,
            'estimated_hours' => $template['estimated_hours'] ?? null,
            'is_billable'  => $template['is_billable'] ?? false,
            'status'       => Task::STATUS_NOT_STARTED,
            'assigned_to'  => $assignedTo?->id,
            'sort_order'   => $loop->index, // use a manual counter if not inside foreach $loop
        ]);
    }
}
```

**Step 2 — Create `ApplyTemplateToProjectAction`**

```bash
php artisan make:class Domains/Tasks/Actions/ApplyTemplateToProjectAction --no-interaction
```

Wraps `$taskTemplate->applyToProject($project, $assignedTo)` in a `DB::transaction`.

**Step 3 — Add the route**

In `app/Domains/Tasks/Routes/admin.php`, inside the `task-templates` route group, add:

```php
Route::post('/{taskTemplate}/apply-to-project', ApplyTemplateController::class)
    ->middleware('can:update,taskTemplate')
    ->name('apply-to-project');
```

Or handle directly via a Livewire action (no new route needed if using Livewire).

**Step 4 — Add UI to the templates index**

In `app/Domains/Tasks/Livewire/Admin/TaskTemplates/Index.php`, add:
- Public properties: `$applyProjectId`, `$applyAssignedTo`, `$applyTemplateId`, `$showApplyModal = false`.
- Action `openApplyModal(string $templateId)`: sets `$applyTemplateId = $templateId`, `$showApplyModal = true`.
- Action `applyTemplate()`: validates, calls `ApplyTemplateToProjectAction`, flashes success, closes modal.

In `index.blade.php`, add an "Apply to Project" button per template row and a `<flux:modal>` with a project `<flux:select>` (populated from `Project::all()`) and an optional assigned-to `<flux:select>`.

**Step 5 — Tests and pint**

```bash
php artisan make:test Tasks/ApplyTemplateToProjectTest --pest --no-interaction
vendor/bin/pint --dirty --format agent
```

#### Acceptance Criteria
- "Apply to Project" button appears on each template row.
- Selecting a project and submitting creates tasks matching the template's `template_tasks`.
- Optional assignee is applied to all created tasks.
- User without `task-templates.edit` cannot apply.

---

### P2-2 · Quick Category Create Endpoint + Enhanced Category Selector

**What it is:** In the task create/edit form, the category selector includes a "+" button that opens an inline modal for creating a new category without leaving the form. On success, the new category is auto-selected.

**Prototype reference:**
- `resources/views/components/task-category-select.blade.php` — full Alpine component with quick-create modal
- `routes/admin/tasks.php` — `POST /admin/task-categories/quick-store`
- `TaskCategoryController@quickStore` — returns JSON `{ category: {...} }`

**Current app touchpoints:**
- New: `app/Domains/Tasks/Routes/api.php` — add `quick-store` endpoint
- Edit: `app/Domains/Tasks/Livewire/Admin/Tasks/Form.php` — add category metadata display
- Edit: `app/Domains/Tasks/Resources/Views/livewire/admin/tasks/form.blade.php` — replace plain `<flux:select>` with an enhanced component
- New: `app/Domains/Tasks/Resources/Views/components/task-category-select.blade.php` — the enhanced selector

#### Steps

**Step 1 — Add `quick-store` API route**

In `app/Domains/Tasks/Routes/api.php`:

```php
Route::post('/task-categories/quick-store', function (Request $request): JsonResponse {
    Gate::authorize('create', TaskCategory::class);
    $validated = $request->validate([
        'name'               => 'required|string|max:255',
        'code'               => 'required|string|max:10|unique:task_categories,code',
        'color'              => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        'description'        => 'nullable|string|max:1000',
        'default_hourly_rate'=> 'nullable|numeric|min:0',
        'is_billable'        => 'boolean',
        'requires_materials' => 'boolean',
        'parent_id'          => 'nullable|exists:task_categories,id',
        'project_id'         => 'nullable|exists:projects,id',
    ]);
    $category = TaskCategory::create($validated);
    return response()->json(['category' => $category->only([
        'id','name','code','color','description',
        'default_hourly_rate','is_billable','requires_materials','parent_id',
    ])]);
})->name('task-categories.quick-store');
```

Mount in `TasksServiceProvider`: `$this->loadRoutesFrom(__DIR__.'/../Routes/api.php');` under the `api` route group prefix.

**Step 2 — Create the enhanced selector Blade component**

Create `app/Domains/Tasks/Resources/Views/components/task-category-select.blade.php`.

Model it on the prototype (`task-category-select.blade.php`). Key behaviour:
- `<select>` with Alpine `x-model="selectedId"` shows hierarchy via `indent` prefix.
- When `selectedId` changes, show a metadata panel: color swatch, description, hourly rate, billable badge.
- A `<flux:button icon="plus" size="xs">` opens a `<flux:modal>` with fields: name, code, color picker, description, hourly rate, billable checkbox, requires materials, parent category.
- On modal submit, POST JSON to `route('api.task-categories.quick-store')`. On success, push new category to `categories` array, set `selectedId`.

**Step 3 — Replace the category select in the task form view**

In `form.blade.php` for tasks (`livewire/admin/tasks/form.blade.php`), replace:

```html
<flux:select wire:model="task.task_category_id" ...>
```

with the new component:

```html
<x-tasks::task-category-select
    name="task.task_category_id"
    :project-id="$task->project_id"
    wire:model="task.task_category_id"
/>
```

**Step 4 — Tests and pint**

```bash
php artisan make:test Tasks/QuickCategoryCreateTest --pest --no-interaction
vendor/bin/pint --dirty --format agent
```

Test: `quick-store` creates a category and returns JSON; duplicate `code` returns 422; unauthorized user gets 403.

#### Acceptance Criteria
- Task form category dropdown has a "+" button.
- Clicking "+" opens a modal for quick category creation.
- On creation, the new category appears in the dropdown and is auto-selected.
- The metadata panel shows the selected category's color, description, and billing info.

---

### P2-3 · Inline Task Rename

**What it is:** In the task hierarchy widget, double-clicking a task's title enters edit mode. Pressing Enter or clicking away saves the new title via an async PATCH. Pressing Escape cancels.

**Prototype reference:**
- `resources/views/components/project/task-row.blade.php` — Alpine component with `editing`, `editedTitle`, `saveTitle()`, `cancelEdit()`
- Prototype route: `PATCH /projects/{project}/tasks/{task}/rename`

**Current app touchpoints:**
- Edit: `app/Domains/Tasks/Livewire/Admin/Projects/TaskHierarchyWidget.php` — add `renameTask()` Livewire action
- Edit: `app/Domains/Tasks/Resources/Views/livewire/admin/projects/_task-category-tree-row.blade.php` — add inline rename Alpine state/template

#### Steps

**Step 1 — Add `renameTask()` to `TaskHierarchyWidget`**

In `TaskHierarchyWidget.php`:

```php
public function renameTask(string $taskId, string $title): void
{
    $task = Task::findOrFail($taskId);
    $this->authorize('update', $task);
    $this->validate(['title' => 'required|string|max:255'], [], ['title' => $title]);
    $task->title = strip_tags(trim($title));
    $task->save();
    $this->dispatch('task-renamed', taskId: $taskId, title: $task->title);
}
```

**Step 2 — Add inline rename to `_task-category-tree-row.blade.php`**

For each task row `<td>` that displays the title, wrap the title in an Alpine component:

```html
<td
    x-data="{
        editing: false,
        editedTitle: '{{ $task->title }}',
        originalTitle: '{{ $task->title }}',
        startEdit() { this.editing = true; this.$nextTick(() => this.$refs.titleInput.focus()); },
        cancelEdit() { this.editedTitle = this.originalTitle; this.editing = false; },
        saveEdit() {
            if (this.editedTitle === this.originalTitle) { this.cancelEdit(); return; }
            $wire.renameTask('{{ $task->id }}', this.editedTitle)
                .then(() => { this.originalTitle = this.editedTitle; this.editing = false; });
        }
    }"
    @dblclick="startEdit()"
>
    <span x-show="!editing" x-text="originalTitle"></span>
    <input
        x-ref="titleInput"
        x-show="editing"
        x-model="editedTitle"
        @keydown.enter.prevent="saveEdit()"
        @keydown.escape.prevent="cancelEdit()"
        @blur="saveEdit()"
        class="border rounded px-1 py-0.5 text-sm w-full"
    />
</td>
```

**Step 3 — Tests and pint**

```bash
php artisan make:test Tasks/InlineTaskRenameTest --pest --no-interaction
vendor/bin/pint --dirty --format agent
```

Test: `renameTask()` updates title; strips HTML tags; user without `tasks.edit` gets 403; empty title returns validation error.

#### Acceptance Criteria
- Double-clicking a task title in the hierarchy widget enters edit mode.
- Pressing Enter saves; pressing Escape cancels; clicking away saves.
- The title updates visually without a full page reload.
- HTML is stripped from the saved title (XSS prevention).

---

## Priority 3 (P3) — Build These Last

---

### P3-1 · Admin Task Detail Page

**What it is:** A dedicated read-heavy page for a single task showing full audit trail, subtask list, billable status, and linked project context. Prototype has `admin/tasks/{task}` (GET).

**Prototype reference:**
- `routes/admin/tasks.php` — `GET /admin/tasks/{task}` → `TaskController@show`
- `resources/views/admin/tasks/show.blade.php` — full detail view

**Current app touchpoints:**
- New: `app/Domains/Tasks/Livewire/Admin/Tasks/Show.php`
- New: `app/Domains/Tasks/Resources/Views/livewire/admin/tasks/show.blade.php`
- Edit: `app/Domains/Tasks/Routes/admin.php` — add `GET /{task}` route

#### Steps

**Step 1 — Create `Show` Livewire component**

```bash
php artisan make:livewire Tasks/Admin/Tasks/Show --no-interaction
```

Move to: `app/Domains/Tasks/Livewire/Admin/Tasks/Show.php`

On `mount(Task $task)`: authorize `view` on `$task`, then eager-load:
- `project`, `category`, `parent`, `subTasks` (with their categories), `assignedTo`

**Step 2 — Create the view**

`app/Domains/Tasks/Resources/Views/livewire/admin/tasks/show.blade.php`

Panel sections:
- **Header**: title, status badge, priority badge, breadcrumb (project → category → task)
- **Meta grid**: assigned user, due date, estimated hours, completion %, billable flag
- **Description**: long-form text
- **Sub-tasks table**: title, status, assigned, due date — with link to edit each
- **Back to project** and **Edit** action buttons

**Step 3 — Add route**

In `admin.php`, inside the `tasks` prefix group:

```php
Route::get('/{task}', Show::class)->name('show');
```

**Step 4 — Add "View" link to the task index**

In `index.blade.php`, add a "View" button alongside the existing "Edit" and "Delete" actions linking to `route('admin.tasks.show', $task)`.

**Step 5 — Tests and pint**

```bash
php artisan make:test Tasks/AdminTaskShowTest --pest --no-interaction
vendor/bin/pint --dirty --format agent
```

#### Acceptance Criteria
- `GET /admin/tasks/{task}` renders the task detail page.
- Sub-tasks are listed with their own status/edit links.
- User without `tasks.view` is redirected.

---

### P3-2 · Extended Analytics Panel (Weighted Progress + Billable Summaries)

**What it is:** The task hierarchy widget surfaces advanced analytics: weighted overall progress (by estimated hours), billable task summaries (completed value, ready-to-invoice amount), and per-category progress bars. Prototype has this in `task-progress.blade.php`.

**Prototype reference:**
- `resources/views/components/project/task-progress.blade.php` — all calculation logic is in a Blade `@php` block

**Current app touchpoints:**
- New: `app/Domains/Tasks/Services/TaskAnalyticsService.php`
- Edit: `app/Domains/Tasks/Services/ProjectTaskHierarchyViewDataService.php` — add analytics data
- Edit: `app/Domains/Tasks/Livewire/Admin/Projects/TaskHierarchyWidget.php` — expose analytics properties
- Edit: `app/Domains/Tasks/Resources/Views/livewire/admin/projects/task-hierarchy-widget.blade.php` — add analytics panel

#### Steps

**Step 1 — Create `TaskAnalyticsService`**

```bash
php artisan make:class Domains/Tasks/Services/TaskAnalyticsService --no-interaction
```

Methods:

```php
// Returns value 0–100
public function weightedProgress(Collection $tasks): float
{
    $totalWeight = $tasks->sum('estimated_hours') ?: $tasks->count();
    if ($totalWeight === 0) { return 0.0; }
    return $tasks->sum(fn ($t) => ($t->estimated_hours ?? 1) * ($t->completion_percentage / 100))
        / $totalWeight * 100;
}

// Returns ['completed_value', 'ready_to_invoice', 'count']
public function billableSummary(Collection $tasks): array
{
    $billable = $tasks->where('is_billable', true);
    return [
        'completed_value'  => $billable->where('status', Task::STATUS_COMPLETED)->sum(fn($t) => ($t->estimated_hours ?? 0) * ($t->hourly_rate ?? 0)),
        'ready_to_invoice' => $billable->where('status', Task::STATUS_COMPLETED)->where('invoiced', false)->sum(...),
        'count'            => $billable->count(),
    ];
}

// Returns Collection of ['category' => TaskCategory, 'progress' => float]
public function categoryProgress(Collection $categories, Collection $tasks): Collection
{
    return $categories->map(fn ($cat) => [
        'category' => $cat,
        'progress' => $this->weightedProgress($tasks->where('task_category_id', $cat->id)->values()),
    ]);
}
```

**Step 2 — Inject analytics into `ProjectTaskHierarchyViewDataService`**

In `ProjectTaskHierarchyViewDataService`, call `TaskAnalyticsService` after the tasks collection is built and add `analytics` to the returned array.

**Step 3 — Add analytics properties to `TaskHierarchyWidget`**

Add computed properties using `#[Computed]` or standard properties populated in `mount()`:
- `$overallProgress` (float)
- `$billableSummary` (array)
- `$categoryProgress` (collection)

**Step 4 — Add the analytics panel to the widget view**

Above the category/task tree in `task-hierarchy-widget.blade.php`, add an expandable analytics section (collapsed by default):

```html
<div x-data="{ open: false }">
    <button @click="open = !open" class="...">Project Progress ▾</button>
    <div x-show="open" x-collapse>
        <!-- Overall weighted progress bar -->
        <!-- Billable summary cards (only if $billableSummary['count'] > 0) -->
        <!-- Per-category progress rows -->
    </div>
</div>
```

**Step 5 — Tests and pint**

```bash
php artisan make:test Tasks/TaskAnalyticsServiceTest --pest --unit --no-interaction
vendor/bin/pint --dirty --format agent
```

Test: `weightedProgress` with known weights; `billableSummary` counts correctly; zero-task edge case returns 0.

#### Acceptance Criteria
- Widget shows a "Project Progress" toggle.
- Opening it reveals overall weighted progress bar.
- If billable tasks exist, a billable summary card appears.
- Per-category progress bars appear below.

---

## Quick-Reference Checklist

| # | Feature | P | Status |
|---|---------|---|--------|
| P1-1 | User task list + status update | P1 | ☐ |
| P1-2 | Mobile task list + create + edit | P1 | ☐ |
| P1-3 | CSV template download + import | P1 | ☐ |
| P1-4 | Drag-and-drop task reorder | P1 | ☐ |
| P2-1 | Template apply-to-project | P2 | ☐ |
| P2-2 | Quick category create + enhanced selector | P2 | ☐ |
| P2-3 | Inline task rename | P2 | ☐ |
| P3-1 | Admin task detail page | P3 | ☐ |
| P3-2 | Extended analytics panel | P3 | ☐ |

---

## Shared Notes

- **CSRF**: All non-Livewire AJAX calls (SortableJS reorder, inline rename fetch, quick-store) must include `X-CSRF-TOKEN` from `document.querySelector('meta[name="csrf-token"]').content`.
- **Authorisation**: Each new action must go through the existing `TaskPolicy` / `TaskCategoryPolicy`. Never add raw role checks.
- **Cache**: Any action that creates, renames, or reorders a `Task` or `TaskCategory` must call `TaskTreeService::clearCache($project->id)` (via the `TaskCategoryObserver` for categories; manually for tasks if not already covered by an observer).
- **Pint**: Run `vendor/bin/pint --dirty --format agent` after every PHP file change.
- **Tests**: Every feature must have at least one passing Pest feature test before being considered done. Run with `php artisan test --compact`.
