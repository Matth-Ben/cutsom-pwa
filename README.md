# Custom PWA - Progressive Web App & Push Notifications Plugin

A comprehensive WordPress plugin for implementing Progressive Web App (PWA) features and Web Push notifications in your WordPress site.

## Features

### PWA Configuration
- 📱 Dynamic web app manifest generation
- 🎨 Customizable app name, colors, and icons
- 🖼️ Support for custom icons or WordPress Site Icon
- 📲 Multiple display modes (standalone, fullscreen, minimal-ui, browser)
- 🔧 Automatic meta tag injection for iOS/Android

### Web Push Notifications
- 🔔 Push notifications for any post type (including custom post types)
- 📝 Customizable notification templates with placeholders
- 🎯 Per-post-type notification rules
- 🧪 Test notification functionality
- 📊 Subscription management via REST API
- 🌐 Multi-platform support (Android, iOS, Mac, Windows)

### Developer-Friendly
- 🎣 Extensive filter and action hooks
- 📚 Clean, well-documented code
- 🔌 Modular architecture
- 🛠️ Example service worker and offline page included
- 🔒 Follows WordPress coding standards

## Requirements

### Server Requirements
- **PHP 8.0 or higher**
- **WordPress 6.0 or higher**
- **HTTPS** (required for service workers and push notifications)

### PHP Extensions (Standard - Usually Pre-installed)
- ✅ `openssl` - VAPID key generation (EC P-256)
- ✅ `curl` - Push notification delivery
- ✅ `json` - Payload encoding
- ✅ `mbstring` - Binary data handling

> **Note**: These extensions are included in standard PHP installations. No external tools (like mkcert) are needed for push notifications. See [PUSH-REQUIREMENTS.md](PUSH-REQUIREMENTS.md) for details.

## Installation

1. Download or clone this repository to `/wp-content/plugins/custom-pwa/`
2. Activate the plugin through the WordPress admin panel
3. **That's it!** The plugin automatically sets up everything you need:
   - ✅ Creates database table for subscriptions
   - ✅ Generates VAPID keys for Web Push
   - ✅ Initializes notification scenarios for all post types
   - ✅ Configures default settings
   - ✅ All features are disabled by default for security

### What happens on activation?

The plugin performs a **complete automatic setup**:

- **Database**: Creates `wp_custom_pwa_subscriptions` table
- **VAPID Keys**: Generates cryptographic key pair for Web Push
- **Scenarios**: Configures notification scenarios for all public post types
  - Blog/Articles: publication, major_update, featured
  - E-commerce: price_drop, back_in_stock, sold_out, etc.
  - Events: sales_open, cancelled, rescheduled, etc.
  - Generic: publication, major_update, status_change
- **Options**: Creates all WordPress options with safe defaults
- **Security**: Everything disabled by default, you activate what you need

📖 **Detailed installation guide**: See [INSTALLATION.md](INSTALLATION.md)

## Quick Start

### 1. Enable Features

Go to **Custom PWA → Config**:
- ✅ Enable PWA features
- ✅ Enable Web Push notifications
- Select your site type
- Choose post types for push notifications

### 2. Configure PWA

Go to **Custom PWA → PWA**:
- Set your app name and description
- Choose theme and background colors
- Upload or select an app icon
- Test manifest at: `https://yoursite.com/manifest.webmanifest`

### 3. Set Up Service Worker

Copy the example service worker to your site root:
```bash
cp wp-content/plugins/custom-pwa/assets/examples/sw-example.js sw.js
```

**Important:** The service worker must be at your site root for proper scope.

### 4. Configure Push Notifications

Go to **Custom PWA → Push**:
- Enable notifications for specific post types
- Customize title, body, and URL templates
- Use placeholders like `{post_title}`, `{excerpt}`, `{permalink}`
- Test with the built-in test tool

## Plugin Structure

```
custom-pwa/
├── custom-pwa.php              # Main plugin file
├── includes/
│   ├── class-admin-menu.php       # Admin menu registration
│   ├── class-config-settings.php  # Global configuration
│   ├── class-pwa-settings.php     # PWA settings & manifest
│   ├── class-push-settings.php    # Push notification rules
│   ├── class-subscriptions.php    # Subscription storage & REST API
│   └── class-dispatcher.php       # Notification dispatch logic
├── assets/
│   ├── js/
│   │   └── frontend-subscribe.js  # Browser subscription handler
│   └── examples/
│       ├── sw-example.js          # Example service worker
│       ├── offline-example.html   # Offline fallback page
│       └── README.md              # Setup instructions
├── readme_promt.md             # Original requirements
└── README.md                   # This file
```

## Configuration Options

### Global Config
- **Enable PWA features**: Turn PWA functionality on/off
- **Enable Web Push**: Turn push notifications on/off
- **Site type**: Generic, E-commerce, Events, Custom
- **Post types**: Select which post types trigger notifications
- **Debug mode**: Enable verbose logging

### PWA Settings
- **App name**: Full application name
- **Short name**: Name for home screen (limited space)
- **Description**: Brief app description
- **Start URL**: Launch URL (default: homepage)
- **Theme color**: Browser UI color
- **Background color**: Splash screen background
- **Display mode**: How the app appears when launched
- **Icon**: Custom icon or fallback to Site Icon

