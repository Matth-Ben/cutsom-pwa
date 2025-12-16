# Installation Guide - Danka WebPush Rules

## Prerequisites

Before installing the plugin, ensure your environment meets these requirements:

1. **PHP 8.0 or higher**
2. **WordPress 6.0 or higher**
3. **HTTPS enabled** (required for Web Push API)
4. **Modern browser** support for Push API

## Installation Steps

### Step 1: Install the Plugin

#### Option A: Manual Installation
1. Download the `danka-webpush-rules` folder
2. Upload it to `/wp-content/plugins/` directory
3. Go to WordPress Admin → Plugins
4. Find "Danka WebPush Rules" and click "Activate"

#### Option B: ZIP Installation
1. Compress the `danka-webpush-rules` folder to a ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"

### Step 2: Initial Configuration

After activation, the plugin will:
- ✅ Create a custom database table `wp_danka_webpush_subscriptions`
- ✅ Register REST API endpoints
- ✅ Add admin menu item "WebPush Rules"

### Step 3: Configure Settings

1. Navigate to **WebPush Rules** in WordPress admin menu
2. Choose your **Site Type**:
   - **Generic**: Basic site (blog, news, etc.)
   - **E-commerce**: Online store
   - **Events**: Event management site

3. Enable notifications for desired post types:
   - Check the boxes for post types you want to notify about
   - Each enabled post type will expand to show template options

4. Configure templates for each post type:
   - **Title Template**: e.g., `New {{post_title}} available!`
   - **Body Template**: e.g., `{{post_excerpt}}`
   - **URL Template**: e.g., `{{post_url}}`

5. Configure extra fields (if applicable):
   - **E-commerce**: Set currency, enable price display
   - **Events**: Set date format, enable location display

6. Click **Save Settings**

### Step 4: Generate VAPID Keys (Important!)

For production use, you **must** generate your own VAPID keys:

```bash
# Install web-push CLI tool
npm install -g web-push

# Generate keys
web-push generate-vapid-keys
```

This will output:
```
Public Key: BEl62iUYgUivxIkv69yViEuiB...
Private Key: bdSiNzUhUP6piAxLHCVbxzRsr...
```

### Step 5: Update Frontend JavaScript

Edit `/danka-webpush-rules/assets/js/frontend.js`:

```javascript
function getApplicationServerKey() {
    return 'YOUR_PUBLIC_VAPID_KEY_HERE';
}
```

Replace with your actual public VAPID key.

### Step 6: Add Frontend Subscribe Button

Add a subscribe button to your theme or page:

```html
<button onclick="window.dankaWebPushAPI.subscribe()">
    🔔 Enable Notifications
</button>
```

Or in PHP:

```php
<button onclick="window.dankaWebPushAPI.subscribe()">
    <?php _e('Enable Notifications', 'your-theme'); ?>
</button>
```

## Verification

### Check Plugin Status

1. Go to **WebPush Rules** in admin
2. You should see "Total active subscriptions: 0" at the bottom
3. Try subscribing from frontend to test

### Test REST Endpoints

Open browser console and run:

```javascript
// Check if API is available
console.log(window.dankaWebPushAPI);

// Check subscription status
window.dankaWebPushAPI.checkSubscription().then(sub => {
    console.log('Subscription:', sub);
});
```

### Verify Database Table

In phpMyAdmin or database tool:

```sql
SELECT * FROM wp_danka_webpush_subscriptions;
```

## Sending Push Notifications

The plugin handles subscription management only. To send notifications:

### Option 1: Using PHP (Server-side)

Install the web-push library:

```bash
composer require minishlink/web-push
```

Example code:

