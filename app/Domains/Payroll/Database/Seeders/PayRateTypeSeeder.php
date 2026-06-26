<?php

namespace App\Domains\Payroll\Database\Seeders;

use App\Domains\Payroll\Models\PayRateType;
use Illuminate\Database\Seeder;

class PayRateTypeSeeder extends Seeder
{
    /**
     * @var array<int, array{key: string, name: string, description: string, is_system: bool, sort_order: int}>
     */
    private const SYSTEM_TYPES = [
        [
            'key' => 'standard',
            'name' => 'Standard',
            'description' => 'Default base hourly rate.',
            'is_system' => true,
            'sort_order' => 10,
        ],
        [
            'key' => 'prevailing_base',
            'name' => 'Prevailing Wage Base',
            'description' => 'Base hourly rate for prevailing wage projects (Davis-Bacon / SCA).',
            'is_system' => true,
            'sort_order' => 20,
        ],
        [
            'key' => 'prevailing_fringe',
            'name' => 'Prevailing Wage Fringe',
            'description' => 'Fringe benefit rate for prevailing wage projects.',
            'is_system' => true,
            'sort_order' => 30,
        ],
    ];

    public function run(): void
    {
        foreach (self::SYSTEM_TYPES as $type) {
            PayRateType::query()->updateOrCreate(
                ['key' => $type['key']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}
