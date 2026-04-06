<?php

namespace App\Core\Announcement\Database\Factories;

use App\Core\Announcement\Enums\AnnouncementType;
use App\Core\Announcement\Models\Announcement;
use App\Core\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'type' => fake()->randomElement(array_column(AnnouncementType::options(), 'value')),
            'is_active' => true,
            'is_dismissable' => fake()->boolean(),
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'created_by' => User::factory(),
        ];
    }
}
