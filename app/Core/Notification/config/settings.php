<?php

use App\Core\Settings\DTO\SettingFormFieldType;
use App\Core\Settings\DTO\SettingType;
use App\Core\Settings\DTO\Setting;


return [

   new Setting(
        $key = 'notifications.enabled',
        $type = SettingType::BOOLEAN,
        $formFieldType = SettingFormFieldType::TOGGLE,
        $value = 'true',
        $display_name = 'Enable Notifications',
        $description = 'Master switch for notification delivery.',
        $group = 'notifications',       
        $options = null,
        $order = 1,
        $is_visible = true,
        $is_public = false,
        $is_required = true,
        $encrypted = false,
    ),

];
