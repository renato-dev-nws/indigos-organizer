import { usePage } from '@inertiajs/vue3';

/**
 * Subscribes the current browser to Web Push notifications.
 * Should be called once on mount in an authenticated layout.
 */
export function usePushSubscription() {
    const page = usePage();

    async function subscribe() {
        if (!('PushManager' in window) || !('serviceWorker' in navigator)) return;

        const vapidPublicKey = page.props.vapidPublicKey;
        if (!vapidPublicKey) return;

        try {
            const registration = await navigator.serviceWorker.ready;
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return;

            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                });
            }

            const json = subscription.toJSON();

            await fetch('/push-subscriptions', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrf(),
                },
                body: JSON.stringify({
                    endpoint: json.endpoint,
                    keys: json.keys,
                }),
            });
        } catch (e) {
            // Silently ignore — user may have denied, or browser incompatibility
            if (import.meta.env.DEV) console.warn('[PushSubscription]', e);
        }
    }

    return { subscribe };
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}
