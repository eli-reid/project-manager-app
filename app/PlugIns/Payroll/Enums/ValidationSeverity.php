<?php

namespace App\Domains\Payroll\Enums;

enum ValidationSeverity: string
{
    case Block = 'block';
    case Warning = 'warning';

}
