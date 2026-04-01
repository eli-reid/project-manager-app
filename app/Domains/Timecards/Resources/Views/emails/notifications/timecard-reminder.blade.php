<x-mail::message>
# Timecard Reminder

You have a pending timecard for {{ $timecard->week_starting?->toFormattedDateString() }} to {{ $timecard->week_ending?->toFormattedDateString() }}.

Please review and submit it as soon as possible.

<x-mail::button :url="$showUrl">
Open Timecard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
