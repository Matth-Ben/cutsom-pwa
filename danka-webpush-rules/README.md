# Danka WebPush Rules

A WordPress plugin for managing Web Push notifications with customizable templates per post type and site-specific configurations.

## Requirements

- PHP 8.0 or higher
- WordPress 6.0 or higher
- HTTPS enabled (required for Web Push API)

## Features

### Core Features
- **Custom Database Table**: Stores push notification subscriptions with endpoint, keys, and metadata
- **REST API Endpoints**: 
  - `POST /wp-json/danka-webpush/v1/subscribe` - Subscribe to notifications
  - `POST /wp-json/danka-webpush/v1/unsubscribe` - Unsubscribe from notifications
- **Admin Interface**: Manage notification settings from WordPress admin panel
- **Service Worker**: Sample service worker for handling push notifications
- **Frontend JavaScript**: Auto-registration and subscription management

### Post Type Configuration
- Enable/disable notifications for any post type (including Custom Post Types)
- Customize notification templates per post type:
  - **Title Template**: Define notification title with placeholders
  - **Body Template**: Define notification body text
  - **URL Template**: Define destination URL when notification is clicked

### Available Placeholders
- `{{post_title}}` - Post title
- `{{post_author}}` - Post author name
- `{{post_excerpt}}` - Post excerpt
- `{{post_content}}` - Post content
- `{{post_date}}` - Post date
- `{{post_url}}` - Post permalink
- `{{site_name}}` - Site name
- `{{home_url}}` - Home URL

### Site Type Support
Choose from three site types with specific features:

#### Generic Site
- Basic notification configuration
- Standard placeholders

#### E-commerce Site
- Currency selection
- Option to show prices in notifications
- Additional placeholders for product data

#### Events Site
- Custom date format configuration
- Option to show event location
- Additional placeholders for event data

## Installation

1. Upload the `danka-webpush-rules` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'WebPush Rules' in the admin menu to configure settings

## Configuration

### Basic Setup
1. Navigate to **WebPush Rules** in WordPress admin
2. Select your **Site Type** (Generic, E-commerce, or Events)
3. Enable notifications for desired post types
4. Configure title, body, and URL templates for each post type
5. Save settings

### Frontend Integration

The plugin automatically enqueues the frontend JavaScript. To use it:

#### Automatic Initialization (Default - Disabled)
Uncomment the last line in `assets/js/frontend.js` to auto-initialize:
```javascript
initPushNotifications();
```

#### Manual Control
Use the exposed API methods:

```javascript
// Initialize push notifications
window.dankaWebPushAPI.init();

// Subscribe to notifications
window.dankaWebPushAPI.subscribe();

// Unsubscribe from notifications
window.dankaWebPushAPI.unsubscribe();

// Check current subscription
window.dankaWebPushAPI.checkSubscription().then(subscription => {
    console.log(subscription);
});
```

### VAPID Keys
For production use, generate your own VAPID keys:

```bash
npx web-push generate-vapid-keys
```

Update the key in `assets/js/frontend.js`:
```javascript
function getApplicationServerKey() {
    return 'YOUR_PUBLIC_VAPID_KEY';
}
```

## Database Schema

The plugin creates a custom table `wp_danka_webpush_subscriptions`:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| endpoint | text | Push subscription endpoint |
| public_key | varchar(255) | P256DH public key |
| auth_token | varchar(255) | Auth secret |
| user_agent | text | Client user agent |
| ip_address | varchar(45) | Client IP address |
| user_id | bigint(20) | WordPress user ID (if logged in) |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update timestamp |

## REST API Endpoints

### Subscribe
**Endpoint**: `POST /wp-json/danka-webpush/v1/subscribe`

**Request Body**:
```json
{
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "keys": {
        "p256dh": "base64-encoded-key",
        "auth": "base64-encoded-auth"
    }
}
```

**Response**:
```json
{
    "success": true,
    "message": "Subscription created successfully",
    "id": 123
}
```

### Unsubscribe
**Endpoint**: `POST /wp-json/danka-webpush/v1/unsubscribe`

**Request Body**:
```json
{
    "endpoint": "https://fcm.googleapis.com/fcm/send/..."
}
```

**Response**:
```json
{
    "success": true,
    "message": "Subscription removed successfully"
}
```

## Sending Notifications

This plugin handles subscription management. To actually send notifications, you'll need to:

1. Use a server-side library like `web-push` (Node.js) or `minishlink/web-push` (PHP)
2. Retrieve subscriptions from the database
3. Send push notifications using the Web Push protocol

Example using PHP `web-push` library:
```php
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Get subscriptions from database
global $wpdb;
$subscriptions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}danka_webpush_subscriptions");

// Configure Web Push
$auth = [
    'VAPID' => [
        'subject' => 'mailto:your-email@example.com',
        'publicKey' => 'your-public-key',
        'privateKey' => 'your-private-key',
    ],
];

$webPush = new WebPush($auth);

// Send notifications
foreach ($subscriptions as $sub) {
    $subscription = Subscription::create([
        'endpoint' => $sub->endpoint,
        'keys' => [
            'p256dh' => $sub->public_key,
            'auth' => $sub->auth_token,
        ],
    ]);
    
    $payload = json_encode([
        'title' => 'Notification Title',
        'body' => 'Notification body text',
        'url' => 'https://yoursite.com/post-url',
    ]);
    
    $webPush->sendOneNotification($subscription, $payload);
}
```

## Security

- All REST endpoints use WordPress nonce verification for authenticated requests
- Subscriptions are validated before storage
- SQL queries use prepared statements to prevent injection
- User input is sanitized and escaped
- IP addresses are stored for audit purposes

## Uninstallation

When the plugin is deleted (not just deactivated):
- Custom database table is removed
- All plugin options are deleted
- Transients are cleaned up

## Troubleshooting

### Notifications not appearing
1. Ensure HTTPS is enabled (required for Web Push)
2. Check browser console for errors
3. Verify notification permissions are granted
4. Check service worker registration

### Subscription not saving
1. Check REST API endpoint accessibility
2. Verify nonce is being sent correctly
3. Check database table exists
4. Review server error logs

## Support

For issues, questions, or contributions, please visit:
https://github.com/Matth-Ben/custom-pwa

## License

GPL v2 or later
