# Custom PWA - Example Files

This directory contains example files that you need to manually copy and configure for your Progressive Web App to work correctly.

## Files Included

1. **sw-example.js** - Example service worker
2. **offline-example.html** - Offline fallback page
3. **README.md** - This file

## Setup Instructions

### 1. Service Worker Setup

The service worker is the core component that enables push notifications and offline functionality.

#### Copy the Service Worker

Copy `sw-example.js` to your site root and rename it to `sw.js`:

```bash
cp wp-content/plugins/custom-pwa/assets/examples/sw-example.js sw.js
```

**Important:** The service worker MUST be served from your site root (or a higher-level directory than the pages it controls). WordPress plugins cannot directly serve service workers from their plugin directory due to scope limitations.

#### Register the Service Worker

The plugin's frontend JavaScript (`frontend-subscribe.js`) expects the service worker to be at `/sw.js`. If you need a different path, update the `swPath` value in the localized script data.

To manually register the service worker, add this code to your theme (optional, as the plugin handles registration):

```javascript
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(function(registration) {
            console.log('Service Worker registered:', registration);
        })
        .catch(function(error) {
            console.error('Service Worker registration failed:', error);
        });
}
```

### 2. Offline Page (Optional)

Copy the offline page to your site root or keep it in the plugin directory:

```bash
cp wp-content/plugins/custom-pwa/assets/examples/offline-example.html offline.html
```

Or reference it directly from the plugin directory in your service worker cache configuration.

## Configuration Steps

### Step 1: Enable PWA Features

1. Go to **WordPress Admin** → **Custom PWA** → **Config**
2. Check **"Enable PWA features"**
3. Check **"Enable Web Push notifications"**
4. Select your site type (Generic, E-commerce, Events, etc.)
5. Select post types to manage with Web Push
6. Save changes

### Step 2: Configure PWA Settings

1. Go to **Custom PWA** → **PWA**
2. Configure:
   - App name
   - Short name
   - Description
   - Theme and background colors
   - Display mode
   - App icon (or use WordPress Site Icon)
3. Save changes
4. Test the manifest: Visit `https://yoursite.com/manifest.webmanifest`

### Step 3: Configure Push Notifications

1. Go to **Custom PWA** → **Push**
2. For each enabled post type:
   - Check **"Enable Notifications"**
   - Configure title template (e.g., "New Post: {post_title}")
   - Configure body template (e.g., "{excerpt}")
   - Configure URL template (e.g., "{permalink}")
3. Save changes

### Step 4: Test Push Notifications

1. On the **Push** page, scroll to "Send Test Notification"
2. Enter test title, body, and URL
3. Click **"Send Test Notification"**
4. Check the result message

## Available Placeholders

Use these placeholders in your notification templates:

### Universal Placeholders
- `{post_title}` - Post title
- `{permalink}` - Full URL to the post
- `{excerpt}` - Post excerpt (auto-generated if not set)
- `{post_type}` - Post type name

### Event-Specific Placeholders (when site type is "Events")
- `{event_date}` - Event date (from `_event_date` post meta)
- `{venue}` - Event venue (from `_venue` post meta)
- `{status_label}` - Event status (from `_status_label` post meta)

## Testing the PWA

### Test PWA Installation

1. Open your site in Chrome/Edge
2. Look for the install icon in the address bar
3. Click to install as PWA
4. The app should open in standalone mode

### Test Push Notifications

1. Visit your site's homepage
2. Open browser DevTools → Console
3. You should see logs from the subscription script
4. If permission is granted, subscription is automatic
5. Publish a new post of an enabled type
6. You should receive a notification (if Web Push library is integrated)

### Test Offline Functionality

1. Open DevTools → Network tab
2. Check "Offline" to simulate no connection
3. Reload the page
4. You should see the offline page

## Service Worker Customization

### Cache Strategy

The example service worker uses a **cache-first** strategy. You can customize this:

