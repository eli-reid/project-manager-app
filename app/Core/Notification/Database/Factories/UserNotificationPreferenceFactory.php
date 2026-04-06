<?php

namespace App\Core\Notification\Database\Factories;

use App\Core\Identity\Models\User;
use App\Core\Notification\Models\UserNotificationPreference;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotificationPreference>
 */
class UserNotificationPreferenceFactory extends Factory
{
    protected $model = UserNotificationPreference::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notification_key' => TimecardNotificationDefinitions::APPROVED,
            'channel' => 'mail',
            'enabled' => true,
        ];
    }
}
