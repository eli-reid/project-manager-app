<?php

namespace App\Core\Settings\DTO;

enum SettingFormFieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case TOGGLE = 'toggle';
    case NUMBER = 'number';
    case PASSWORD = 'password';
    case JSON = 'json';
    case DATETIME = 'datetime';
}
