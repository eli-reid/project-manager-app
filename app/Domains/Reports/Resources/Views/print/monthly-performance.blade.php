<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Monthly Financial Performance – {{ $year }}</title>
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, sans-serif; font-size: 13px; color: #111; background: #fff; padding: 2cm; }
    h1 { font-size: 20px; margin-bottom: 4px; }
    p.subtitle { color: #555; margin-bottom: 24px; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th { background: #f4f4f5; text-align: left; padding: 8px 12px; font-weight: 600; border-bottom: 2px solid #d4d4d8; }
    th:not(:first-child) { text-align: right; }
    td { padding: 7px 12px; border-bottom: 1px solid #e4e4e7; }
    td:not(:first-child) { text-align: right; }
    tfoot td { font-weight: 700; border-top: 2px solid #d4d4d8; border-bottom: none; background: #f4f4f5; }
    .positive { color: #16a34a; }
    .negative { color: #dc2626; }
    .footer { margin-top: 24px; font-size: 11px; color: #888; text-align: right; }
    @media print {
        body { padding: 1cm; }
        .no-print { display: none; }
    }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:16px;">
    <button onclick="window.print()" style="padding:8px 16px;background:#3b82f6;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;">
        🖨 Print / Save as PDF
    </button>
    <button onclick="window.history.back()" style="padding:8px 16px;background:#e4e4e7;color:#111;border:none;border-radius:6px;cursor:pointer;font-size:13px;margin-left:8px;">
        ← Back
    </button>
</div>

<h1>Monthly Financial Performance</h1>
<p class="subtitle">Year: {{ $year }} &nbsp;•&nbsp; Generated: {{ now()->format('M d, Y H:i') }}</p>

<table>
    <thead>
        <tr>
            <th>Month</th>
            <th>Timecard Hours</th>
            <th>Invoice Revenue</th>
            <th>Stock Cost</th>
            <th>Gross Margin</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($months as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ number_format($row['hours'], 2) }}</td>
                <td>${{ number_format($row['revenue'], 2) }}</td>
                <td>${{ number_format($row['stock_cost'], 2) }}</td>
                <td class="{{ $row['margin'] >= 0 ? 'positive' : 'negative' }}">
                    ${{ number_format($row['margin'], 2) }}
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>Total</td>
            <td>{{ number_format(collect($months)->sum('hours'), 2) }}</td>
            <td>${{ number_format(collect($months)->sum('revenue'), 2) }}</td>
            <td>${{ number_format(collect($months)->sum('stock_cost'), 2) }}</td>
            <td class="{{ collect($months)->sum('margin') >= 0 ? 'positive' : 'negative' }}">
                ${{ number_format(collect($months)->sum('margin'), 2) }}
            </td>
        </tr>
    </tfoot>
</table>

<p class="footer">{{ config('app.name') }} &nbsp;•&nbsp; Confidential</p>
</body>
</html>
