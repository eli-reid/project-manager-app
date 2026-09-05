<?php

use App\Core\Identity\Models\User;
use App\Core\Assets\Models\Asset;
use App\Domains\Projects\Livewire\Admin\Projects\AssetsTab;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('uploads files to the public project library disk and links them to the project', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $file = UploadedFile::fake()->create('site-plan.pdf', 64, 'application/pdf');

    $this->actingAs($user);

    Livewire::test(AssetsTab::class, ['project' => $project])
        ->set('title', 'Site Plan')
        ->set('file', $file)
        ->call('upload')
        ->assertHasNoErrors();

    /** @var Asset|null $uploadedAsset */
    $uploadedAsset = Asset::query()->latest('created_at')->first();

    expect($uploadedAsset)->not->toBeNull()
        ->and($uploadedAsset?->storage_disk)->toBe('public')
        ->and($uploadedAsset?->created_by_id)->toBe($user->id);

    Storage::disk('public')->assertExists((string) $uploadedAsset?->storage_path);

    $this->assertDatabaseHas('project_assets', [
        'project_id' => $project->id,
        'asset_id' => $uploadedAsset?->id,
        'title' => 'Site Plan',
    ]);
});
