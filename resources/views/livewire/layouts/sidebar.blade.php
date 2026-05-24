<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <livewire:layout.app-sidebar />

        @isset($domainNavbar)
            <div class="sticky top-0 z-30 border-b border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900 lg:ms-64">
                {{ $domainNavbar }}
            </div>
        @endisset

        {{ $slot }}

        @fluxScripts

        {{-- When a Livewire component request gets a 419 (session expired),
             force a full page reload so the server can redirect to the login
             page and render it fresh — avoiding a stale cached CSRF token. --}}
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status === 419) {
                            preventDefault();
                            window.location.reload();
                        }
                    });
                });
            });

            // Recover from stale SPA navigation state by forcing a full reload.
            window.addEventListener('unhandledrejection', (event) => {
                const reason = event.reason;
                const message = typeof reason === 'string' ? reason : reason?.message;
                const isAdminQueuePage = window.location.pathname === '/admin/queue'
                    || window.location.pathname.startsWith('/admin/queue/');

                if (
                    (typeof message !== 'string' || !message.includes('Component not found:'))
                    && !(reason == null && isAdminQueuePage)
                ) {
                    return;
                }

                event.preventDefault();

                if (sessionStorage.getItem('livewire-component-recovery') === '1') {
                    return;
                }

                sessionStorage.setItem('livewire-component-recovery', '1');
                window.location.reload();
            });

            window.addEventListener('pageshow', () => {
                sessionStorage.removeItem('livewire-component-recovery');
            });

        </script>
    </body>
</html>
