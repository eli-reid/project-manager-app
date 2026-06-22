<x-mail::message>
# Pay Run Approved

A pay run has been approved.

- Pay Run ID: {{ $payRunId }}
- Approved By: {{ $approvedBy }}
- Pay Period Start: {{ $payPeriodStart }}
- Pay Period End: {{ $payPeriodEnd }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
