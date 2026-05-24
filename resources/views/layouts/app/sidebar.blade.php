<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <livewire:users::layout.app-sidebar />

        @isset($domainNavbar)
            <div class="sticky top-0 z-30 w-full border-b border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
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

            document.addEventListener('click', async (event) => {
                const trigger = event.target.closest('[data-webmail-launcher]');
                if (!trigger) {
                    return;
                }

                event.preventDefault();

                if (trigger.dataset.launching === '1') {
                    return;
                }

                const endpoint = trigger.dataset.webmailSessionEndpoint;
                const fallbackUrl = trigger.dataset.webmailFallbackUrl;

                if (!endpoint) {
                    if (fallbackUrl) {
                        window.open(fallbackUrl, '_blank', 'noopener,noreferrer');
                    }

                    return;
                }

                trigger.dataset.launching = '1';

                try {
                    const response = await fetch(endpoint, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload?.success) {
                        throw new Error(payload?.message || 'Unable to launch webmail.');
                    }

                    if (payload.mode === 'post_handshake' && payload.login_url && payload.session) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = payload.login_url;
                        form.target = '_blank';

                        const sessionInput = document.createElement('input');
                        sessionInput.type = 'hidden';
                        sessionInput.name = 'session';
                        sessionInput.value = payload.session;

                        form.appendChild(sessionInput);
                        document.body.appendChild(form);
                        form.submit();
                        form.remove();

                        return;
                    }

                    if (payload.url) {
                        window.open(payload.url, '_blank', 'noopener,noreferrer');

                        return;
                    }

                    throw new Error('Unable to launch webmail.');
                } catch (_error) {
                    if (fallbackUrl) {
                        window.open(fallbackUrl, '_blank', 'noopener,noreferrer');
                    }
                } finally {
                    delete trigger.dataset.launching;
                }
            });
        </script>
    </body>
</html>
