<x-mail::message>
# Timecard Submitted for Review

{{ $employeeName }} submitted a timecard for {{ $timecard->week_starting?->toFormattedDateString() }} to {{ $timecard->week_ending?->toFormattedDateString() }}.

<x-mail::button :url="$reviewUrl">
Review Timecard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>