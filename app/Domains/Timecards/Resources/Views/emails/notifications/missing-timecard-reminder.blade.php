<x-mail::message>
# Missing Timecard - Action Required

You have not submitted a timecard for the week of {{ $weekStarting->toFormattedDateString() }} to {{ $weekEnding->toFormattedDateString() }}.

Please submit your timecard as soon as possible to ensure accurate payroll processing.

<x-mail::button :url="$createUrl">
Submit Timecard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
