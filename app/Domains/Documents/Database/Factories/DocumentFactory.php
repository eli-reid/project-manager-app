<?php

namespace App\Domains\Documents\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->lexify('document-????').'.pdf';

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'original_name' => $name,
            'stored_name' => $name,
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(5120, 2500000),
            'storage_disk' => 'local',
            'storage_path' => 'documents/user/'.$name,
            'owner_scope' => Document::OWNER_SCOPE_USER,
            'owner_id' => User::factory(),
            'visibility' => Document::VISIBILITY_PRIVATE,
            'replace_mode' => Document::REPLACE_MODE_REPLACE,
            'uploaded_by_id' => User::factory(),
        ];
    }

    public function global(): static
    {
        return $this->state([
            'visibility' => Document::VISIBILITY_GLOBAL,
        ]);
    }

    public function projectOwned(): static
    {
        return $this->state([
            'owner_scope' => Document::OWNER_SCOPE_PROJECT,
            'owner_id' => Project::factory(),
            'visibility' => Document::VISIBILITY_PROJECT,
        ]);
    }
}
