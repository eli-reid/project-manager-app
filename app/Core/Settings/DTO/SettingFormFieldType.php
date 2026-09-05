<?php

namespace App\Core\Settings\DTO;

enum SettingFormFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Toggle = 'toggle';
    case Number = 'number';
    case Password = 'password';
    case Json = 'json';
    case Datetime = 'datetime';
}