```php
<?php
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// VAPID configuration
$auth = [
    'VAPID' => [
        'subject' => 'mailto:your-email@example.com',
        'publicKey' => 'YOUR_PUBLIC_KEY',
        'privateKey' => 'YOUR_PRIVATE_KEY',
    ],
];

$webPush = new WebPush($auth);

// Get subscriptions
global $wpdb;
$table_name = $wpdb->prefix . 'danka_webpush_subscriptions';
$subscriptions = $wpdb->get_results("SELECT * FROM $table_name");

// Send to each subscriber
foreach ($subscriptions as $sub) {
    $subscription = Subscription::create([
        'endpoint' => $sub->endpoint,
        'keys' => [
            'p256dh' => $sub->public_key,
            'auth' => $sub->auth_token,
        ],
    ]);
    
    $payload = json_encode([
        'title' => 'New Post Published!',
        'body' => 'Check out our latest article',
        'url' => get_permalink(123), // Post ID
        'icon' => get_site_icon_url(),
    ]);
    
    $webPush->queueNotification($subscription, $payload);
}

// Send all notifications
$results = $webPush->flush();

// Handle results
foreach ($results as $result) {
    if (!$result->isSuccess()) {
        // Remove invalid subscriptions
        $endpoint = $result->getEndpoint();
        $wpdb->delete($table_name, ['endpoint' => $endpoint]);
    }
}
```

### Option 2: Using Node.js

Install the web-push library:

```bash
npm install web-push
```

Example code:

```javascript
const webpush = require('web-push');
const mysql = require('mysql2/promise');

// Set VAPID details
webpush.setVapidDetails(
    'mailto:your-email@example.com',
    'YOUR_PUBLIC_KEY',
    'YOUR_PRIVATE_KEY'
);

async function sendNotifications() {
    // Connect to database
    const connection = await mysql.createConnection({
        host: 'localhost',
        user: 'your_user',
        password: 'your_password',
        database: 'your_database'
    });
    
    // Get subscriptions
    const [subscriptions] = await connection.execute(
        'SELECT * FROM wp_danka_webpush_subscriptions'
    );
    
    // Prepare notification
    const payload = JSON.stringify({
        title: 'New Post Published!',
        body: 'Check out our latest article',
        url: 'https://yoursite.com/post-url',
        icon: 'https://yoursite.com/icon.png'
    });
    
    // Send to each subscriber
    for (const sub of subscriptions) {
        const subscription = {
            endpoint: sub.endpoint,
            keys: {
                p256dh: sub.public_key,
                auth: sub.auth_token
            }
        };
        
        try {
            await webpush.sendNotification(subscription, payload);
            console.log('Sent to', sub.id);
        } catch (error) {
            console.error('Error:', error);
            // Remove invalid subscription
            if (error.statusCode === 410) {
                await connection.execute(
                    'DELETE FROM wp_danka_webpush_subscriptions WHERE id = ?',
                    [sub.id]
                );
            }
        }
    }
    
    await connection.end();
}

sendNotifications();
```

## Troubleshooting

### Issue: "Service Worker registration failed"

**Solution:**
- Ensure site is served over HTTPS
- Check browser console for specific error
- Verify service worker file is accessible at `/wp-content/plugins/danka-webpush-rules/sw.js`

### Issue: "Notification permission denied"

**Solution:**
- User must grant permission manually
- Check browser settings: Site Settings → Notifications
- Try in incognito mode to reset permissions

### Issue: "Subscriptions not saving"

**Solution:**
- Check REST API is accessible: Visit `/wp-json/danka-webpush/v1`
- Verify database table exists
- Check PHP error logs
- Ensure user has proper permissions

### Issue: "Admin page not showing"

**Solution:**
- Verify user has `manage_options` capability
- Clear WordPress cache
- Deactivate and reactivate plugin

## Security Considerations

1. **Always use HTTPS** - Required for Web Push API
2. **Keep VAPID keys secure** - Never commit to version control
3. **Validate subscriptions** - Plugin validates all inputs
4. **Monitor subscriptions** - Remove inactive/invalid ones regularly
5. **Rate limiting** - Implement rate limiting for REST endpoints

## Next Steps

1. ✅ Install and activate plugin
2. ✅ Configure post types and templates
3. ✅ Generate VAPID keys
4. ✅ Update frontend JavaScript with keys
5. ✅ Add subscribe button to theme
6. ⬜ Implement sending logic (server-side)
7. ⬜ Test with real notifications
8. ⬜ Monitor and optimize

## Support

For issues or questions:
- Check the README.md file
- Review the demo.html file
- Visit: https://github.com/Matth-Ben/custom-pwa

## Uninstallation

To completely remove the plugin:

1. Deactivate the plugin
2. Click "Delete" in plugins list
3. This will automatically:
   - Drop the subscriptions table
   - Delete all plugin options
   - Clean up transients

**Note:** Deactivation alone does NOT remove data. You must delete the plugin.
