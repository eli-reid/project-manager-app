<?php

namespace App\Domains\Timecards\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\TimecardRequiredUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimecardRequiredUser>
 */
class TimecardRequiredUserFactory extends Factory
{
    protected $model = TimecardRequiredUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reminders_enabled' => true,
            'effective_start_date' => null,
            'effective_end_date' => null,
        ];
    }

    /**
     * Mark reminders as disabled for this user.
     */
    public function remindersDisabled(): self
    {
        return $this->state([
            'reminders_enabled' => false,
        ]);
    }

    /**
     * Set effective date range for timecard requirement.
     */
    public function effectiveDates(\DateTime $start, ?\DateTime $end = null): self
    {
        return $this->state([
            'effective_start_date' => $start,
            'effective_end_date' => $end,
        ]);
    }
}
