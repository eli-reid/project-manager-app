# Plans Domain - Authorization and Settings

## Permissions

`app/Domains/Plans/Permissions/PlanPermissions.php`, following the exact shape of
`app/Domains/Documents/Permissions/DocumentPermissions.php`: public const arrays plus a static
`all()`.

| Constant | Resource | Action | Grants |
| --- | --- | --- | --- |
| `VIEW` | plans | view | See the plans tab, sheet index, and viewer |
| `UPLOAD` | plans | upload | Upload a new plan set |
| `UPDATE` | plans | update | Edit set metadata, sheet numbers, titles, ordering |
| `DELETE` | plans | delete | Delete sets and sheets |
| `PUBLISH_REVISION` | plans | publish-revision | Promote or roll back the current revision |
| `ANNOTATE` | plans | annotate | Create and edit own markup |
| `MANAGE_ANNOTATIONS` | plans | manage-annotations | Edit, resolve, or delete others' markup |
| `MANAGE_LINKS` | plans | manage-links | Create and confirm sheet hyperlinks |
| `COMPARE` | plans | compare | Use revision comparison |
| `EXPORT` | plans | export | Export sheets or sets with burned-in markup |

Registration in `PlansServiceProvider`:

```php
public function boot(
    PermissionRegistryContract $permissionRegistry,
    SettingsRegistryContract $settingsRegistry,
    AssetReferencerRegistry $assetRegistry,
    ProjectTabRegistry $projectTabRegistry,
): void {
    $this->registerPermissions($permissionRegistry);
    // ...
}

private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
{
    $permissionRegistry->registerPermissions(PlanPermissions::all());
}
```

The DI-in-boot form is mandatory. Resolving the registry with `$this->app->make(...)` inside
provider methods is a known boot-order hazard in this repo.

## Policies

Four policies in `app/Domains/Plans/Policies`, registered in the provider's `boot`.

| Policy | Abilities |
| --- | --- |
| `PlanSetPolicy` | viewAny, view, create, update, delete |
| `PlanSheetPolicy` | view, update, delete, publishRevision, compare, export |
| `PlanAnnotationPolicy` | viewAny, create, update, delete, resolve |
| `PlanLinkPolicy` | create, update, delete, confirm |

**Every ability follows the same two-gate shape:**

1. Can this user see this project at all?
2. Does this user hold the relevant `plans.*` permission?

Project visibility is resolved through a thin contract call into the Projects domain rather than
querying project tables directly, per the domain boundary rule in `AGENTS.md`.

Annotation edit and delete additionally allow the author regardless of
`plans.manage-annotations`:

```php
public function update(User $user, PlanAnnotation $annotation): bool
{
    if (! $this->canReachSheet($user, $annotation->sheet)) {
        return false;
    }

    return $annotation->author_id === $user->id
        ? $user->hasPermission('plans.annotate')
        : $user->hasPermission('plans.manage-annotations');
}
```

## Asset Access Resolver

The source PDF is an `Asset`, so the Plans domain must register a resolver or nobody will be able
to download the original set.

`app/Domains/Plans/Services/PlanAssetAccessResolver.php` implements
`App\Core\Assets\Contracts\AssetAccessResolver` and delegates to `PlanSetPolicy`:

```php
public function canView(User $user, Asset $asset, AssetReference $reference): bool
{
    $planSet = PlanSet::query()->where('source_asset_id', $asset->id)->first();

    return $planSet !== null && $this->policy->view($user, $planSet);
}
```

Registered in the provider:

```php
private function registerAssetIntegration(AssetReferencerRegistry $assetRegistry): void
{
    $assetRegistry->register('plans', PlanAssetAccessResolver::class);
}
```

The referencer type string `'plans'` is a stable registry key and must never be a class FQN.

## Project Tab

`app/Domains/Plans/Support/PlansProjectTab.php` extends `ProjectTab`:

```php
final class PlansProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'plans',
            label: 'Plans',
            sort: 95,
            panel: new LivewireComponentTabPanel(
                component: 'plans::admin.projects.plans-tab',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAny', PlanSet::class);
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return null;
    }
}
```

**Breaking change:** `app/Domains/Documents/Support/PlansProjectTab.php` must be removed from
`DocumentsServiceProvider::registerProjectTabs()` in the same change that registers this one. Two
definitions sharing the `plans` key will collide in `ProjectTabRegistry`.

## Settings

`app/Domains/Plans/config/settings.php`, registered via
`$settingsRegistry->registerConfigFile('plans', __DIR__.'/../config/settings.php')`.
Each entry uses the full definition shape
(`key`, `value`, `display_name`, `description`, `type`, `group`, `order`, `is_visible`,
`is_public`, `is_required`, `encrypted`).

| Key | Type | Default | Purpose |
| --- | --- | --- | --- |
| `plans.enabled` | select | `false` | Feature flag for dark launch |
| `plans.rasterizer_driver` | select | `poppler` | `poppler` or `imagick` |
| `plans.poppler_bin_path` | text | `''` | Directory holding `pdftoppm` and `pdfinfo` |
| `plans.render_dpi` | number | `150` | Rasterization DPI for the preview |
| `plans.thumbnail_width` | number | `320` | Thumbnail pixel width |
| `plans.preview_width` | number | `2000` | Preview pixel width |
| `plans.enable_tiles` | select | `false` | Generate zoom tiles for very large sheets |
| `plans.derivative_format` | select | `webp` | `webp` or `png` |
| `plans.storage_disk` | select | `local` | Never `public` |
| `plans.max_pages_per_set` | number | `500` | Ingestion guard |
| `plans.max_upload_kilobytes` | number | `512000` | Upload size ceiling |
| `plans.title_block_region` | text | `bottom-right` | Region hint for detection |
| `plans.sheet_number_pattern` | text | see below | Regex for candidate sheet numbers |

Default pattern: `^[A-Z]{1,3}[-.]?\d{1,3}(\.\d+)?$`

Read through the facade, matching existing domains:

```php
$dpi = Settings::get('plans.render_dpi', 150)->toInt();
```

No secrets belong in this domain's settings. `encrypted` stays `false` throughout, and no API
credentials are introduced until the deferred OCR phase - at which point they belong in
`config/services.php` sourced from environment variables, never in the settings table.

## Validation Rules

Upload validation reuses the asset registry override mechanism so the Plans domain can require
PDFs specifically while other domains keep broader allowances:

```php
$assetRegistry->registerValidationRules('plans', [
    'allowed_extensions' => ['pdf'],
    'max_kilobytes' => Settings::get('plans.max_upload_kilobytes', 512000)->toInt(),
]);
```
