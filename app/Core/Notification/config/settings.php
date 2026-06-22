<?php

use App\Core\Settings\DTO\SettingFormFieldType;
use App\Core\Settings\DTO\SettingType;
use App\Core\Settings\DTO\Setting;


return [

   new Setting(
        $value = 'true',
        $display_name = 'Enable Notifications',
        $description = 'Master switch for notification delivery.',
        $type = 'select',
        $group = self::GROUP,
        $options = ['true' => 'Enabled', 'false' => 'Disabled'],
        $order = 1,
        $is_visible = true,
        $is_public = false,
        $is_required = true,
        $encrypted = false,
    ),

];
