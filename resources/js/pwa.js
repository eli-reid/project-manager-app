class PwaManager {
    constructor() {
        this.deferredPrompt = null;
        this.registration = null;
        this.vapidPublicKey = this.metaContent('vapid-public-key');
        this.pushSubscribeUrl = this.metaContent('push-subscribe-url');
        this.pushUnsubscribeUrl = this.metaContent('push-unsubscribe-url');

        this.init();
    }

    async init() {
        this.captureInstallPrompt();
        this.captureInstallationState();
        this.observeNetworkState();
        await this.registerServiceWorker();
        await this.initializePushNotifications();

        window.triggerPWAInstall = () => this.install();
    }

    metaContent(name) {
        const element = document.querySelector(`meta[name="${name}"]`);

        if (!element) {
            return null;
        }

        const content = element.getAttribute('content');

        return typeof content === 'string' ? content : null;
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

    async initializePushNotifications() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            return;
        }

        if (this.metaContent('push-authenticated') !== '1') {
            return;
        }

        if (!this.vapidPublicKey || !this.pushSubscribeUrl || !this.pushUnsubscribeUrl) {
            return;
        }

        window.enablePushNotifications = () => this.subscribeToPush({ requestPermission: true, showToast: true });
        window.disablePushNotifications = () => this.unsubscribeFromPush({ showToast: true });

        if (Notification.permission === 'granted') {
            await this.subscribeToPush({ requestPermission: false, showToast: false });
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

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);

        return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
    }

    async subscribeToPush({ requestPermission, showToast }) {
        if (!this.vapidPublicKey || !this.pushSubscribeUrl) {
            return false;
        }

        try {
            if (requestPermission && Notification.permission !== 'granted') {
                const permission = await Notification.requestPermission();

                if (permission !== 'granted') {
                    if (showToast) {
                        this.toast('Push disabled', 'Notifications permission was not granted.');
                    }

                    return false;
                }
            }

            if (Notification.permission !== 'granted') {
                return false;
            }

            const registration = await navigator.serviceWorker.ready;
            const encoding = (PushManager.supportedContentEncodings || ['aes128gcm'])[0];
            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey),
                });
            }

            const json = subscription.toJSON();

            const response = await fetch(this.pushSubscribeUrl, {
                method: 'POST',
                headers: this.requestHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({
                    endpoint: json.endpoint,
                    key: json.keys?.p256dh || null,
                    token: json.keys?.auth || null,
                    encoding,
                }),
            });

            if (!response.ok) {
                throw new Error('Push subscription endpoint request failed.');
            }

            window.dispatchEvent(new CustomEvent('push-subscription-changed', {
                detail: { subscribed: true },
            }));

            if (showToast) {
                this.toast('Push enabled', 'You will now receive reminder notifications.');
            }

            return true;
        } catch (error) {
            console.error('Push subscribe failed.', error);

            if (showToast) {
                this.toast('Push setup failed', 'Unable to enable push notifications right now.');
            }

            return false;
        }
    }

    async unsubscribeFromPush({ showToast }) {
        if (!this.pushUnsubscribeUrl || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            return false;
        }

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                return true;
            }

            const endpoint = subscription.endpoint;

            const response = await fetch(this.pushUnsubscribeUrl, {
                method: 'DELETE',
                headers: this.requestHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ endpoint }),
            });

            if (!response.ok) {
                throw new Error('Push unsubscribe endpoint request failed.');
            }

            await subscription.unsubscribe();

            window.dispatchEvent(new CustomEvent('push-subscription-changed', {
                detail: { subscribed: false },
            }));

            if (showToast) {
                this.toast('Push disabled', 'Push notifications have been turned off for this device.');
            }

            return true;
        } catch (error) {
            console.error('Push unsubscribe failed.', error);

            if (showToast) {
                this.toast('Push update failed', 'Unable to disable push notifications right now.');
            }

            return false;
        }
    }

    requestHeaders() {
        const csrf = this.metaContent('csrf-token') || '';

        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        };
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