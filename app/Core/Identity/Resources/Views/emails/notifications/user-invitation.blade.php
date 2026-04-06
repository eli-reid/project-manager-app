<x-mail::message>
# Welcome to {{ config('app.name') }}

@if (filled($temporaryPassword ?? null))
Your account has been created. Sign in with your email address or username and the temporary password below.

<x-mail::panel>
@if (filled($user->email))
Email: {{ $user->email }}
@endif

@if (filled($user->username))
Username: {{ $user->username }}
@endif

Temporary Password: {{ $temporaryPassword }}
</x-mail::panel>

Use this password once, then set a new one when prompted.
@else
Your account is ready. Sign in using your work email address.
@endif

<x-mail::button :url="$loginUrl">
Sign In
</x-mail::button>

@if (blank($temporaryPassword ?? null))
If you do not know your password yet, use the "Forgot password" link on the sign-in page.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
