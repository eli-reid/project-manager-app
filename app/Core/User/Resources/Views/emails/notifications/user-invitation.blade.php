<x-mail::message>
# Welcome to {{ config('app.name') }}

Your account is ready. Sign in using your work email address.

<x-mail::button :url="$loginUrl">
Sign In
</x-mail::button>

If you do not know your password yet, use the "Forgot password" link on the sign-in page.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>