- **Network-first**: Try network, fallback to cache
- **Cache-first**: Use cache if available, otherwise network
- **Stale-while-revalidate**: Serve from cache, update in background

### Cache Scope

Edit the `STATIC_ASSETS` array in `sw.js` to include files you want cached:

```javascript
const STATIC_ASSETS = [
    '/',
    '/about/',
    '/contact/',
    '/wp-content/themes/your-theme/style.css',
    '/wp-content/plugins/custom-pwa/assets/examples/offline-example.html'
];
```

## Integrating Real Web Push

The plugin currently includes stub implementation for push notifications. To send real push notifications, you need to:

### 1. Generate VAPID Keys

VAPID keys authenticate your server with push services.

```bash
# Using OpenSSL
openssl ecparam -name prime256v1 -genkey -noout -out vapid_private.pem
openssl ec -in vapid_private.pem -pubout -out vapid_public.pem

# Or use online generators or Node.js web-push library
```

### 2. Install Web Push Library

Add to your WordPress installation:

```bash
composer require minishlink/web-push
```

Or manually include the library in your plugin.

### 3. Update VAPID Keys

Edit `includes/class-subscriptions.php` and replace the placeholder `VAPID_PUBLIC_KEY` with your real public key.

Store the private key securely (not in code). Use WordPress options or environment variables:

```php
define('CUSTOM_PWA_VAPID_PRIVATE_KEY', 'your-private-key-here');
```

### 4. Implement Push Sending

In `includes/class-dispatcher.php`, replace the stub `send_notification()` method with real Web Push code:

```php
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription as WebPushSubscription;

private function send_notification( $subscription, $payload ) {
    $auth = [
        'VAPID' => [
            'subject' => get_bloginfo('url'),
            'publicKey' => Custom_PWA_Subscriptions::VAPID_PUBLIC_KEY,
            'privateKey' => CUSTOM_PWA_VAPID_PRIVATE_KEY,
        ]
    ];

    $webPush = new WebPush($auth);

    $pushSubscription = WebPushSubscription::create([
        'endpoint' => $subscription->endpoint,
        'keys' => [
            'p256dh' => $subscription->p256dh,
            'auth' => $subscription->auth,
        ],
    ]);

    $result = $webPush->sendOneNotification(
        $pushSubscription,
        json_encode($payload)
    );

    // Handle result and errors
    if (!$result->isSuccess()) {
        error_log('Push failed: ' . $result->getReason());
    }
}
```

## Troubleshooting

### Service Worker Not Registering
- Check that `sw.js` is in the site root
- Verify HTTPS is enabled (required for service workers)
- Check browser console for errors

### Push Notifications Not Working
- Ensure notification permission is granted
- Check that service worker is active
- Verify subscription was sent to server
- Check REST API endpoints are accessible

### Manifest Not Loading
- Verify PWA is enabled in Config
- Check manifest URL: `/manifest.webmanifest`
- Ensure rewrite rules are flushed (deactivate/reactivate plugin)

### Offline Page Not Showing
- Verify service worker is caching the offline page
- Check the fetch event handler in `sw.js`
- Test with DevTools → Network → Offline mode

## Browser Support

- **Chrome/Edge**: Full support
- **Firefox**: Full support
- **Safari**: Limited (no push on iOS, PWA on macOS only)
- **Opera**: Full support

## Security Considerations

1. Always use HTTPS in production
2. Keep VAPID private key secure
3. Validate and sanitize all user inputs
4. Regularly update dependencies
5. Monitor for failed push endpoints

## Further Resources

- [Web Push Protocol](https://developers.google.com/web/fundamentals/push-notifications)
- [Service Workers](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Web App Manifest](https://developer.mozilla.org/en-US/docs/Web/Manifest)
- [VAPID](https://blog.mozilla.org/services/2016/08/23/sending-vapid-identified-webpush-notifications-via-mozillas-push-service/)

## Support

For issues and feature requests, please contact the plugin developer or refer to the plugin documentation.

---

**Version:** 1.0.0  
**Last Updated:** December 2025
