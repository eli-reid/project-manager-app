import './mobile';
import './pwa';

/**
 * Force a full page load when navigating to auth pages.
 *
 * When wire:navigate caches the login page and later serves it after session
 * expiry, the @csrf token is stale → 419. Intercepting the navigate event and
 * redirecting via window.location ensures a fresh CSRF token is always rendered.
 */
function clearBrowserAuthState() {
    try {
        sessionStorage.removeItem('livewire-component-recovery');
    } catch {
        // Ignore storage failures in privacy-restricted contexts.
    }

    try {
        if ('caches' in window) {
            caches.keys().then((keys) => Promise.all(keys.map((key) => caches.delete(key))));
        }
    } catch {
        // Ignore cache cleanup failures in unsupported contexts.
    }
}

document.addEventListener('livewire:navigate', (event) => {
    const authPaths = ['/', '/login', '/register', '/forgot-password', '/reset-password', '/email/verify', '/confirm-password', '/two-factor-challenge'];

    try {
        const url = new URL(event.detail.url, window.location.origin);

        if (authPaths.some((path) => url.pathname === path || url.pathname.startsWith(path + '/'))) {
            event.preventDefault();
            window.location.href = event.detail.url;
        }
    } catch {
        // Malformed URL — let Livewire handle it normally.
    }
});

// When a Livewire request fails with 419 (session expired), clear stale auth state
// and force a hard reload so Laravel can render a fresh page with a valid CSRF token.
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();
                clearBrowserAuthState();
                window.location.reload();
            }
        });
    });
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    try {
        const url = new URL(form.action, window.location.origin);

        if (url.pathname === '/logout' || url.pathname.endsWith('/logout')) {
            clearBrowserAuthState();
        }
    } catch {
        // Ignore invalid form actions.
    }
});

// Recover from stale SPA state by forcing one full reload if Livewire cannot
// resolve a component during navigation.
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

// Mobile browsers can restore auth pages from back-forward cache with a stale
// CSRF token. Force a fresh reload when that happens.
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) {
        return;
    }

    const authPaths = ['/', '/login', '/register', '/forgot-password', '/reset-password', '/email/verify', '/confirm-password', '/two-factor-challenge'];
    const path = window.location.pathname;

    if (authPaths.some((authPath) => path === authPath || path.startsWith(authPath + '/'))) {
        window.location.reload();
    }
});
