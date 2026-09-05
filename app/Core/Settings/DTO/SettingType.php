<?php

namespace App\Core\Settings\DTO;

enum SettingType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Array = 'array';
    case Json = 'json';
    case datetime = 'datetime';
}