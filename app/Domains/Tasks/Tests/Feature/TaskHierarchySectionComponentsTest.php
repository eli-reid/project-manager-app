<?php

use App\Domains\Tasks\Livewire\Admin\Projects\TaskHierarchyMetrics;
use App\Domains\Tasks\Livewire\Admin\Projects\TaskHierarchyTemplates;
use Livewire\Livewire;

it('renders task hierarchy metrics cards', function (): void {
    Livewire::test(TaskHierarchyMetrics::class, [
        'cards' => [
            [
                'label' => 'Total',
                'value' => '12',
                'valueClass' => 'text-zinc-900 dark:text-zinc-100',
            ],
            [
                'label' => 'Completed',
                'value' => '4',
                'valueClass' => 'text-emerald-600 dark:text-emerald-400',
            ],
        ],
    ])
        ->assertSee('Total')
        ->assertSee('12')
        ->assertSee('Completed')
        ->assertSee('4');
});

it('renders task hierarchy templates list', function (): void {
    Livewire::test(TaskHierarchyTemplates::class, [
        'templates' => [
            [
                'id' => 'template-1',
                'name' => 'Closeout Checklist',
                'priorityLabel' => 'High',
            ],
        ],
        'manageUrl' => route('admin.task-templates.index'),
    ])
        ->assertSee('Task Templates')
        ->assertSee('Manage Templates')
        ->assertSee('Closeout Checklist')
        ->assertSee('(High)');
});
