import './mobile';
import './pwa';

// Domain-owned client-side behaviour lives inside its domain folder, not here —
// this is just the single Vite entry point required to bundle it.
import '../../app/Domains/Plans/Resources/js/sheet-viewer.js';

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

    preventDoubleSubmit(form, event);
});

/**
 * Block a second submit of the same plain POST form. The first request rotates
 * the session's CSRF token, so a repeat submit (double-tap, impatient retry)
 * would otherwise fail with 419 Page Expired.
 */
function isLivewireForm(form) {
    return Array.from(form.attributes).some((attribute) => attribute.name.startsWith('wire:submit'));
}

function preventDoubleSubmit(form, event) {
    if (
        event.defaultPrevented
        || form.method.toLowerCase() !== 'post'
        || isLivewireForm(form)
        || ('allowResubmit' in form.dataset)
        || (form.target && form.target !== '_self')
    ) {
        return;
    }

    if (form.dataset.submitting === '1') {
        event.preventDefault();

        return;
    }

    form.dataset.submitting = '1';
    form.setAttribute('aria-busy', 'true');

    // Defer disabling so the submitter's name/value is still included in this submission.
    setTimeout(() => {
        form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.dataset.submitDisabled = '1';
        });
    }, 0);

    // Safety net for responses that never navigate away (e.g. file downloads).
    setTimeout(() => resetSubmittingForm(form), 15000);
}

function resetSubmittingForm(form) {
    delete form.dataset.submitting;
    form.removeAttribute('aria-busy');

    form.querySelectorAll('[data-submit-disabled="1"]').forEach((button) => {
        button.disabled = false;
        delete button.dataset.submitDisabled;
    });
}

function resetSubmittingForms() {
    document.querySelectorAll('form[data-submitting="1"]').forEach(resetSubmittingForm);
}

// Never leave a form stuck in the submitting state when the page is restored
// from the back-forward cache.
window.addEventListener('pageshow', resetSubmittingForms);

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
