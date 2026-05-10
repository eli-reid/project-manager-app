<x-mail::message>
# Pay Stub Available

Your pay stub is now available.

- Pay Period End: {{ $payPeriodEnd }}
- Gross Pay: {{ $grossPay }}
- Net Pay: {{ $netPay }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
