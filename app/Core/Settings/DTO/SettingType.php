<?php

namespace App\Core\Settings\DTO;

enum SettingType: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case BOOLEAN = 'boolean';
    case ARRAY = 'array';
    case JSON = 'json';
    case DATETIME = 'datetime';
}