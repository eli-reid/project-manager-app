<?php

namespace App\Domains\Invoices\Console\Commands;

use App\Domains\Invoices\Models\InvoicePdfImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneInvoicePdfImportsCommand extends Command
{
    protected $signature = 'invoices:prune-pdf-imports {--days=7 : Retention period in days}';

    protected $description = 'Delete staged PDFs and rows for failed or abandoned invoice imports past the retention period.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        // Imported rows are excluded: their PDF now lives in the Documents
        // domain and the row is the audit trail for that import.
        $stale = InvoicePdfImport::query()
            ->whereIn('status', [
                InvoicePdfImport::STATUS_PENDING,
                InvoicePdfImport::STATUS_PARSED,
                InvoicePdfImport::STATUS_FAILED,
            ])
            ->where('created_at', '<', $cutoff)
            ->get();

        $deleted = 0;

        foreach ($stale as $import) {
            if (filled($import->file_path) && Storage::disk('local')->exists($import->file_path)) {
                Storage::disk('local')->delete($import->file_path);
            }

            $import->delete();
            $deleted++;
        }

        $this->info($deleted.' stale invoice PDF import(s) pruned.');

        return self::SUCCESS;
    }
}
