<?php

namespace App\Domains\Documents\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentShare;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentShare>
 */
class DocumentShareFactory extends Factory
{
    protected $model = DocumentShare::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'created_by_id' => User::factory(),
            'share_token' => DocumentShare::generateShareToken(),
            'share_password' => null,
            'expires_at' => $this->faker->optional()->dateTimeBetween('+1 day', '+30 days'),
            'max_downloads' => $this->faker->optional()->numberBetween(1, 100),
            'download_count' => 0,
            'is_active' => true,
            'access_notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function withPassword(string $password = 'test123'): self
    {
        return $this->state([
            'share_password' => hash('sha256', $password),
        ]);
    }

    public function expired(): self
    {
        return $this->state([
            'expires_at' => now()->subDay(),
        ]);
    }

    public function disabled(): self
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function withDownloadLimit(int $limit): self
    {
        return $this->state([
            'max_downloads' => $limit,
        ]);
    }

    public function downloadLimitReached(int $limit): self
    {
        return $this->state([
            'max_downloads' => $limit,
            'download_count' => $limit,
        ]);
    }
}
