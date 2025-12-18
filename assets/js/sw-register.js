/**
 * Service Worker Registration Script
 * 
 * Registers the Service Worker for PWA functionality.
 * This runs independently of push notifications to ensure
 * the app is installable even if push is disabled.
 * 
 * @package Custom_PWA
 * @since 1.0.3
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
     * Initialize Service Worker registration
     */
    function init() {
        // Check if customPwaData exists
        if (typeof customPwaData === 'undefined') {
            console.error('[Custom PWA] customPwaData is not defined. Script may not be properly enqueued.');
            return;
        }

        console.log('[Custom PWA] customPwaData:', customPwaData);

        // Check if PWA is enabled (check for '1' string or true boolean)
        if (!customPwaData.pwaEnabled || customPwaData.pwaEnabled === '0') {
            console.log('[Custom PWA] PWA is disabled');
            return;
        }

        // Check browser support
        if (!('serviceWorker' in navigator)) {
            console.log('[Custom PWA] Service Workers are not supported');
            return;
        }

        // Get configuration
        var swPath = customPwaData.swPath || '/sw.js';
        var localDevMode = customPwaData.localDevMode === '1';
        var pushEnabled = customPwaData.pushEnabled === '1';
        
        console.log('[Custom PWA] Registering service worker:', swPath);
        
        if (localDevMode) {
            console.warn('[Custom PWA] 🔓 Local Development Mode is ENABLED');
            console.warn('[Custom PWA] SSL certificate checks are bypassed for Service Worker');
            console.warn('[Custom PWA] ⚠️ NEVER use this mode in production!');
        }
        
        // Register Service Worker
        navigator.serviceWorker.register(swPath, { scope: '/' })
            .then(function(registration) {
                console.log('[Custom PWA] ✓ Service Worker registered successfully');
                console.log('[Custom PWA] Scope:', registration.scope);
                
                // Wait for SW to be ready
                return navigator.serviceWorker.ready;
            })
            .then(function(registration) {
                console.log('[Custom PWA] ✓ Service Worker is ready and controlling the page');
                
                if (pushEnabled) {
                    console.log('[Custom PWA] Push notifications are enabled');
                } else {
                    console.log('[Custom PWA] PWA is installable (Push notifications disabled)');
                }
            })
            .catch(function(error) {
                console.error('[Custom PWA] ✗ Service Worker registration failed:', error);
                
                // Check if it's an SSL certificate error
                if (error.name === 'SecurityError' && error.message.includes('SSL certificate')) {
                    console.error('[Custom PWA] ❌ SSL Certificate Error!');
                    console.warn('[Custom PWA] 📋 SOLUTIONS:');
                    console.warn('[Custom PWA] 1. Enable "Local Development Mode" in WordPress Admin:');
                    console.warn('[Custom PWA]    → Custom PWA → Config → Local Development Mode ✓');
                    console.warn('[Custom PWA] 2. OR use the SSL Helper:');
                    console.warn('[Custom PWA]    → Custom PWA → 🔒 SSL Helper');
                    console.warn('[Custom PWA] 3. OR open https://' + window.location.hostname + '/sw.js in a new tab');
                    console.warn('[Custom PWA]    → Accept the security warning');
                    console.warn('[Custom PWA]    → Reload this page');
                }
            });
    }

})();
