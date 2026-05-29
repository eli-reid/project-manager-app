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

it('rejects duplicate postings for the same source record', function (): void {
    $cash = AccountingCode::factory()->create([
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);

    $revenue = AccountingCode::factory()->create([
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);

    /** @var JournalPostingService $service */
    $service = app(JournalPostingService::class);

    $service->post('Original source post', [
        [
            'accounting_code_id' => $cash->id,
            'debit_amount' => 100.00,
        ],
        [
            'accounting_code_id' => $revenue->id,
            'credit_amount' => 100.00,
        ],
    ], sourceType: 'invoice', sourceId: 'invoice-123');

    expect(fn () => $service->post('Duplicate source post', [
        [
            'accounting_code_id' => $cash->id,
            'debit_amount' => 100.00,
        ],
        [
            'accounting_code_id' => $revenue->id,
            'credit_amount' => 100.00,
        ],
    ], sourceType: 'invoice', sourceId: 'invoice-123'))
        ->toThrow(\DomainException::class, 'A journal entry has already been posted for this source record.');
});

it('creates a reversing journal entry with swapped debit and credit lines', function (): void {
    $cash = AccountingCode::factory()->create([
        'code' => '1000',
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);

    $revenue = AccountingCode::factory()->create([
        'code' => '4000',
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);

    /** @var JournalPostingService $service */
    $service = app(JournalPostingService::class);

    $entry = $service->post('Record revenue', [
        [
            'accounting_code_id' => $cash->id,
            'debit_amount' => 75.00,
            'description' => 'Cash in',
        ],
        [
            'accounting_code_id' => $revenue->id,
            'credit_amount' => 75.00,
            'description' => 'Revenue out',
        ],
    ]);

    $reversal = $service->reverse($entry);

    expect($reversal->reversal_of_id)->toBe($entry->id);
    expect($reversal->entry_number)->toBe('JE-000002');
    expect($reversal->lines)->toHaveCount(2);
    expect($reversal->lines[0]->debit_amount)->toBe('0.00');
    expect($reversal->lines[0]->credit_amount)->toBe('75.00');
    expect($reversal->lines[1]->debit_amount)->toBe('75.00');
    expect($reversal->lines[1]->credit_amount)->toBe('0.00');
});

it('rejects reversing an entry twice', function (): void {
    $cash = AccountingCode::factory()->create([
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);

    $revenue = AccountingCode::factory()->create([
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);

    /** @var JournalPostingService $service */
    $service = app(JournalPostingService::class);

    $entry = $service->post('Record revenue', [
        [
            'accounting_code_id' => $cash->id,
            'debit_amount' => 40.00,
        ],
        [
            'accounting_code_id' => $revenue->id,
            'credit_amount' => 40.00,
        ],
    ]);

    $service->reverse($entry);

    expect(fn () => $service->reverse($entry))
        ->toThrow(\DomainException::class, 'This journal entry has already been reversed.');
});
