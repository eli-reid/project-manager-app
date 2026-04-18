<x-mail::message>
# Timecard Reminder

You have {{ $timecards->count() }} pending timecard(s) for week ending {{ \Carbon\Carbon::parse($weekEnding)->toFormattedDateString() }}.

@foreach ($timecards as $timecard)
- {{ $timecard->week_starting?->toFormattedDateString() }} to {{ $timecard->week_ending?->toFormattedDateString() }} ({{ ucfirst((string) $timecard->status) }})
@endforeach

Please review and submit as soon as possible.

<x-mail::button :url="$showUrl">
Open Timecards
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>