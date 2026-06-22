<?php

namespace App\Domains\Payroll\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class WeeklyEmployeeHoursPdfService
{
    public function generate(Collection $employeeHours, string $weekStart, string $weekEnd): string
    {
        $pdf = Pdf::loadView('payroll::pdf.weekly-employee-hours', [
            'employeeHours' => $employeeHours,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
        ]);
        $path = storage_path('app/reports/WeeklyEmployeeHoursReport_'.$weekStart.'.pdf');
        $pdf->save($path);

        return $path;
    }
}
