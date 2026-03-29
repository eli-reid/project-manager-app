<x-mail::message>
# Timecard Rejected

Your timecard for {{ $timecard->week_starting?->toFormattedDateString() }} to {{ $timecard->week_ending?->toFormattedDateString() }} needs updates.

@if (filled($timecard->rejection_reason))
Reason: {{ $timecard->rejection_reason }}
@endif

<x-mail::button :url="$showUrl">
Update Timecard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>