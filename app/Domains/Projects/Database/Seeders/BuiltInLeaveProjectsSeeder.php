<?php

namespace App\Domains\Projects\Database\Seeders;

use App\Domains\Payroll\Database\Seeders\PayRateTypeSeeder;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Seeder;

class BuiltInLeaveProjectsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PayRateTypeSeeder::class);

        $standardPayRateTypeId = PayRateType::query()
            ->where('key', 'standard')
            ->value('id');

        $this->seedLeaveProject(
            projectNumber: Project::BUILT_IN_SICK_PROJECT_NUMBER,
            name: 'Sick Time',
            description: 'Built-in project for recording paid sick leave time entries.',
            payRateTypeId: $standardPayRateTypeId,
        );

        $this->seedLeaveProject(
            projectNumber: Project::BUILT_IN_VACATION_PROJECT_NUMBER,
            name: 'Vacation Time',
            description: 'Built-in project for recording paid vacation leave time entries.',
            payRateTypeId: $standardPayRateTypeId,
        );
    }

    private function seedLeaveProject(string $projectNumber, string $name, string $description, ?string $payRateTypeId): void
    {
        Project::query()->updateOrCreate(
            ['project_number' => $projectNumber],
            [
                'name' => $name,
                'description' => $description,
                'status' => ProjectStatusEnum::ACTIVE->value,
                'is_active' => true,
                'pay_rate_type_id' => $payRateTypeId,
            ]
        );
    }
}
