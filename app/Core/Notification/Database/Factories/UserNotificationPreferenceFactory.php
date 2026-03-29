<?php

namespace App\Core\Notification\Database\Factories;

use App\Core\Notification\Models\UserNotificationPreference;
use App\Core\User\Models\User;
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
            'notification_key' => 'timecards.approved',
            'channel' => 'mail',
            'enabled' => true,
        ];
    }
}
