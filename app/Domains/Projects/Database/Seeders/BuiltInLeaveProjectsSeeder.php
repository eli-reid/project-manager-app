<?php

namespace App\Domains\Projects\Database\Seeders;

use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Seeder;

class BuiltInLeaveProjectsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLeaveProject(
            projectNumber: Project::BUILT_IN_SICK_PROJECT_NUMBER,
            name: 'Sick Time',
            leaveCategory: 'sick',
            description: 'Built-in project for recording paid sick leave time entries.'
        );

        $this->seedLeaveProject(
            projectNumber: Project::BUILT_IN_VACATION_PROJECT_NUMBER,
            name: 'Vacation Time',
            leaveCategory: 'vacation',
            description: 'Built-in project for recording paid vacation leave time entries.'
        );
    }

    private function seedLeaveProject(string $projectNumber, string $name, string $leaveCategory, string $description): void
    {
        Project::query()->updateOrCreate(
            ['project_number' => $projectNumber],
            [
                'name' => $name,
                'description' => $description,
                'status' => ProjectStatusEnum::ACTIVE->value,
                'is_active' => true,
                'leave_category' => $leaveCategory,
            ]
        );
    }
}
