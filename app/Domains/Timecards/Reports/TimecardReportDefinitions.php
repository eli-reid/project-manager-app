<?php

namespace App\Domains\Timecards\Reports;

class TimecardReportDefinitions
{
    /**
     * @return array<int, array{key:string,section:string,title:string,description:string,route:string,badge_label:string,badge_color:string,sort:int}>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'financial.labor-cost-analysis',
                'section' => 'financial',
                'title' => 'Labor Cost Analysis',
                'description' => 'Review labor cost distribution by project and period.',
                'route' => 'reports.financial.labor-cost-analysis.index',
                'badge_label' => 'Available',
                'badge_color' => 'green',
                'sort' => 20,
            ],
            [
                'key' => 'operational.timecard-activity',
                'section' => 'operational',
                'title' => 'Timecard Activity',
                'description' => 'Track submissions, approvals, and workforce activity.',
                'route' => 'timecards.index',
                'badge_label' => 'Operational',
                'badge_color' => 'sky',
                'sort' => 30,
            ],
        ];
    }
}
