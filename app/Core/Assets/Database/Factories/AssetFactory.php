<?php

declare(strict_types=1);

namespace App\Core\Assets\Database\Factories;

use App\Core\Assets\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->slug(3).'.pdf';

        return [
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 1024 * 512),
            'storage_disk' => 'local',
            'storage_path' => 'assets/'.$name,
            'folder_path' => null,
            'content_hash' => hash('sha256', fake()->unique()->uuid()),
            'created_by_id' => null,
        ];
    }
}
