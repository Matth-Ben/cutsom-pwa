/* Frontend JavaScript for Danka WebPush Rules */
(function() {
    'use strict';
    
    // Check if service workers and notifications are supported
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.log('Push notifications are not supported in this browser.');
        return;
    }
    
    // Register service worker
    function registerServiceWorker() {
        return navigator.serviceWorker.register(dankaWebPush.swUrl)
            .then(function(registration) {
                console.log('Service Worker registered successfully:', registration);
                return registration;
            })
            .catch(function(error) {
                console.error('Service Worker registration failed:', error);
                throw error;
            });
    }
    
    // Request notification permission
    function requestNotificationPermission() {
        return Notification.requestPermission().then(function(permission) {
            if (permission === 'granted') {
                console.log('Notification permission granted.');
                return true;
            } else {
                console.log('Notification permission denied.');
                return false;
            }
        });
    }
    
    // Subscribe to push notifications
    function subscribeToPush(registration) {
        return registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(getApplicationServerKey())
        }).then(function(subscription) {
            console.log('Push subscription:', subscription);
            return sendSubscriptionToServer(subscription);
        }).catch(function(error) {
            console.error('Failed to subscribe to push notifications:', error);
        });
    }
    
    // Send subscription to server
    function sendSubscriptionToServer(subscription) {
        const subscriptionData = {
            endpoint: subscription.endpoint,
            keys: {
                p256dh: arrayBufferToBase64(subscription.getKey('p256dh')),
                auth: arrayBufferToBase64(subscription.getKey('auth'))
            }
        };
        
        return fetch(dankaWebPush.restUrl + '/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': dankaWebPush.nonce
            },
            body: JSON.stringify(subscriptionData)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            console.log('Subscription sent to server:', data);
            return data;
        })
        .catch(function(error) {
            console.error('Failed to send subscription to server:', error);
        });
    }
    
    // Unsubscribe from push notifications
    function unsubscribeFromPush() {
        return navigator.serviceWorker.ready.then(function(registration) {
            return registration.pushManager.getSubscription();
        }).then(function(subscription) {
            if (subscription) {
                const endpoint = subscription.endpoint;
                
                return subscription.unsubscribe().then(function(successful) {
                    if (successful) {
                        return fetch(dankaWebPush.restUrl + '/unsubscribe', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': dankaWebPush.nonce
                            },
                            body: JSON.stringify({ endpoint: endpoint })
                        });
                    }
                });
            }
        }).then(function() {
            console.log('Successfully unsubscribed from push notifications.');
        }).catch(function(error) {
            console.error('Failed to unsubscribe:', error);
        });
    }
    
    // Utility functions
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
    
    function arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }
    
    // Get application server key (VAPID public key)
    // IMPORTANT: In production, this should be loaded from WordPress options
    // and configured via the admin interface or wp-config.php
    function getApplicationServerKey() {
        // Check if key is provided via inline script (set by WordPress)
        if (typeof window.dankaWebPushVapidKey !== 'undefined' && window.dankaWebPushVapidKey) {
            return window.dankaWebPushVapidKey;
        }
        
        // Fallback placeholder key - MUST be replaced for production use
        // Generate keys with: npx web-push generate-vapid-keys
        console.warn('Using placeholder VAPID key. Please configure proper keys in production!');
        return 'BEl62iUYgUivxIkv69yViEuiBIa-Ib37J8YN2iu6F3GKZMgJVoGPOYHdAJRf_oIf3c1qEt1dWd5v95a6qZ5wM=';
    }
    
    // Initialize push notifications
    function initPushNotifications() {
        registerServiceWorker().then(function(registration) {
            return requestNotificationPermission().then(function(granted) {
                if (granted) {
                    return subscribeToPush(registration);
                }
            });
        }).catch(function(error) {
            console.error('Failed to initialize push notifications:', error);
        });
    }
    
    // Expose functions globally for custom implementation
    window.dankaWebPushAPI = {
        init: initPushNotifications,
        subscribe: function() {
            return navigator.serviceWorker.ready.then(function(registration) {
                return requestNotificationPermission().then(function(granted) {
                    if (granted) {
                        return subscribeToPush(registration);
                    }
                });
            });
        },
        unsubscribe: unsubscribeFromPush,
        checkSubscription: function() {
            return navigator.serviceWorker.ready.then(function(registration) {
                return registration.pushManager.getSubscription();
            });
        }
    };
    
    // Auto-initialize on page load (optional - can be disabled for manual control)
    // Comment out the next line if you want to manually control initialization
    // initPushNotifications();
    
})();
