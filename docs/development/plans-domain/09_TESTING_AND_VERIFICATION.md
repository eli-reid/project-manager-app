# Plans Domain - Testing and Verification

## Framework and Placement

Pest 4. Domain tests live in `app/Domains/Plans/Tests/Feature`, already covered by the "Domain"
testsuite in `phpunit.xml` which scans `app/Domains` for the `Test.php` suffix. No phpunit
changes are needed.

`RefreshDatabase` is applied globally in `tests/Pest.php`. The test environment uses SQLite
in-memory with `QUEUE_CONNECTION=sync`.

Create tests with `php artisan make:test --pest {name}`.

## Permission Helper

Follow the existing per-domain helper pattern from
`app/Domains/Documents/Tests/Feature/PlansProjectTabTest.php`. Guard with `function_exists` if it
ever moves to `tests/Pest.php`, to avoid redeclaration errors.

```php
function userWithPlanPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Plans Test Role '.str()->uuid(),
        'description' => 'Role for plans domain tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            return Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
```

## Testing The Pipeline Without Binaries

`NullRasterizer` is bound in the testing environment, so the full ingest, split, render, and
finalize flow runs in CI with no Poppler or Imagick installed. This is the single most important
testability decision in the design.

Rendered-state assertions use `PlanSheetRevisionFactory::rendered()` rather than real image files.

For extraction tests, add a small multi-page fixture PDF at `tests/fixtures/sample-plan-set.pdf`,
mirroring the existing `tests/fixtures/sample-invoice.pdf` approach.

## Coverage By Area

| Area | Must be tested |
| --- | --- |
| Rasterizer | Driver selection from settings, availability reporting, `Process` receives array args |
| Ingestion | Asset created with a `plans` reference, set row created, job dispatched |
| Split | Correct sheet and revision count, max-pages guard rejects, batch dispatched |
| Render | Paths and dimensions written, failure records status and message, retry config present |
| Matching | Existing sheet gains a revision, unmatched page creates a sheet, only one current revision |
| Detection | Number and title extracted, confidence recorded, manual override survives re-detection |
| Delivery | 403 for a project the user cannot access, 404 for a missing file, ETag present |
| Policies | Full matrix across all ten permissions, including author-versus-manager on annotations |
| Index | Rendering, search, filters, natural sheet ordering, permission-gated upload |
| Viewer | State, revision switching, view-state persistence |
| Annotations | Geometry round-trip, authorization, following the sheet across revisions |
| Links | CRUD authorization, auto-detected flagging, navigation target resolution |
| Compare | Permission gating, revision pair selection |
| Mobile | Component rendering, route prefix mapping, gesture behavior |

## Browser Tests

In `tests/Browser/`. Every browser test must assert `assertNoJavaScriptErrors()` - the canvas
viewer is the most JavaScript-heavy surface in the application and silent errors there are easy to
miss.

Key scenarios:

- Viewer loads, zooms, and navigates to the next sheet.
- Draw an annotation, reload, and confirm it persists in the same position.
- Compare mode renders all three modes.
- Sheet jump palette filters and navigates.
- Mobile viewport: pinch zoom changes the transform, swipe navigates only when zoomed to fit.

A visual regression test on the viewer chrome is worthwhile once the layout stabilizes.

## Architecture Tests

```php
arch('plans domain does not reach into other domain internals')
    ->expect('App\Domains\Plans')
    ->not->toUse([
        'App\Domains\Documents\Models',
        'App\Domains\Tasks\Models',
    ]);
```

This enforces the domain boundary rule from `AGENTS.md` and catches the most likely violation -
reaching directly into the Tasks models when wiring `linked_task_id`.

## Known Repo Test Caveats

Recorded from prior sessions. Expect these and route around them rather than debugging from
scratch:

- Full-page route tests can fail with `No hint path defined for [layouts]` when resolving
  `layouts::app`. Prefer `Livewire::test(Component::class)` for isolated component assertions.
- Admin project route assertions have hit a `layouts.head` multiple-root detection failure. Same
  workaround applies.
- `Route::get(..., Component::class)` for a Livewire component causes
  `Invalid route action` bootstrap failures that break unrelated tests. Always use
  `Route::livewire(...)`.
- Sync-mode terminal runs in this workspace have occasionally returned only a continuation
  prompt. Async mode worked for Pint and test runs.

## Commands

```powershell
php artisan test --compact --filter=Plans
php artisan test --compact app/Domains/Plans/Tests/Feature/PlanPipelineTest.php
php artisan test --compact
vendor/bin/pint --dirty --format agent
php artisan plans:rasterizer-check
```

## Manual Verification Before Sign-Off

1. Upload a real 100+ page set. Sheets appear incrementally rather than after a long wait.
2. Sheet numbers are auto-detected on a majority of pages; corrections stick.
3. Panning and zooming stay smooth at roughly 60fps on a large sheet.
4. Re-upload a revised set. Sheets gain revisions instead of duplicating.
5. Difference compare visibly highlights a known change.
6. Markup drawn on desktop appears correctly positioned on mobile.
7. Mobile pinch zoom and swipe navigation behave per the gesture rules.
8. A user without project access receives 403 on a derivative URL taken from another project.
9. No secrets appear in settings output, logs, or the rasterizer check.
