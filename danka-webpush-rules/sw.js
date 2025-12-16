/* Service Worker for Danka WebPush Rules */
'use strict';

// Service worker version
const CACHE_VERSION = 'danka-webpush-v1';

// Install event
self.addEventListener('install', function(event) {
    console.log('Service Worker: Installing...');
    // Skip waiting to activate immediately
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', function(event) {
    console.log('Service Worker: Activating...');
    // Claim all clients immediately
    event.waitUntil(self.clients.claim());
});

// Push event - handle incoming push notifications
self.addEventListener('push', function(event) {
    console.log('Push notification received:', event);
    
    let notificationData = {
        title: 'New Notification',
        body: 'You have a new notification.',
        icon: '/wp-content/plugins/danka-webpush-rules/assets/images/icon.png',
        badge: '/wp-content/plugins/danka-webpush-rules/assets/images/badge.png',
        tag: 'notification-' + Date.now(),
        requireInteraction: false,
        data: {
            url: '/'
        }
    };
    
    // Parse notification data from push event
    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = {
                title: data.title || notificationData.title,
                body: data.body || notificationData.body,
                icon: data.icon || notificationData.icon,
                badge: data.badge || notificationData.badge,
                tag: data.tag || notificationData.tag,
                requireInteraction: data.requireInteraction || false,
                data: {
                    url: data.url || notificationData.data.url,
                    ...data.data
                }
            };
        } catch (error) {
            console.error('Error parsing push notification data:', error);
        }
    }
    
    // Show the notification
    event.waitUntil(
        self.registration.showNotification(notificationData.title, {
            body: notificationData.body,
            icon: notificationData.icon,
            badge: notificationData.badge,
            tag: notificationData.tag,
            requireInteraction: notificationData.requireInteraction,
            data: notificationData.data
        })
    );
});

// Notification click event
self.addEventListener('notificationclick', function(event) {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    // Get the URL to open
    const urlToOpen = event.notification.data && event.notification.data.url 
        ? event.notification.data.url 
        : '/';
    
    // Open the URL in a new window or focus existing window
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

// Notification close event
self.addEventListener('notificationclose', function(event) {
    console.log('Notification closed:', event);
});

// Push subscription change event
self.addEventListener('pushsubscriptionchange', function(event) {
    console.log('Push subscription changed:', event);
    
    event.waitUntil(
        self.registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: event.oldSubscription.options.applicationServerKey
        }).then(function(subscription) {
            // Send new subscription to server
            return fetch('/wp-json/danka-webpush/v1/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: arrayBufferToBase64(subscription.getKey('p256dh')),
                        auth: arrayBufferToBase64(subscription.getKey('auth'))
                    }
                })
            });
        })
    );
});

// Utility function to convert array buffer to base64
function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return self.btoa(binary);
}

// Message event - handle messages from clients
self.addEventListener('message', function(event) {
    console.log('Service Worker received message:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
