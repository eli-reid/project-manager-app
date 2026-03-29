<x-mail::message>
# Timecard Approved

Your timecard for {{ $timecard->week_starting?->toFormattedDateString() }} to {{ $timecard->week_ending?->toFormattedDateString() }} was approved.

<x-mail::button :url="$showUrl">
View Timecard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>