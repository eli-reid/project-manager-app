<?php

namespace App\Domains\Invoices\Jobs;

use App\Domains\Invoices\Models\InvoicePdfImport;
use App\Domains\Invoices\Services\InvoicePdfParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessInvoicePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $invoicePdfImportId,
        public string $filePath,
        public string $projectId,
        public string $userId,
    ) {}

    public function handle(InvoicePdfParserService $parser): void
    {
        $import = InvoicePdfImport::query()->find($this->invoicePdfImportId);

        if ($import === null) {
            return;
        }

        try {
            $data = $parser->parse($this->filePath);

            $import->update([
                'status' => InvoicePdfImport::STATUS_PARSED,
                'parsed_data' => $data->toArray(),
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            $import->update([
                'status' => InvoicePdfImport::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
