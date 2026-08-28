<?php

namespace App\Core\Settings\DTO;

readonly class Setting
{
    public function __construct(
        public string $key,
        public SettingType $type,
        public SettingFormFieldType $formFieldType,
        public mixed $value = null,
        public ?string $display_name = null,
        public ?string $description = null,
        public ?string $group = null,
        public ?array $options = null,
        public int $order = 100,
        public bool $is_visible = true,
        public bool $is_public = false,
        public bool $is_required = false,
        public bool $encrypted = false
    ) { 

    }
}




