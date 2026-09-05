<?php

namespace App\Domains\Invoices\Data;

class InvoicePdfData
{
    /**
     * @param  array<int, array{description: string, quantity: string, unit_price: string, total: string}>  $lineItems
     * @param  array<string, float>  $confidence  Map of field name => confidence score (0.0 - 1.0).
     */
    public function __construct(
        public ?string $vendorName = null,
        public ?string $invoiceNumber = null,
        public ?string $invoiceDate = null,
        public ?string $dueDate = null,
        public ?string $subtotal = null,
        public ?string $taxAmount = null,
        public ?string $totalAmount = null,
        public array $lineItems = [],
        public array $confidence = [],
        public ?string $rawText = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vendor_name' => $this->vendorName,
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'due_date' => $this->dueDate,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'line_items' => $this->lineItems,
            'confidence' => $this->confidence,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            vendorName: $data['vendor_name'] ?? null,
            invoiceNumber: $data['invoice_number'] ?? null,
            invoiceDate: $data['invoice_date'] ?? null,
            dueDate: $data['due_date'] ?? null,
            subtotal: $data['subtotal'] ?? null,
            taxAmount: $data['tax_amount'] ?? null,
            totalAmount: $data['total_amount'] ?? null,
            lineItems: $data['line_items'] ?? [],
            confidence: $data['confidence'] ?? [],
        );
    }

    public function confidenceFor(string $field): float
    {
        return $this->confidence[$field] ?? 0.0;
    }

    public function isLowConfidence(string $field, float $threshold = 0.6): bool
    {
        return $this->confidenceFor($field) < $threshold;
    }
}
