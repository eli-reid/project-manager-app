/**
 * Force a full page load when navigating to auth pages.
 *
 * When wire:navigate caches the login page and later serves it after session
 * expiry, the @csrf token is stale → 419. Intercepting the navigate event and
 * redirecting via window.location ensures a fresh CSRF token is always rendered.
 */
document.addEventListener('livewire:navigate', (event) => {
    const authPaths = ['/login', '/register', '/forgot-password', '/reset-password', '/email/verify', '/confirm-password', '/two-factor-challenge'];

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
