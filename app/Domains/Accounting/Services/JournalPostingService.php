<?php

namespace App\Domains\Accounting\Services;

use App\Domains\Accounting\Models\AccountingCode;
use App\Domains\Accounting\Models\AccountingJournalEntry;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JournalPostingService
{
    /**
     * @param  array<int, array{accounting_code_id:string,debit_amount?:float|int|string|null,credit_amount?:float|int|string|null,description?:string|null}>  $lines
     */
    public function post(
        string $description,
        array $lines,
        ?Carbon $postedAt = null,
        ?string $sourceType = null,
        ?string $sourceId = null,
    ): AccountingJournalEntry {
        if (trim($description) === '') {
            throw new DomainException('Journal entry description is required.');
        }

        if (count($lines) < 2) {
            throw new DomainException('Journal entries require at least two lines.');
        }

        $this->validateSourcePair($sourceType, $sourceId);

        if ($sourceType !== null && $sourceId !== null && $this->entryExistsForSource($sourceType, $sourceId)) {
            throw new DomainException('A journal entry has already been posted for this source record.');
        }

        $normalizedLines = $this->normalizeLines($lines);
        $debitTotal = round(collect($normalizedLines)->sum('debit_amount'), 2);
        $creditTotal = round(collect($normalizedLines)->sum('credit_amount'), 2);

        if ($debitTotal <= 0 || $creditTotal <= 0) {
            throw new DomainException('Journal entries must contain both debit and credit amounts.');
        }

        if ($debitTotal !== $creditTotal) {
            throw new DomainException('Journal entries must be balanced before posting.');
        }

        return DB::transaction(function () use ($description, $normalizedLines, $postedAt, $sourceId, $sourceType): AccountingJournalEntry {
            $entry = AccountingJournalEntry::query()->create([
                'entry_number' => $this->nextEntryNumber(),
                'description' => trim($description),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reversal_of_id' => null,
                'posted_at' => $postedAt ?? now(),
            ]);

            foreach ($normalizedLines as $index => $line) {
                $entry->lines()->create([
                    'accounting_code_id' => $line['accounting_code_id'],
                    'line_number' => $index + 1,
                    'description' => $line['description'],
                    'debit_amount' => $line['debit_amount'],
                    'credit_amount' => $line['credit_amount'],
                ]);
            }

            return $entry->fresh('lines.accountingCode') ?? $entry;
        });
    }

    public function reverse(
        AccountingJournalEntry $journalEntry,
        ?string $description = null,
        ?Carbon $postedAt = null,
    ): AccountingJournalEntry {
        $entry = $journalEntry->fresh('lines') ?? $journalEntry->loadMissing('lines');

        if ($entry->reversalEntry()->exists()) {
            throw new DomainException('This journal entry has already been reversed.');
        }

        if ($entry->lines->isEmpty()) {
            throw new DomainException('Journal entries without lines cannot be reversed.');
        }

        return DB::transaction(function () use ($description, $entry, $postedAt): AccountingJournalEntry {
            $reversalEntry = AccountingJournalEntry::query()->create([
                'entry_number' => $this->nextEntryNumber(),
                'description' => trim($description ?? 'Reversal of '.$entry->entry_number),
                'source_type' => null,
                'source_id' => null,
                'reversal_of_id' => $entry->id,
                'posted_at' => $postedAt ?? now(),
            ]);

            foreach ($entry->lines as $index => $line) {
                $reversalEntry->lines()->create([
                    'accounting_code_id' => $line->accounting_code_id,
                    'line_number' => $index + 1,
                    'description' => $line->description,
                    'debit_amount' => $line->credit_amount,
                    'credit_amount' => $line->debit_amount,
                ]);
            }

            return $reversalEntry->fresh('lines.accountingCode', 'reversalOf') ?? $reversalEntry;
        });
    }

    /**
     * @param  array<int, array{accounting_code_id:string,debit_amount?:float|int|string|null,credit_amount?:float|int|string|null,description?:string|null}>  $lines
     * @return array<int, array{accounting_code_id:string,debit_amount:float,credit_amount:float,description:?string}>
     */
    private function normalizeLines(array $lines): array
    {
        return collect($lines)
            ->values()
            ->map(function (array $line, int $index): array {
                $accountingCodeId = (string) Arr::get($line, 'accounting_code_id', '');
                $debitAmount = round((float) Arr::get($line, 'debit_amount', 0), 2);
                $creditAmount = round((float) Arr::get($line, 'credit_amount', 0), 2);

                if ($accountingCodeId === '' || ! AccountingCode::query()->whereKey($accountingCodeId)->exists()) {
                    throw new DomainException('Each journal line must reference a valid accounting code.');
                }

                if ($debitAmount < 0 || $creditAmount < 0) {
                    throw new DomainException('Journal line amounts cannot be negative.');
                }

                if (($debitAmount > 0 && $creditAmount > 0) || ($debitAmount === 0.0 && $creditAmount === 0.0)) {
                    throw new DomainException('Each journal line must contain exactly one non-zero side.');
                }

                return [
                    'accounting_code_id' => $accountingCodeId,
                    'debit_amount' => $debitAmount,
                    'credit_amount' => $creditAmount,
                    'description' => ($description = Arr::get($line, 'description')) !== null ? trim((string) $description) : null,
                ];
            })
            ->all();
    }

    private function nextEntryNumber(): string
    {
        $sequence = AccountingJournalEntry::query()->count() + 1;

        return 'JE-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    private function entryExistsForSource(string $sourceType, string $sourceId): bool
    {
        return AccountingJournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }

    private function validateSourcePair(?string $sourceType, ?string $sourceId): void
    {
        if (($sourceType === null) !== ($sourceId === null)) {
            throw new DomainException('Journal entry sources require both source_type and source_id together.');
        }
    }
}
