<x-mail::message>
# Pay Run Finalized

A pay run has been finalized.

- Pay Run ID: {{ $payRunId }}
- Pay Period Start: {{ $payPeriodStart }}
- Pay Period End: {{ $payPeriodEnd }}
- Pay Date: {{ $payDate }}
- Employee Count: {{ $employeeCount }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
