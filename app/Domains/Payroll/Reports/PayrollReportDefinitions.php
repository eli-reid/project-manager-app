<?php

namespace App\Domains\Payroll\Reports;

class PayrollReportDefinitions
{
    public const CERTIFIED_WH347 = 'financial.payroll-certified-wh347';

    public const TAX_FILINGS = 'financial.payroll-tax-filings';

    public const LABOR_COST = 'financial.payroll-labor-cost';

    public const UNION_REMITTANCE = 'financial.payroll-union-remittance';

    public const AUDIT_TRAIL = 'financial.payroll-audit-trail';

    public const WEEKLY_EMPLOYEE_HOURS = 'operational.payroll-weekly-employee-hours';

    public const WEEKLY_HOUR_ADJUSTMENTS = 'operational.payroll-weekly-hour-adjustments';

    /**
     * @return array<int, array{key:string,section:string,title:string,description:string,route:string,badge_label:string,badge_color:string,sort:int}>
     */
    public static function all(): array
    {
        return [
            [
                'key' => self::CERTIFIED_WH347,
                'section' => 'financial',
                'title' => 'Certified Payroll (WH-347)',
                'description' => 'Generate certified payroll by project and week.',
                'route' => 'reports.payroll.certified.index',
                'badge_label' => 'Compliance',
                'badge_color' => 'amber',
                'sort' => 60,
            ],
            [
                'key' => self::TAX_FILINGS,
                'section' => 'financial',
                'title' => 'Payroll Tax Filings (941 and W-2)',
                'description' => 'Generate quarterly and annual payroll tax filing datasets.',
                'route' => 'reports.payroll.tax-filings.index',
                'badge_label' => 'Compliance',
                'badge_color' => 'amber',
                'sort' => 70,
            ],
            [
                'key' => self::LABOR_COST,
                'section' => 'financial',
                'title' => 'Payroll Labor Cost by Project and Cost Code',
                'description' => 'Analyze payroll labor cost by project, cost code, and employee.',
                'route' => 'reports.payroll.labor-cost.index',
                'badge_label' => 'Financial',
                'badge_color' => 'green',
                'sort' => 80,
            ],
            [
                'key' => self::UNION_REMITTANCE,
                'section' => 'financial',
                'title' => 'Union Remittance',
                'description' => 'Generate union remittance reports and export-ready files.',
                'route' => 'reports.payroll.union-remittance.index',
                'badge_label' => 'Compliance',
                'badge_color' => 'amber',
                'sort' => 90,
            ],
            [
                'key' => self::AUDIT_TRAIL,
                'section' => 'financial',
                'title' => 'Payroll Audit Trail',
                'description' => 'Review payroll mutations and digest chain integrity status.',
                'route' => 'reports.payroll.audit.index',
                'badge_label' => 'Audit',
                'badge_color' => 'red',
                'sort' => 100,
            ],
            [
                'key' => self::WEEKLY_EMPLOYEE_HOURS,
                'section' => 'operational',
                'title' => 'Weekly Employee Hours',
                'description' => 'Review and export weekly employee totals for payroll approval.',
                'route' => 'admin.payroll.reports.weekly-employee-hours',
                'badge_label' => 'Payroll',
                'badge_color' => 'sky',
                'sort' => 25,
                'ability' => 'payroll-runs.preview',
            ],
            [
                'key' => self::WEEKLY_HOUR_ADJUSTMENTS,
                'section' => 'operational',
                'title' => 'Weekly Hour Adjustment Report',
                'description' => 'Review weekly hour overrides that are tracked separately from timecards.',
                'route' => 'admin.payroll.reports.weekly-hour-adjustments',
                'badge_label' => 'Payroll',
                'badge_color' => 'orange',
                'sort' => 26,
                'ability' => 'payroll-runs.preview',
            ],
        ];
    }
}
