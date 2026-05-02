window.handleBackNavigation = function handleBackNavigation(fallbackUrl = '/mobile/dashboard') {
    if (window.history.length > 1) {
        window.history.back();

        return;
    }

    window.location.assign(fallbackUrl);
};

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-mobile-haptic]');

    if (! trigger || typeof navigator.vibrate !== 'function') {
        return;
    }

    navigator.vibrate(10);
});