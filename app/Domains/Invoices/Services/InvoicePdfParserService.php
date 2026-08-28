<?php

namespace App\Domains\Invoices\Services;

use App\Domains\Invoices\Data\InvoicePdfData;
use Smalot\PdfParser\Parser;
use Throwable;

class InvoicePdfParserService
{
    public function parse(string $filePath): InvoicePdfData
    {
        $parser = new Parser;
        $document = $parser->parseFile($filePath);
        $text = $document->getText();

        $confidence = [];

        $vendorName = $this->extractVendorName($text, $confidence);
        $invoiceNumber = $this->extractInvoiceNumber($text, $confidence);
        $invoiceDate = $this->extractDate($text, ['invoice date', 'date issued', 'date'], 'invoice_date', $confidence);
        $dueDate = $this->extractDate($text, ['due date', 'payment due', 'due'], 'due_date', $confidence);
        $subtotal = $this->extractAmount($text, ['subtotal', 'sub-total', 'sub total'], 'subtotal', $confidence);
        $taxAmount = $this->extractAmount($text, ['tax', 'vat', 'sales tax'], 'tax_amount', $confidence);
        $totalAmount = $this->extractAmount($text, ['total due', 'amount due', 'grand total', 'total'], 'total_amount', $confidence);
        $lineItems = $this->extractLineItems($text, $confidence);

        return new InvoicePdfData(
            vendorName: $vendorName,
            invoiceNumber: $invoiceNumber,
            invoiceDate: $invoiceDate,
            dueDate: $dueDate,
            subtotal: $subtotal,
            taxAmount: $taxAmount,
            totalAmount: $totalAmount,
            lineItems: $lineItems,
            confidence: $confidence,
            rawText: $text,
        );
    }

    /**
     * @param  array<string, float>  $confidence
     */
    private function extractVendorName(string $text, array &$confidence): ?string
    {
        $lines = $this->nonEmptyLines($text);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^invoice\b/i', $line)) {
                continue;
            }

            $confidence['vendor_name'] = 0.5;

            return $line;
        }

        $confidence['vendor_name'] = 0.0;

        return null;
    }

    /**
     * @param  array<string, float>  $confidence
     */
    private function extractInvoiceNumber(string $text, array &$confidence): ?string
    {
        if (preg_match('/invoice\s*(?:#|no\.?|number)\s*[:\-]?\s*([A-Z0-9\-\/]+)/i', $text, $matches)) {
            $confidence['invoice_number'] = 0.9;

            return trim($matches[1]);
        }

        $confidence['invoice_number'] = 0.0;

        return null;
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<string, float>  $confidence
     */
    private function extractDate(string $text, array $labels, string $key, array &$confidence): ?string
    {
        $labelPattern = implode('|', array_map(fn (string $l) => preg_quote($l, '/'), $labels));

        if (preg_match('/(?:'.$labelPattern.')\s*[:\-]?\s*(\d{1,4}[\/\-.]\d{1,2}[\/\-.]\d{1,4}|[A-Za-z]+\s+\d{1,2},?\s+\d{4})/i', $text, $matches)) {
            $normalized = $this->normalizeDate($matches[1]);

            if ($normalized !== null) {
                $confidence[$key] = 0.85;

                return $normalized;
            }
        }

        $confidence[$key] = 0.0;

        return null;
    }

    private function normalizeDate(string $raw): ?string
    {
        $formats = ['Y-m-d', 'm/d/Y', 'm-d-Y', 'd/m/Y', 'M j, Y', 'F j, Y', 'M j Y', 'F j Y'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $raw);

            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        try {
            return (new \DateTime($raw))->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<string, float>  $confidence
     */
    private function extractAmount(string $text, array $labels, string $key, array &$confidence): ?string
    {
        $labelPattern = implode('|', array_map(fn (string $l) => preg_quote($l, '/'), $labels));

        if (preg_match('/(?<![a-z\-])(?:'.$labelPattern.')\s*[:\-]?\s*\$?\s*([\d,]+\.\d{2})/i', $text, $matches)) {
            $confidence[$key] = 0.8;

            return str_replace(',', '', $matches[1]);
        }

        $confidence[$key] = 0.0;

        return null;
    }

    /**
     * @param  array<string, float>  $confidence
     * @return array<int, array{description: string, quantity: string, unit_price: string, total: string}>
     */
    private function extractLineItems(string $text, array &$confidence): array
    {
        $lines = $this->nonEmptyLines($text);
        $items = [];

        foreach ($lines as $line) {
            // Match rows like: Description  qty  unit_price  total
            if (preg_match('/^(.+?)\s+(\d+(?:\.\d+)?)\s+\$?([\d,]+\.\d{2})\s+\$?([\d,]+\.\d{2})$/', trim($line), $matches)) {
                $items[] = [
                    'description' => trim($matches[1]),
                    'quantity' => $matches[2],
                    'unit_price' => str_replace(',', '', $matches[3]),
                    'total' => str_replace(',', '', $matches[4]),
                ];
            }
        }

        $confidence['line_items'] = $items === [] ? 0.0 : 0.7;

        return $items;
    }

    /**
     * @return array<int, string>
     */
    private function nonEmptyLines(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            fn (string $line): bool => $line !== ''
        ));
    }
}
