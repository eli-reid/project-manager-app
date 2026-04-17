<?php

use App\Domains\Projects\Models\Project;
use Database\Seeders\DatabaseSeeder;

it('does not seed demo domain data when demo seed flag is disabled', function (): void {
    config()->set('database.seed_demo_data', false);

    $this->seed(DatabaseSeeder::class);

    expect(Project::query()->count())->toBe(0);
});
