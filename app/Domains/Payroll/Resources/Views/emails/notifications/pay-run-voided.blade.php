<x-mail::message>
# Pay Run Voided

A pay run has been voided and requires attention.

- Pay Run ID: {{ $payRunId }}
- Pay Period Start: {{ $payPeriodStart }}
- Pay Period End: {{ $payPeriodEnd }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
