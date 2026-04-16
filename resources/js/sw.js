// Índigos - Artist Organizer – custom service worker
// Uses Workbox for precaching + adds Web Push support

import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute, NavigationRoute } from 'workbox-routing';
import { NetworkFirst } from 'workbox-strategies';

// Workbox injects the precache manifest here
precacheAndRoute(self.__WB_MANIFEST);

// Network-first for navigation requests (server-side rendered Inertia pages)
registerRoute(
    new NavigationRoute(new NetworkFirst({ networkTimeoutSeconds: 5 }), {
        denylist: [/^\/api\//],
    }),
);

// ─── Push event ──────────────────────────────────────────────────────────────
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Índigos - Artist Organizer', body: event.data.text() };
    }

    const title = payload.title ?? 'Índigos - Artist Organizer';
    const options = {
        body: payload.body ?? payload.message ?? '',
        icon: payload.icon ?? '/icons/io-icon-192x192.png',
        badge: '/icons/io-icon-192x192.png',
        data: payload.data ?? {},
        tag: payload.tag ?? 'band-organizer',
        renotify: true,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// ─── Notification click ───────────────────────────────────────────────────────
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url ?? '/dashboard';

    event.waitUntil(
        clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Focus existing window if available
                for (const client of windowClients) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.navigate(url);
                        return client.focus();
                    }
                }
                // Otherwise open a new window
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            }),
    );
});
