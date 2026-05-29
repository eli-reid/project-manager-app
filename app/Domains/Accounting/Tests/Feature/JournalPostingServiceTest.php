<?php

use App\Domains\Accounting\Models\AccountingCode;
use App\Domains\Accounting\Models\AccountingJournalEntry;
use App\Domains\Accounting\Models\AccountingJournalLine;
use App\Domains\Accounting\Services\JournalPostingService;

it('posts a balanced journal entry with matching lines', function (): void {
    $cash = AccountingCode::factory()->create([
        'code' => '1000',
        'name' => 'Cash',
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);

    $revenue = AccountingCode::factory()->create([
        'code' => '4000',
        'name' => 'Service Revenue',
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);

    $entry = app(JournalPostingService::class)->post('Record service revenue', [
        [
            'accounting_code_id' => $cash->id,
            'debit_amount' => 250.00,
            'description' => 'Increase cash',
        ],
        [
            'accounting_code_id' => $revenue->id,
            'credit_amount' => 250.00,
            'description' => 'Recognize revenue',
        ],
    ]);

    expect($entry)->toBeInstanceOf(AccountingJournalEntry::class);
    expect($entry->entry_number)->toBe('JE-000001');
    expect($entry->lines)->toHaveCount(2);
    expect($entry->totalDebits())->toBe(250.0);
    expect($entry->totalCredits())->toBe(250.0);

    expect(AccountingJournalLine::query()->count())->toBe(2);
});

it('rejects unbalanced journal entries', function (): void {
    $cash = AccountingCode::factory()->create([
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);

    $revenue = AccountingCode::factory()->create([
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);

    expect(fn () => app(JournalPostingService::class)->post('Bad entry', [
        [
            'accounting_code_id' => $cash->id,
            'debit_amount' => 250.00,
        ],
        [
            'accounting_code_id' => $revenue->id,
            'credit_amount' => 225.00,
        ],
    ]))->toThrow(DomainException::class, 'Journal entries must be balanced before posting.');

    expect(AccountingJournalEntry::query()->count())->toBe(0);
    expect(AccountingJournalLine::query()->count())->toBe(0);
});

it('rejects journal lines without a single non-zero side', function (): void {
    $cash = AccountingCode::factory()->create([
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);

    $revenue = AccountingCode::factory()->create([
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);

    expect(fn () => app(JournalPostingService::class)->post('Invalid line shape', [
        [
            'accounting_code_id' => $cash->id,
            'debit_amount' => 50.00,
            'credit_amount' => 50.00,
        ],
        [
            'accounting_code_id' => $revenue->id,
            'credit_amount' => 50.00,
        ],
    ]))->toThrow(DomainException::class, 'Each journal line must contain exactly one non-zero side.');
});
