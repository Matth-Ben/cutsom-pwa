/**
 * Example Service Worker
 * 
 * This is an example service worker for the Custom PWA plugin.
 * Copy this file to your site root as /sw.js (or another path and adjust frontend-subscribe.js).
 * 
 * Features:
 * - Push notification handling
 * - Notification click handling
 * - Basic caching (optional)
 * 
 * @package Custom_PWA
 * @since 1.0.0
 */

const CACHE_VERSION = 'v1';
const CACHE_NAME = 'custom-pwa-cache-' + CACHE_VERSION;

// Files to cache (customize as needed)
const STATIC_ASSETS = [
    '/',
    '/wp-content/plugins/custom-pwa/assets/examples/offline-example.html'
];

/**
 * Install event
 * Pre-cache static assets
 */
self.addEventListener('install', function(event) {
    console.log('[Service Worker] Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('[Service Worker] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(function() {
                // Force the waiting service worker to become the active service worker
                return self.skipWaiting();
            })
    );
});

/**
 * Activate event
 * Clean up old caches
 */
self.addEventListener('activate', function(event) {
    console.log('[Service Worker] Activating...');
    
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[Service Worker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(function() {
            // Take control of all clients immediately
            return self.clients.claim();
        })
    );
});

/**
 * Push event
 * Handle incoming push notifications
 */
self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push received');

    let notificationData = {
        title: 'New Notification',
        body: 'You have a new notification',
        icon: '/wp-content/plugins/custom-pwa/assets/images/icon-192x192.png',
        badge: '/wp-content/plugins/custom-pwa/assets/images/badge-72x72.png',
        tag: 'default',
        data: {
            url: '/'
        }
    };

    // Parse push data if available
    if (event.data) {
        try {
            const payload = event.data.json();
            
            if (payload.title) {
                notificationData.title = payload.title;
            }
            if (payload.body) {
                notificationData.body = payload.body;
            }
            if (payload.icon) {
                notificationData.icon = payload.icon;
            }
            if (payload.badge) {
                notificationData.badge = payload.badge;
            }
            if (payload.tag) {
                notificationData.tag = payload.tag;
            }
            if (payload.url) {
                notificationData.data.url = payload.url;
            }
            if (payload.data) {
                notificationData.data = Object.assign(notificationData.data, payload.data);
            }
        } catch (error) {
            console.error('[Service Worker] Error parsing push data:', error);
        }
    }

    const options = {
        body: notificationData.body,
        icon: notificationData.icon,
        badge: notificationData.badge,
        tag: notificationData.tag,
        data: notificationData.data,
        requireInteraction: false,
        vibrate: [200, 100, 200]
    };

    event.waitUntil(
        self.registration.showNotification(notificationData.title, options)
    );
});

/**
 * Notification click event
 * Handle notification clicks
 */
self.addEventListener('notificationclick', function(event) {
    console.log('[Service Worker] Notification clicked');

    event.notification.close();

    const urlToOpen = event.notification.data && event.notification.data.url 
        ? event.notification.data.url 
        : '/';

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(function(clientList) {
            // Check if there's already a window open with this URL
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // If no window is open, open a new one
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

/**
 * Fetch event (optional caching strategy)
 * 
 * This is a basic cache-first strategy for static assets.
 * Customize based on your needs (network-first, stale-while-revalidate, etc.)
 */
self.addEventListener('fetch', function(event) {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip admin and REST API requests
    if (event.request.url.includes('/wp-admin/') || 
        event.request.url.includes('/wp-json/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(function(cachedResponse) {
                // Return cached response if found
                if (cachedResponse) {
                    return cachedResponse;
                }

                // Otherwise fetch from network
                return fetch(event.request)
                    .then(function(response) {
                        // Don't cache if not a valid response
                        if (!response || response.status !== 200 || response.type === 'error') {
                            return response;
                        }

                        // Clone the response
                        const responseToCache = response.clone();

                        // Cache the response for future use (optional)
                        // Uncomment if you want to cache dynamically
                        /*
                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                            });
                        */

                        return response;
                    })
                    .catch(function() {
                        // If both cache and network fail, show offline page
                        return caches.match('/wp-content/plugins/custom-pwa/assets/examples/offline-example.html');
                    });
            })
    );
});

/**
 * Message event
 * Handle messages from clients (optional)
 */
self.addEventListener('message', function(event) {
    console.log('[Service Worker] Message received:', event.data);

    if (event.data && event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }
});
