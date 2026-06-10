<?php

use App\Core\Identity\Models\User;
use App\Domains\Assets\Models\Asset;
use App\Domains\Projects\Livewire\Admin\Projects\AssetsTab;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectAsset;
use Livewire\Livewire;

it('renders project library metrics and asset rows', function (): void {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    $asset = Asset::query()->create([
        'title' => 'Foundation Specs',
        'original_name' => 'foundation-specs.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 2048,
        'storage_disk' => 'public',
        'storage_path' => 'project-assets/foundation-specs.pdf',
        'created_by_id' => $user->id,
    ]);

    ProjectAsset::query()->create([
        'id' => (string) str()->ulid(),
        'project_id' => $project->id,
        'asset_id' => $asset->id,
        'created_by_id' => $user->id,
        'title' => 'Specs Sheet',
    ]);

    Livewire::test(AssetsTab::class, ['project' => $project])
        ->assertSee('Project Library')
        ->assertSee('Total Files')
        ->assertSee('Specs Sheet')
        ->assertSee('application/pdf')
        ->assertSee('Download');
});

it('renders a clear empty state when the project library has no files', function (): void {
    $project = Project::factory()->create();

    Livewire::test(AssetsTab::class, ['project' => $project])
        ->assertSee('No files in this library yet.')
        ->assertSee('Upload the first file using the panel above.');
});
