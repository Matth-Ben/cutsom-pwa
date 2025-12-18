/**
 * Frontend Subscribe Script
 * 
 * Handles push notification subscription in the browser:
 * - Checks for service worker and Push API support
 * - Requests notification permission
 * - Subscribes to push notifications
 * - Sends subscription data to server
 * 
 * @package Custom_PWA
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /**
     * Initialize push subscription logic
     */
    function init() {
        // Check if push notifications are enabled
        if (!customPwaData.pushEnabled) {
            console.log('[Custom PWA] Push notifications are disabled in config');
            return;
        }

        // Check browser support
        if (!('serviceWorker' in navigator)) {
            console.log('[Custom PWA] Service Workers are not supported');
            return;
        }

        if (!('PushManager' in window)) {
            console.log('[Custom PWA] Push API is not supported');
            return;
        }

        // Register service worker first
        const swPath = customPwaData.swPath || '/sw.js';
        const localDevMode = customPwaData.localDevMode === '1';
        
        console.log('[Custom PWA] Registering service worker:', swPath);
        
        if (localDevMode) {
            console.warn('[Custom PWA] 🔓 Local Development Mode is ENABLED');
            console.warn('[Custom PWA] SSL certificate checks are bypassed for Service Worker');
            console.warn('[Custom PWA] ⚠️ NEVER use this mode in production!');
        }
        
        navigator.serviceWorker.register(swPath, { scope: '/' })
            .then(function(registration) {
                console.log('[Custom PWA] Service Worker registered successfully');
                return navigator.serviceWorker.ready;
            })
            .then(function(registration) {
                console.log('[Custom PWA] Service Worker is ready');
                
                // Check current subscription status
                return registration.pushManager.getSubscription();
            })
            .then(function(subscription) {
                if (subscription) {
                    console.log('[Custom PWA] Already subscribed to push notifications');
                    // Optionally update subscription on server
                    return subscription;
                }

                // Auto-subscribe or wait for user action
                // For now, we'll auto-subscribe if permission is already granted
                if (Notification.permission === 'granted') {
                    return subscribeToPush();
                }

                // TODO: Add a UI button to trigger subscription
                // Example: document.getElementById('enable-notifications-btn').addEventListener('click', subscribeToPush);
                console.log('[Custom PWA] Notification permission not granted yet. Add a button to trigger subscription.');
            })
            .catch(function(error) {
                console.error('[Custom PWA] Service Worker error:', error);
                
                // Check if it's an SSL certificate error
                if (error.name === 'SecurityError' && error.message.includes('SSL certificate')) {
                    console.error('[Custom PWA] ❌ SSL Certificate Error!');
                    console.warn('[Custom PWA] 📋 SOLUTIONS:');
                    console.warn('[Custom PWA] 1. Enable "Local Development Mode" in WordPress Admin:');
                    console.warn('[Custom PWA]    → Custom PWA → Config → Local Development Mode ✓');
                    console.warn('[Custom PWA] 2. OR open https://' + window.location.hostname + '/sw.js in a new tab');
                    console.warn('[Custom PWA]    → Accept the security warning');
                    console.warn('[Custom PWA]    → Reload this page');
                    console.warn('[Custom PWA] 3. OR use mkcert to generate valid local certificates');
                }
            });
    }

    /**
     * Subscribe to push notifications
     */
    function subscribeToPush() {
        console.log('[Custom PWA] Requesting notification permission...');

        return Notification.requestPermission().then(function(permission) {
            if (permission !== 'granted') {
                console.log('[Custom PWA] Notification permission denied');
                throw new Error('Permission not granted for notifications');
            }

            console.log('[Custom PWA] Notification permission granted');

            // Fetch VAPID public key
            return fetch(customPwaData.restUrl + 'custom-pwa/v1/public-key')
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    console.log('[Custom PWA] VAPID key received:', data.public_key ? 'Yes' : 'No');
                    if (!data.public_key) {
                        throw new Error('No VAPID public key available');
                    }
                    return data.public_key;
                });
        }).then(function(vapidPublicKey) {
            console.log('[Custom PWA] Got VAPID public key');

            // Get service worker registration
            return navigator.serviceWorker.ready.then(function(registration) {
                // Subscribe to push notifications
                return registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
                });
            });
        }).then(function(subscription) {
            console.log('[Custom PWA] Subscribed to push notifications');

            // Send subscription to server
            return sendSubscriptionToServer(subscription);
        }).catch(function(error) {
            console.error('[Custom PWA] Failed to subscribe:', error);
        });
    }

    /**
     * Send subscription data to server
     * 
     * @param {PushSubscription} subscription - Push subscription object
     */
    function sendSubscriptionToServer(subscription) {
        var subscriptionJson = subscription.toJSON();
        
        var data = {
            endpoint: subscriptionJson.endpoint,
            keys: subscriptionJson.keys,
            lang: customPwaData.lang || 'en',
            platform: detectPlatform(),
            userAgent: navigator.userAgent
        };

        console.log('[Custom PWA] Sending subscription to server...');

        return fetch(customPwaData.restUrl + 'custom-pwa/v1/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(result) {
            if (result.success) {
                console.log('[Custom PWA] Subscription saved on server');
            } else {
                console.error('[Custom PWA] Failed to save subscription:', result);
            }
        })
        .catch(function(error) {
            console.error('[Custom PWA] Error sending subscription to server:', error);
        });
    }

    /**
     * Unsubscribe from push notifications
     */
    function unsubscribeFromPush() {
        return navigator.serviceWorker.ready.then(function(registration) {
            return registration.pushManager.getSubscription();
        }).then(function(subscription) {
            if (!subscription) {
                console.log('[Custom PWA] No subscription to unsubscribe from');
                return;
            }

            var endpoint = subscription.endpoint;

            // Unsubscribe from browser
            return subscription.unsubscribe().then(function(successful) {
                if (successful) {
                    console.log('[Custom PWA] Unsubscribed from push notifications');

                    // Notify server
                    return fetch(customPwaData.restUrl + 'custom-pwa/v1/unsubscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ endpoint: endpoint })
                    });
                }
            });
        }).catch(function(error) {
            console.error('[Custom PWA] Failed to unsubscribe:', error);
        });
    }

    /**
     * Detect platform from user agent
     * 
     * @returns {string} Platform name (android, ios, mac, windows, other)
     */
    function detectPlatform() {
        var userAgent = navigator.userAgent.toLowerCase();

        if (/android/.test(userAgent)) {
            return 'android';
        } else if (/iphone|ipad|ipod/.test(userAgent)) {
            return 'ios';
        } else if (/mac os x/.test(userAgent)) {
            return 'mac';
        } else if (/windows/.test(userAgent)) {
            return 'windows';
        }

        return 'other';
    }

    /**
     * Convert VAPID public key from URL-safe base64 to Uint8Array
     * 
     * @param {string} base64String - Base64 encoded VAPID key
     * @returns {Uint8Array} - Decoded key
     */
    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);

        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Expose functions globally for optional manual control
    window.customPwa = {
        subscribe: subscribeToPush,
        unsubscribe: unsubscribeFromPush
    };

})();
