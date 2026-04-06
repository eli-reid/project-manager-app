<x-mail::message>
# Project Access Granted

You have been granted access to the project **{{ $project->name }}**.

<x-mail::button :url="$showUrl">
View Project
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