### Push Notification Templates
Available placeholders:
- `{post_title}` - Post title
- `{permalink}` - Full URL to post
- `{excerpt}` - Post excerpt
- `{post_type}` - Post type name
- `{event_date}` - Event date (for Events site type)
- `{venue}` - Event venue (for Events site type)
- `{status_label}` - Event status (for Events site type)

## REST API Endpoints

### Get VAPID Public Key
```
GET /wp-json/custom-pwa/v1/public-key
```

### Subscribe to Push Notifications
```
POST /wp-json/custom-pwa/v1/subscribe
{
  "endpoint": "https://...",
  "keys": {
    "p256dh": "...",
    "auth": "..."
  },
  "lang": "en",
  "platform": "android",
  "userAgent": "..."
}
```

### Unsubscribe from Push Notifications
```
POST /wp-json/custom-pwa/v1/unsubscribe
{
  "endpoint": "https://..."
}
```

### Send Test Notification (Admin Only)
```
POST /wp-json/custom-pwa/v1/test-push
{
  "title": "Test",
  "body": "This is a test",
  "url": "https://..."
}
```

## Developer Hooks

### Filters

#### `custom_pwa_config_options`
Modify global configuration options before saving.
```php
add_filter('custom_pwa_config_options', function($options, $input) {
    $options['custom_field'] = sanitize_text_field($input['custom_field']);
    return $options;
}, 10, 2);
```

#### `custom_pwa_manifest_data`
Modify the PWA manifest before output.
```php
add_filter('custom_pwa_manifest_data', function($manifest) {
    $manifest['orientation'] = 'portrait';
    return $manifest;
});
```

#### `custom_pwa_push_rules`
Modify push notification rules before saving.
```php
add_filter('custom_pwa_push_rules', function($rules, $input) {
    // Add custom validation or modification
    return $rules;
}, 10, 2);
```

#### `custom_pwa_notification_context`
Modify notification context before rendering templates.
```php
add_filter('custom_pwa_notification_context', function($context, $post) {
    $context['author_name'] = get_the_author_meta('display_name', $post->post_author);
    return $context;
}, 10, 2);
```

#### `custom_pwa_push_payload`
Modify push notification payload before sending.
```php
add_filter('custom_pwa_push_payload', function($payload, $subscription, $post) {
    $payload['badge'] = 'https://example.com/badge.png';
    return $payload;
}, 10, 3);
```

### Actions

#### `custom_pwa_init`
Fires after plugin initialization.
```php
add_action('custom_pwa_init', function($plugin) {
    // Custom initialization code
});
```

#### `custom_pwa_activated`
Fires when plugin is activated.
```php
add_action('custom_pwa_activated', function() {
    // Custom activation code
});
```

#### `custom_pwa_deactivated`
Fires when plugin is deactivated.
```php
add_action('custom_pwa_deactivated', function() {
    // Custom deactivation code
});
```

#### `custom_pwa_admin_menu_registered`
Fires after admin menu items are registered.
```php
add_action('custom_pwa_admin_menu_registered', function() {
    // Add custom menu items
});
```

#### `custom_pwa_head_tags_injected`
Fires after PWA head tags are injected.
```php
add_action('custom_pwa_head_tags_injected', function() {
    // Add custom meta tags
});
```

## Integrating Real Web Push

This plugin includes stub implementations for push notifications. To send real notifications:

### 1. Generate VAPID Keys
```bash
openssl ecparam -name prime256v1 -genkey -noout -out vapid_private.pem
openssl ec -in vapid_private.pem -pubout -out vapid_public.pem
```

### 2. Install Web Push Library
```bash
composer require minishlink/web-push
```

### 3. Update Implementation
See `assets/examples/README.md` for detailed integration instructions.

## Troubleshooting

### Service Worker Not Registering
- Ensure HTTPS is enabled
- Verify `sw.js` is in site root
- Check browser console for errors
- Clear browser cache

### Push Notifications Not Working
- Grant notification permission in browser
- Verify service worker is active
- Check REST API endpoints are accessible
- Enable debug mode in Config

### Manifest Not Loading
- Ensure PWA is enabled in Config
- Test URL: `/manifest.webmanifest`
- Flush rewrite rules (deactivate/reactivate plugin)

### Icons Not Showing
- Upload a square icon (minimum 512x512px)
- Or set a WordPress Site Icon
- Clear browser cache

## Browser Support

| Feature | Chrome/Edge | Firefox | Safari | Opera |
|---------|-------------|---------|--------|-------|
| PWA Install | ✅ | ✅ | ✅ (macOS) | ✅ |
| Web Push | ✅ | ✅ | ❌ (iOS) | ✅ |
| Service Workers | ✅ | ✅ | ✅ | ✅ |
| Offline Support | ✅ | ✅ | ✅ | ✅ |

## Security

- Always use HTTPS in production
- Keep VAPID private key secure (use environment variables)
- Validate and sanitize all inputs
- Regularly update dependencies
- Monitor for failed push endpoints

## Contributing

This plugin was created as a comprehensive solution for PWA and Web Push in WordPress. Feel free to extend it for your needs.

## License

GPL v2 or later

## Credits

Developed with ❤️ for the WordPress community.

---

**Version:** 1.0.5  
**Requires PHP:** 8.0+  
**Requires WordPress:** 6.0+  
**Tested up to:** 6.4  
**License:** GPLv2 or later
