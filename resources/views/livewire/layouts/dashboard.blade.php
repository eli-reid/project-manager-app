<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    <body class="min-h-screen bg-[linear-gradient(180deg,_#f8fafc_0%,_#e2e8f0_100%)] text-zinc-950 dark:bg-[radial-gradient(circle_at_top_left,_rgba(23,37,84,0.28),_transparent_28%),linear-gradient(180deg,_#09090b_0%,_#111827_100%)] dark:text-zinc-100">
        {{ $slot }}

        @fluxScripts
    </body>
</html>