<?php

namespace App\Domains\Payroll\Notifications;

class PayrollNotificationDefinitions
{
    // Pay Run Notifications (PR-*)
    public const PREVIEW_CREATED = 'payroll.pay-run.preview-created';

    public const EXCEPTIONS_FOUND = 'payroll.pay-run.exceptions-found';

    public const APPROVED = 'payroll.pay-run.approved';

    public const FINALIZED = 'payroll.pay-run.finalized';

    public const VOIDED = 'payroll.pay-run.voided';

    public const DIRECT_DEPOSIT_SCHEDULED = 'payroll.pay-run.direct-deposit-scheduled';

    // Employee Notifications (EM-*)
    public const RATE_CHANGE_EFFECTIVE = 'payroll.employee.rate-change-effective';

    public const DEDUCTION_MODIFIED = 'payroll.employee.deduction-modified';

    public const PAY_STUB_AVAILABLE = 'payroll.employee.pay-stub-available';

    // System Notifications (SY-*)
    public const HASH_CHAIN_INTEGRITY_FAILURE = 'payroll.system.hash-chain-integrity-failure';

    public const TAX_TABLE_UPDATE_AVAILABLE = 'payroll.system.tax-table-update-available';

    // Compliance Notifications (CO-*)
    public const CERTIFIED_PAYROLL_DUE = 'payroll.compliance.certified-payroll-due';

    public const CERTIFIED_PAYROLL_GENERATED = 'payroll.compliance.certified-payroll-generated';

    public const QUARTERLY_TAX_FILING_DUE = 'payroll.compliance.quarterly-tax-filing-due';

    /**
     * @return array<int, array{key:string,label:string,description:string,supported_channels:array<int, string>}>
     */
    public static function definitions(): array
    {
        return [
            // Pay Run Notifications
            [
                'key' => self::PREVIEW_CREATED,
                'label' => 'Pay Run Preview Created',
                'description' => 'Sent when a pay run preview is created and ready for review.',
                'supported_channels' => ['mail', 'database'],
            ],
            [
                'key' => self::EXCEPTIONS_FOUND,
                'label' => 'Pay Run Exceptions Found',
                'description' => 'Sent when payroll processing exceptions or warnings are detected.',
                'supported_channels' => ['mail', 'database', 'sms', 'push'],
            ],
            [
                'key' => self::APPROVED,
                'label' => 'Pay Run Approved',
                'description' => 'Sent when a pay run is approved by a controller.',
                'supported_channels' => ['mail', 'database'],
            ],
            [
                'key' => self::FINALIZED,
                'label' => 'Pay Run Finalized',
                'description' => 'Sent when a pay run is finalized and scheduled for disbursement.',
                'supported_channels' => ['mail', 'database'],
            ],
            [
                'key' => self::VOIDED,
                'label' => 'Pay Run Voided',
                'description' => 'Sent when a finalized pay run is voided.',
                'supported_channels' => ['mail', 'database', 'sms'],
            ],
            [
                'key' => self::DIRECT_DEPOSIT_SCHEDULED,
                'label' => 'Direct Deposit Scheduled',
                'description' => 'Sent to employees when direct deposit is scheduled for an upcoming pay date.',
                'supported_channels' => ['mail', 'database', 'push'],
            ],
            // Employee Notifications
            [
                'key' => self::RATE_CHANGE_EFFECTIVE,
                'label' => 'Pay Rate Change Effective',
                'description' => 'Sent when an employee\'s pay rate is changed and becomes effective.',
                'supported_channels' => ['mail', 'database'],
            ],
            [
                'key' => self::DEDUCTION_MODIFIED,
                'label' => 'Deduction Modified',
                'description' => 'Sent when an employee deduction is added or modified.',
                'supported_channels' => ['mail', 'database'],
            ],
            [
                'key' => self::PAY_STUB_AVAILABLE,
                'label' => 'Pay Stub Available',
                'description' => 'Sent when a pay stub is ready for employee viewing.',
                'supported_channels' => ['mail', 'database', 'push'],
            ],
            // System Notifications
            [
                'key' => self::HASH_CHAIN_INTEGRITY_FAILURE,
                'label' => 'Audit Chain Integrity Failure',
                'description' => 'Sent when payroll audit digest chain validation detects integrity issues.',
                'supported_channels' => ['mail', 'sms'],
            ],
            [
                'key' => self::TAX_TABLE_UPDATE_AVAILABLE,
                'label' => 'Tax Table Update Available',
                'description' => 'Sent when new IRS/state tax withholding tables are available.',
                'supported_channels' => ['mail', 'database'],
            ],
            // Compliance Notifications
            [
                'key' => self::CERTIFIED_PAYROLL_DUE,
                'label' => 'Certified Payroll Report Due',
                'description' => 'Sent as a reminder that certified payroll reports are due for filing.',
                'supported_channels' => ['mail', 'database', 'push'],
            ],
            [
                'key' => self::CERTIFIED_PAYROLL_GENERATED,
                'label' => 'Certified Payroll Generated',
                'description' => 'Sent when certified payroll reports have been generated.',
                'supported_channels' => ['mail', 'database'],
            ],
            [
                'key' => self::QUARTERLY_TAX_FILING_DUE,
                'label' => 'Quarterly Tax Filing Due',
                'description' => 'Sent as a reminder that quarterly tax filings are due.',
                'supported_channels' => ['mail', 'database', 'push'],
            ],
        ];
    }
}
