<?php

namespace App\Domains\Invoices\Enums;

enum InvoiceStatusEnum: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Verified = 'verified';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Verified => 'Verified',
            self::Paid => 'Paid',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
            self::Pending => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            self::Verified => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            self::Paid => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::Rejected => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return array_combine(
            array_map(fn (self $case) => $case->value, self::cases()),
            array_map(fn (self $case) => $case->label(), self::cases()),
        );
    }
}
