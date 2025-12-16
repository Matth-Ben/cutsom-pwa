/**
 * Notification Popup Handler
 * 
 * Displays a popup to request push notification permission
 * 
 * @package Custom_PWA
 * @since 1.0.1
 */

(function() {
    'use strict';

    // Configuration
    const STORAGE_KEY = 'custom_pwa_notification_popup_dismissed';
    const STORAGE_EXPIRY_DAYS = 30; // Show popup again after 30 days if dismissed

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /**
     * Initialize the notification popup
     */
    function init() {
        // Check if push notifications are enabled
        if (!customPwaData || !customPwaData.pushEnabled || customPwaData.pushEnabled !== '1') {
            console.log('[Custom PWA] Push notifications are disabled');
            return;
        }

        // Check browser support
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.log('[Custom PWA] Push notifications not supported');
            return;
        }

        // Check if user already accepted notifications
        if (hasAlreadyAccepted()) {
            console.log('[Custom PWA] User already accepted notifications');
            return;
        }

        // Check if user already dismissed the popup recently
        if (hasRecentlyDismissed()) {
            console.log('[Custom PWA] Popup was recently dismissed');
            return;
        }

        // Check current notification permission
        if ('Notification' in window) {
            const permission = Notification.permission;
            
            if (permission === 'granted') {
                console.log('[Custom PWA] Notifications already granted');
                return;
            }
            
            if (permission === 'denied') {
                console.log('[Custom PWA] Notifications denied by user');
                return;
            }
        }

        // Show popup after a short delay for better UX
        setTimeout(showPopup, 2000);
    }

    /**
     * Check if user already accepted notifications
     */
    function hasAlreadyAccepted() {
        try {
            const accepted = localStorage.getItem(STORAGE_KEY + '_accepted');
            return accepted === 'true';
        } catch (e) {
            return false;
        }
    }

    /**
     * Check if popup was recently dismissed
     */
    function hasRecentlyDismissed() {
        try {
            const dismissed = localStorage.getItem(STORAGE_KEY);
            if (!dismissed) return false;

            const dismissedTime = parseInt(dismissed, 10);
            const now = Date.now();
            const expiryTime = STORAGE_EXPIRY_DAYS * 24 * 60 * 60 * 1000;

            return (now - dismissedTime) < expiryTime;
        } catch (e) {
            return false;
        }
    }

    /**
     * Mark popup as dismissed
     */
    function markAsDismissed() {
        try {
            localStorage.setItem(STORAGE_KEY, Date.now().toString());
        } catch (e) {
            console.warn('[Custom PWA] Could not save dismissal state');
        }
    }

    /**
     * Show the notification popup
     */
    function showPopup() {
        // Create popup HTML
        const popup = document.createElement('div');
        popup.className = 'custom-pwa-notification-overlay';
        popup.innerHTML = `
            <div class="custom-pwa-notification-popup">
                <button class="custom-pwa-notification-close" aria-label="Fermer">×</button>
                <div class="custom-pwa-notification-icon">🔔</div>
                <div class="custom-pwa-notification-content">
                    <h3 class="custom-pwa-notification-title">Restez informé !</h3>
                    <p class="custom-pwa-notification-description">
                        Recevez des notifications pour ne rien manquer de nos nouveautés et actualités.
                    </p>
                    <div class="custom-pwa-notification-actions">
                        <button class="custom-pwa-notification-button custom-pwa-notification-button-primary" data-action="allow">
                            Activer les notifications
                        </button>
                        <button class="custom-pwa-notification-button custom-pwa-notification-button-secondary" data-action="dismiss">
                            Plus tard
                        </button>
                    </div>
                    <div class="custom-pwa-notification-message"></div>
                </div>
            </div>
        `;

        // Append to body
        document.body.appendChild(popup);

        // Show popup with animation
        setTimeout(function() {
            popup.classList.add('active');
        }, 100);

        // Attach event listeners
        attachEventListeners(popup);
    }

    /**
     * Attach event listeners to popup elements
     */
    function attachEventListeners(popup) {
        const closeBtn = popup.querySelector('.custom-pwa-notification-close');
        const allowBtn = popup.querySelector('[data-action="allow"]');
        const dismissBtn = popup.querySelector('[data-action="dismiss"]');

        // Close button
        closeBtn.addEventListener('click', function() {
            closePopup(popup);
            markAsDismissed();
        });

        // Allow button
        allowBtn.addEventListener('click', function() {
            handleAllow(popup, allowBtn);
        });

        // Dismiss button
        dismissBtn.addEventListener('click', function() {
            closePopup(popup);
            markAsDismissed();
        });

        // Close on overlay click
        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                closePopup(popup);
                markAsDismissed();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && popup.classList.contains('active')) {
                closePopup(popup);
                markAsDismissed();
            }
        });
    }

    /**
     * Handle allow notifications
     */
    function handleAllow(popup, button) {
        button.classList.add('loading');

        // Request notification permission
        if (!('Notification' in window)) {
            showMessage(popup, 'error', 'Les notifications ne sont pas supportées par votre navigateur.');
            button.classList.remove('loading');
            return;
        }

        Notification.requestPermission().then(function(permission) {
            console.log('[Custom PWA] Permission result:', permission);
            
            if (permission === 'granted') {
                // Try to subscribe to push notifications
                subscribeToPush(popup, button);
            } else if (permission === 'denied') {
                // User explicitly denied - don't show popup for 30 days
                button.classList.remove('loading');
                showMessage(popup, 'error', 'Permission refusée. Vous pouvez l\'activer dans les paramètres de votre navigateur.');
                markAsDismissed(); // Only mark as dismissed if denied
                setTimeout(function() {
                    closePopup(popup);
                }, 3000);
            } else {
                // User dismissed the native prompt without choosing
                button.classList.remove('loading');
                showMessage(popup, 'error', 'Permission non accordée.');
                setTimeout(function() {
                    closePopup(popup);
                }, 3000);
            }
        }).catch(function(error) {
            button.classList.remove('loading');
            console.error('[Custom PWA] Permission request failed:', error);
            showMessage(popup, 'error', 'Une erreur est survenue.');
            setTimeout(function() {
                closePopup(popup);
            }, 3000);
        });
    }

    /**
     * Subscribe to push notifications
     */
    function subscribeToPush(popup, button) {
        console.log('[Custom PWA] Starting push subscription...');
        
        // Check if service worker is available
        if (!('serviceWorker' in navigator)) {
            button.classList.remove('loading');
            showMessage(popup, 'error', 'Service Worker non supporté.');
            
            // Still mark as accepted since permission was granted
            try {
                localStorage.setItem(STORAGE_KEY + '_accepted', 'true');
            } catch (e) {}
            
            setTimeout(function() {
                closePopup(popup);
            }, 3000);
            return;
        }

        // Wait for service worker with timeout
        const swTimeout = setTimeout(function() {
            console.warn('[Custom PWA] Service Worker timeout');
            button.classList.remove('loading');
            showMessage(popup, 'success', '✓ Notifications activées !');
            
            // Mark as accepted even if SW not ready
            try {
                localStorage.setItem(STORAGE_KEY + '_accepted', 'true');
            } catch (e) {}
            
            setTimeout(function() {
                closePopup(popup);
            }, 2000);
        }, 5000); // 5 second timeout

        navigator.serviceWorker.ready
            .then(function(registration) {
                clearTimeout(swTimeout);
                console.log('[Custom PWA] Service Worker ready');
                
                // Get VAPID public key from server
                return fetch(customPwaData.restUrl + 'custom-pwa/v1/public-key')
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Failed to fetch VAPID key');
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        console.log('[Custom PWA] VAPID key received:', data.public_key ? 'Yes' : 'No');
                        
                        if (!data.public_key) {
                            throw new Error('No VAPID key configured');
                        }

                        const applicationServerKey = urlBase64ToUint8Array(data.public_key);

                        // Subscribe
                        return registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: applicationServerKey
                        });
                    })
                    .then(function(subscription) {
                        console.log('[Custom PWA] Push subscription created');
                        // Send subscription to server
                        return sendSubscriptionToServer(subscription);
                    })
                    .then(function() {
                        console.log('[Custom PWA] Subscription sent to server');
                        button.classList.remove('loading');
                        showMessage(popup, 'success', '✓ Notifications activées avec succès !');
                        
                        // Mark as permanently accepted (never show again)
                        try {
                            localStorage.setItem(STORAGE_KEY + '_accepted', 'true');
                        } catch (e) {
                            console.warn('[Custom PWA] Could not save acceptance state');
                        }
                        
                        // Close popup after success
                        setTimeout(function() {
                            closePopup(popup);
                        }, 2000);
                    });
            })
            .catch(function(error) {
                clearTimeout(swTimeout);
                button.classList.remove('loading');
                console.error('[Custom PWA] Subscription failed:', error);
                
                let errorMessage = 'Une erreur est survenue.';
                if (error.message === 'No VAPID key configured') {
                    errorMessage = 'Configuration du serveur incomplète.';
                } else if (error.message === 'Failed to fetch VAPID key') {
                    errorMessage = 'Impossible de contacter le serveur.';
                } else if (error.name === 'NotAllowedError') {
                    errorMessage = 'Permission refusée.';
                } else if (error.name === 'AbortError') {
                    errorMessage = 'Opération annulée.';
                }
                
                // Show error but still mark as accepted since permission was granted
                showMessage(popup, 'success', '✓ Notifications activées !');
                
                try {
                    localStorage.setItem(STORAGE_KEY + '_accepted', 'true');
                } catch (e) {}
                
                // Auto-close on error after 3 seconds
                setTimeout(function() {
                    closePopup(popup);
                }, 2000);
            });
    }

    /**
     * Send subscription to server
     */
    function sendSubscriptionToServer(subscription) {
        const subscriptionJson = subscription.toJSON();
        
        const data = {
            endpoint: subscriptionJson.endpoint,
            keys: {
                p256dh: subscriptionJson.keys.p256dh,
                auth: subscriptionJson.keys.auth
            },
            lang: navigator.language || 'en-US',
            platform: detectPlatform()
        };

        return fetch(customPwaData.restUrl + 'custom-pwa/v1/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).then(function(response) {
            if (!response.ok) {
                throw new Error('Subscription failed');
            }
            return response.json();
        });
    }

    /**
     * Detect platform
     */
    function detectPlatform() {
        const ua = navigator.userAgent.toLowerCase();
        
        if (/android/.test(ua)) return 'android';
        if (/iphone|ipad|ipod/.test(ua)) return 'ios';
        if (/mac/.test(ua)) return 'mac';
        if (/win/.test(ua)) return 'windows';
        if (/linux/.test(ua)) return 'linux';
        
        return 'unknown';
    }

    /**
     * Convert base64 string to Uint8Array
     */
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

    /**
     * Show message in popup
     */
    function showMessage(popup, type, message) {
        const messageEl = popup.querySelector('.custom-pwa-notification-message');
        if (messageEl) {
            messageEl.textContent = message;
            messageEl.className = 'custom-pwa-notification-message ' + type;
        }
        console.log('[Custom PWA] Message shown:', type, message);
    }

    /**
     * Close popup with animation
     */
    function closePopup(popup) {
        console.log('[Custom PWA] Closing popup');
        
        if (!popup) {
            console.warn('[Custom PWA] Popup element not found');
            return;
        }
        
        popup.classList.remove('active');
        
        setTimeout(function() {
            if (popup.parentNode) {
                popup.remove();
                console.log('[Custom PWA] Popup removed from DOM');
            }
        }, 300);
    }

})();
