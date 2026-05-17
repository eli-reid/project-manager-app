class PwaManager {
    constructor() {
        this.deferredPrompt = null;
        this.registration = null;

        this.init();
    }

    async init() {
        this.captureInstallPrompt();
        this.captureInstallationState();
        this.observeNetworkState();
        await this.registerServiceWorker();

        window.triggerPWAInstall = () => this.install();
    }

    captureInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            this.deferredPrompt = event;
            window.dispatchEvent(new CustomEvent('pwa-installable'));
        });
    }

    captureInstallationState() {
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            document.documentElement.classList.add('pwa-installed');
        }

        window.addEventListener('appinstalled', () => {
            this.toast('App installed', 'Project Manager is ready on your home screen.');
            this.deferredPrompt = null;
            window.dispatchEvent(new CustomEvent('pwa-installed'));
        });
    }

    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        try {
            this.registration = await navigator.serviceWorker.register('/sw.js');

            this.registration.addEventListener('updatefound', () => {
                const worker = this.registration?.installing;

                if (!worker) {
                    return;
                }

                worker.addEventListener('statechange', () => {
                    if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                        this.showReloadPrompt();
                    }
                });
            });
        } catch (error) {
            console.error('PWA service worker registration failed.', error);
        }
    }

    observeNetworkState() {
        window.addEventListener('online', () => {
            this.toast('Back online', 'Queued actions can sync again.');
        });

        window.addEventListener('offline', () => {
            this.toast('Offline mode', 'Read views stay available while the connection is down.');
        });
    }

    async install() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            await this.deferredPrompt.userChoice;

            return;
        }

        if (this.isIosDevice()) {
            this.toast('Install this app', 'Use Share, then Add to Home Screen.');

            return;
        }

        this.toast('Install unavailable', 'Your browser does not currently expose the install prompt.');
    }

    isIosDevice() {
        const userAgent = window.navigator.userAgent || '';
        const platform = window.navigator.platform || '';

        return /iPad|iPhone|iPod/.test(userAgent)
            || /iPad|iPhone|iPod/.test(platform)
            || (platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
    }

    showReloadPrompt() {
        const container = this.ensureToastContainer();
        const toast = document.createElement('div');

        toast.className = 'pointer-events-auto flex items-center justify-between gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg';
        toast.innerHTML = '<div><div class="font-semibold">Update available</div><div class="text-emerald-50/90">Reload to use the newest mobile shell.</div></div><button type="button" class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-700">Reload</button>';
        toast.querySelector('button')?.addEventListener('click', () => window.location.reload());

        container.appendChild(toast);
        window.setTimeout(() => toast.remove(), 8000);
    }

    toast(title, message) {
        const container = this.ensureToastContainer();
        const toast = document.createElement('div');

        toast.className = 'pointer-events-auto rounded-2xl border border-zinc-700 bg-zinc-900/95 px-4 py-3 text-sm text-white shadow-lg backdrop-blur';
        toast.innerHTML = `<div class="font-semibold">${title}</div><div class="mt-1 text-zinc-300">${message}</div>`;

        container.appendChild(toast);
        window.setTimeout(() => toast.remove(), 4000);
    }

    ensureToastContainer() {
        let container = document.getElementById('pwa-toast-stack');

        if (container) {
            return container;
        }

        container = document.createElement('div');
        container.id = 'pwa-toast-stack';
        container.className = 'pointer-events-none fixed inset-x-4 top-4 z-60 flex flex-col gap-3';
        document.body.appendChild(container);

        return container;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.pwaManager = new PwaManager();
});