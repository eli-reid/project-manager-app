<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Models\PayrollStatement;

class PayStubPdfService
{
    /**
     * Generate a single-page PDF document for a payroll statement.
     */
    public function generate(PayrollStatement $statement): string
    {
        $employeeName = trim((string) ($statement->user?->first_name ?? '').' '.(string) ($statement->user?->last_name ?? ''));
        $employeeLabel = $employeeName !== '' ? $employeeName : 'Employee';

        $lines = [
            'Pay Stub',
            sprintf('Employee: %s', $employeeLabel),
            sprintf('Employee #: %s', (string) ($statement->payrollEmployeeProfile?->employee_number ?? 'N/A')),
            sprintf('Pay Date: %s', (string) optional($statement->payRun?->pay_date)->format('Y-m-d')),
            sprintf('Period: %s to %s', (string) optional($statement->payRun?->pay_period_start)->format('Y-m-d'), (string) optional($statement->payRun?->pay_period_end)->format('Y-m-d')),
            '',
            sprintf('Regular Hours: %.2f', (float) $statement->total_regular_hours),
            sprintf('Overtime Hours: %.2f', (float) $statement->total_ot_hours),
            sprintf('Double Time Hours: %.2f', (float) $statement->total_dt_hours),
            sprintf('Gross Pay: $%.2f', (float) $statement->gross_pay),
            sprintf('Federal Tax: $%.2f', (float) $statement->federal_tax),
            sprintf('State Tax: $%.2f', (float) $statement->state_tax),
            sprintf('Local Tax: $%.2f', (float) $statement->local_tax),
            sprintf('Social Security: $%.2f', (float) $statement->social_security),
            sprintf('Medicare: $%.2f', (float) $statement->medicare),
            sprintf('Other Deductions: $%.2f', (float) $statement->other_deductions),
            sprintf('Net Pay: $%.2f', (float) $statement->net_pay),
            '',
            sprintf('YTD Gross: $%.2f', (float) $statement->ytd_gross),
            sprintf('YTD Federal Tax: $%.2f', (float) $statement->ytd_federal_tax),
            sprintf('YTD Net: $%.2f', (float) $statement->ytd_net),
        ];

        $content = $this->buildContentStream($lines);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($content), $content),
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        return $this->buildPdf($objects);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function buildContentStream(array $lines): string
    {
        $commands = ['BT', '/F1 12 Tf'];
        $y = 760;

        foreach ($lines as $line) {
            $escaped = $this->escapeText($line);
            $commands[] = sprintf('1 0 0 1 72 %d Tm (%s) Tj', $y, $escaped);
            $y -= 22;
        }

        $commands[] = 'ET';

        return implode("\n", $commands);
    }

    /**
     * @param  array<int, string>  $objects
     */
    private function buildPdf(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $objectBody) {
            $offsets[] = strlen($pdf);
            $objectNumber = $index + 1;
            $pdf .= "{$objectNumber} 0 obj\n{$objectBody}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= sprintf("xref\n0 %d\n", count($objects) + 1);
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n";
        $pdf .= sprintf("<< /Size %d /Root 1 0 R >>\n", count($objects) + 1);
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function escapeText(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
