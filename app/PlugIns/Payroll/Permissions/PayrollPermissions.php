<?php

namespace App\Domains\Payroll\Permissions;

class PayrollPermissions
{
    // ─── Reports ─────────────────────────────────────────────────────────────

    public const REPORTS_VIEW = [
        'resource' => 'payroll-reports',
        'action' => 'view',
        'description' => 'View payroll compliance and analytics reports',
    ];

    public const REPORTS_EXPORT = [
        'resource' => 'payroll-reports',
        'action' => 'export',
        'description' => 'Export payroll reports',
    ];

    public const REPORTS_GENERATE = [
        'resource' => 'payroll-reports',
        'action' => 'generate',
        'description' => 'Generate payroll report outputs',
    ];

    public const REPORTS_CERTIFY = [
        'resource' => 'payroll-reports',
        'action' => 'certify',
        'description' => 'Certify payroll reports for submission workflows',
    ];

    public const REPORTS_REMIT = [
        'resource' => 'payroll-reports',
        'action' => 'remit',
        'description' => 'Generate and manage union remittance outputs',
    ];

    public const REPORTS_MANAGE = [
        'resource' => 'payroll-reports',
        'action' => 'manage',
        'description' => 'Manage payroll report configuration and templates',
    ];

    // ─── Employee Master ──────────────────────────────────────────────────────

    public const EMPLOYEES_VIEW = [
        'resource' => 'payroll-employees',
        'action' => 'view',
        'description' => 'View payroll employee profiles',
    ];

    public const EMPLOYEES_CREATE = [
        'resource' => 'payroll-employees',
        'action' => 'create',
        'description' => 'Create payroll employee profiles',
    ];

    public const EMPLOYEES_UPDATE = [
        'resource' => 'payroll-employees',
        'action' => 'update',
        'description' => 'Update payroll employee profiles',
    ];

    public const EMPLOYEES_DEACTIVATE = [
        'resource' => 'payroll-employees',
        'action' => 'deactivate',
        'description' => 'Deactivate or terminate payroll employee profiles',
    ];

    // ─── Timecards ────────────────────────────────────────────────────────────

    public const TIMECARDS_VIEW = [
        'resource' => 'payroll-timecards',
        'action' => 'view',
        'description' => 'View payroll timecards for processing',
    ];

    public const TIMECARDS_SUBMIT = [
        'resource' => 'payroll-timecards',
        'action' => 'submit',
        'description' => 'Submit timecards for payroll processing',
    ];

    public const TIMECARDS_APPROVE = [
        'resource' => 'payroll-timecards',
        'action' => 'approve',
        'description' => 'Approve timecards in the payroll pipeline',
    ];

    public const TIMECARDS_BULK_ENTER = [
        'resource' => 'payroll-timecards',
        'action' => 'bulk-enter',
        'description' => 'Perform crew-level bulk timecard entry as a foreman',
    ];

    // ─── Pay Runs ─────────────────────────────────────────────────────────────

    public const RUNS_PREVIEW = [
        'resource' => 'payroll-runs',
        'action' => 'preview',
        'description' => 'Create and view pay run previews',
    ];

    public const RUNS_APPROVE = [
        'resource' => 'payroll-runs',
        'action' => 'approve',
        'description' => 'Approve pay runs (Controller role)',
    ];

    public const RUNS_FINALIZE = [
        'resource' => 'payroll-runs',
        'action' => 'finalize',
        'description' => 'Finalize and lock pay runs',
    ];

    public const RUNS_VOID = [
        'resource' => 'payroll-runs',
        'action' => 'void',
        'description' => 'Void a finalized pay run (System Admin only)',
    ];

    public const RUNS_ADJUST_HOURS = [
        'resource' => 'payroll-runs',
        'action' => 'adjust-hours',
        'description' => 'Adjust weekly employee hour totals for payroll reporting without changing timecards',
    ];

    // ─── Pay Stubs ────────────────────────────────────────────────────────────

    public const STUBS_VIEW_OWN = [
        'resource' => 'payroll-stubs',
        'action' => 'view-own',
        'description' => "View the authenticated user's own pay stubs",
    ];

    public const STUBS_VIEW_ALL = [
        'resource' => 'payroll-stubs',
        'action' => 'view-all',
        'description' => 'View pay stubs for all employees',
    ];

    // ─── Deductions ───────────────────────────────────────────────────────────

    public const DEDUCTIONS_CONFIGURE = [
        'resource' => 'payroll-deductions',
        'action' => 'configure',
        'description' => 'Create and configure global deduction definitions',
    ];

    public const DEDUCTIONS_ASSIGN = [
        'resource' => 'payroll-deductions',
        'action' => 'assign',
        'description' => 'Assign deductions to individual employees',
    ];

    // ─── Pay Rates ────────────────────────────────────────────────────────────

    public const RATES_VIEW = [
        'resource' => 'payroll-rates',
        'action' => 'view',
        'description' => 'View employee pay rates',
    ];

    public const RATES_MANAGE = [
        'resource' => 'payroll-rates',
        'action' => 'manage',
        'description' => 'Create, update, and expire employee pay rates',
    ];

    // ─── Registry ──────────────────────────────────────────────────────────────

    public static function all(): array
    {
        return [
            // Reports
            self::REPORTS_VIEW,
            self::REPORTS_EXPORT,
            self::REPORTS_GENERATE,
            self::REPORTS_CERTIFY,
            self::REPORTS_REMIT,
            self::REPORTS_MANAGE,
            // Employees
            self::EMPLOYEES_VIEW,
            self::EMPLOYEES_CREATE,
            self::EMPLOYEES_UPDATE,
            self::EMPLOYEES_DEACTIVATE,
            // Timecards
            self::TIMECARDS_VIEW,
            self::TIMECARDS_SUBMIT,
            self::TIMECARDS_APPROVE,
            self::TIMECARDS_BULK_ENTER,
            // Pay Runs
            self::RUNS_PREVIEW,
            self::RUNS_APPROVE,
            self::RUNS_FINALIZE,
            self::RUNS_VOID,
            self::RUNS_ADJUST_HOURS,
            // Pay Stubs
            self::STUBS_VIEW_OWN,
            self::STUBS_VIEW_ALL,
            // Deductions
            self::DEDUCTIONS_CONFIGURE,
            self::DEDUCTIONS_ASSIGN,
            // Rates
            self::RATES_VIEW,
            self::RATES_MANAGE,
        ];
    }
}
