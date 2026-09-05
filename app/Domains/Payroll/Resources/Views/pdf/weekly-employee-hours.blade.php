{{-- payroll::pdf.weekly-employee-hours --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Weekly Employee Hours Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Weekly Employee Hours</h1>
    <p>Week of {{ \Carbon\CarbonImmutable::parse($weekStart)->format('M j, Y') }} to {{ \Carbon\CarbonImmutable::parse($weekEnd)->format('M j, Y') }}</p>
    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Source Hours</th>
                <th>Reported Hours</th>
                <th>Adjusted</th>
                <th>Adjustment Reason</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employeeHours as $item)
                <tr>
                    <td>{{ $item['first_name'] }} {{ $item['last_name'] }}</td>
                    <td>{{ number_format($item['source_hours'], 2) }}</td>
                    <td>{{ number_format($item['hours'], 2) }}</td>
                    <td>{{ $item['is_adjusted'] ? 'Yes' : 'No' }}</td>
                    <td>{{ $item['adjustment_reason'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Total Hours:</strong> {{ number_format($employeeHours->sum('hours'), 2) }}</p>
    <p>Generated on {{ now()->format('M j, Y \a\t g:i A') }}</p>
</body>
</html>
