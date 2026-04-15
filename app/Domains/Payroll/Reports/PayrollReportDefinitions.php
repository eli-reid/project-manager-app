<?php

namespace App\Domains\Payroll\Reports;

class PayrollReportDefinitions
{
    /**
     * @return array<int, array{key:string,section:string,title:string,description:string,route:string,badge_label:string,badge_color:string,sort:int}>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'financial.payroll-certified-wh347',
                'section' => 'financial',
                'title' => 'Certified Payroll (WH-347)',
                'description' => 'Generate certified payroll by project and week.',
                'route' => 'reports.payroll.certified.index',
                'badge_label' => 'Compliance',
                'badge_color' => 'amber',
                'sort' => 60,
            ],
            [
                'key' => 'financial.payroll-tax-filings',
                'section' => 'financial',
                'title' => 'Payroll Tax Filings (941 and W-2)',
                'description' => 'Generate quarterly and annual payroll tax filing datasets.',
                'route' => 'reports.payroll.tax-filings.index',
                'badge_label' => 'Compliance',
                'badge_color' => 'amber',
                'sort' => 70,
            ],
            [
                'key' => 'financial.payroll-labor-cost',
                'section' => 'financial',
                'title' => 'Payroll Labor Cost by Project and Cost Code',
                'description' => 'Analyze payroll labor cost by project, cost code, and employee.',
                'route' => 'reports.payroll.labor-cost.index',
                'badge_label' => 'Financial',
                'badge_color' => 'green',
                'sort' => 80,
            ],
            [
                'key' => 'financial.payroll-union-remittance',
                'section' => 'financial',
                'title' => 'Union Remittance',
                'description' => 'Generate union remittance reports and export-ready files.',
                'route' => 'reports.payroll.union-remittance.index',
                'badge_label' => 'Compliance',
                'badge_color' => 'amber',
                'sort' => 90,
            ],
            [
                'key' => 'financial.payroll-audit-trail',
                'section' => 'financial',
                'title' => 'Payroll Audit Trail',
                'description' => 'Review payroll mutations and digest chain integrity status.',
                'route' => 'reports.payroll.audit.index',
                'badge_label' => 'Audit',
                'badge_color' => 'red',
                'sort' => 100,
            ],
            [
                'key' => 'operational.payroll-weekly-employee-hours',
                'section' => 'operational',
                'title' => 'Weekly Employee Hours',
                'description' => 'Review and export weekly employee totals for payroll approval.',
                'route' => 'admin.payroll.reports.weekly-employee-hours',
                'badge_label' => 'Payroll',
                'badge_color' => 'sky',
                'sort' => 25,
                'ability' => 'payroll-runs.preview',
            ],
        ];
    }
}
