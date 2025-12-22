# Custom PWA - Progressive Web App & Push Notifications Plugin

A comprehensive WordPress plugin for implementing Progressive Web App (PWA) features and Web Push notifications in your WordPress site.

## ✨ What's New in v1.0.5

- 🔑 **VAPID Key Management**: View and regenerate push notification keys directly in the admin
- 🔒 **Enhanced Security**: Visual indicators for sensitive information and confirmation dialogs
- 📊 **Key Status Display**: Real-time validation of your VAPID keys
- ⚡ **One-Click Regeneration**: Regenerate compromised keys with automatic subscription cleanup
- 📖 **Comprehensive Documentation**: New PUSH-REQUIREMENTS.md guide

## Features

### Automatic Setup (v1.0.4+)
- 🚀 **Zero-configuration installation**: Everything set up on plugin activation
- 📂 **Automatic file copying**: `sw.js` and `offline.html` installed to site root
- 📊 **Installation dashboard**: Visual status of all required files
- 📋 **Smart troubleshooting**: Detailed guides if automatic setup fails

### PWA Configuration
- 📱 Dynamic web app manifest generation
- 🎨 Customizable app name, colors, and icons
- 🖼️ Support for custom icons or WordPress Site Icon
- 📲 Multiple display modes (standalone, fullscreen, minimal-ui, browser)
- 🔧 Automatic meta tag injection for iOS/Android

### Web Push Notifications
- 🔔 Push notifications for any post type (including custom post types)
- � **Automatic VAPID key generation** (RFC 8292 compliant)
- 🎛️ **VAPID key management interface**: View, validate, and regenerate keys
- �📝 Customizable notification templates with placeholders
- 🎯 Per-post-type notification rules
- 🧪 Test notification functionality
- 📊 Subscription management via REST API
- 🌐 Multi-platform support (Android, Mac, Windows - iOS Web Push not supported by Apple)

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
   - ✅ Copies required files (`sw.js`, `offline.html`) to site root
   - ✅ Configures default settings
   - ✅ All features are disabled by default for security

### What happens on activation?

The plugin performs a **complete automatic setup**:

- **Database**: Creates `wp_custom_pwa_subscriptions` table
- **VAPID Keys**: Generates cryptographic EC P-256 key pair for Web Push (RFC 8292)
- **Files**: Automatically copies `sw.js` and `offline.html` to your site root (no FTP needed!)
- **Scenarios**: Configures notification scenarios for all public post types
  - Blog/Articles: publication, major_update, featured
  - E-commerce: price_drop, back_in_stock, sold_out, etc.
  - Events: sales_open, cancelled, rescheduled, etc.
  - Generic: publication, major_update, status_change
- **Options**: Creates all WordPress options with safe defaults
- **Security**: Everything disabled by default, you activate what you need

### Installation Page

After activation, go to **Custom PWA → Installation** to verify:
- ✅ File installation status (`sw.js`, `offline.html`)
- 📍 Exact file paths and URLs
- 📋 Manual installation instructions (if automatic copy failed)
- 🔧 Troubleshooting guide
- 🔗 Quick links to all configuration pages

📖 **Detailed installation guide**: See [INSTALLATION.md](INSTALLATION.md)

## Quick Start

### 1. Verify Installation

Go to **Custom PWA → Installation**:
- ✅ Check that `sw.js` and `offline.html` are properly installed
- 📍 Note the file locations and URLs
- 🔧 Follow troubleshooting steps if needed

### 2. Enable Features

Go to **Custom PWA → Config**:
- ✅ Enable PWA features
- ✅ Enable Web Push notifications
- 🔑 **VAPID Keys**: View and manage your push notification keys
  - Keys are automatically generated on activation
  - Regenerate if compromised or for testing
  - ⚠️ Warning: Regenerating keys invalidates all existing subscriptions
- Select your site type
- Choose post types for push notifications

### 3. Configure PWA

Go to **Custom PWA → PWA**:
- Set your app name and description
- Choose theme and background colors
- Upload or select an app icon
- Test manifest at: `https://yoursite.com/manifest.webmanifest`

### 4. Configure Push Notifications

Go to **Custom PWA → Push**:
- Enable notifications for specific post types
- Customize title, body, and URL templates
- Use placeholders like `{post_title}`, `{excerpt}`, `{permalink}`
- Test with the built-in test tool

## Plugin Structure

```
custom-pwa/
├── custom-pwa.php                 # Main plugin file
├── includes/
│   ├── class-admin-menu.php       # Admin menu registration
│   ├── class-config-settings.php  # Global configuration + VAPID management
│   ├── class-pwa-settings.php     # PWA settings & manifest
│   ├── class-push-settings.php    # Push notification rules
│   ├── class-subscriptions.php    # Subscription storage & REST API
│   ├── class-dispatcher.php       # Notification dispatch logic
│   └── class-installation-page.php # Installation status & troubleshooting
├── assets/
│   ├── js/
│   │   └── frontend-subscribe.js  # Browser subscription handler
│   └── examples/
│       ├── sw-example.js          # Example service worker (auto-copied)
│       ├── offline-example.html   # Offline fallback page (auto-copied)
│       └── README.md              # Setup instructions
├── test-complete-activation.php   # Complete activation test script
├── test-vapid-management.php      # VAPID functionality test script
├── CHANGELOG.md                   # Version history
├── INSTALLATION.md                # Detailed installation guide
├── PUSH-REQUIREMENTS.md           # Push notification requirements
├── SSL-SETUP.md                   # HTTPS setup guide
└── README.md                      # This file
```

## Configuration Options

### Global Config
- **Enable PWA features**: Turn PWA functionality on/off
- **Enable Web Push**: Turn push notifications on/off
- **VAPID Keys Management**: View and regenerate push notification keys
  - Display public and private keys with status indicator
  - Visual security indicators for sensitive information
  - One-click regeneration with confirmation dialog
  - Automatic subscription clearing on regeneration
  - Technical information about P-256 algorithm (RFC 8292)
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

This plugin includes a complete implementation for push notifications using VAPID keys (RFC 8292).

### VAPID Keys are Automatically Generated

✅ **No manual setup required!** The plugin:
- Generates EC P-256 VAPID keys on activation
- Stores them securely in WordPress options
- Provides management interface in Config page
- Uses standard PHP OpenSSL extension (no external tools needed)

### Managing VAPID Keys

Go to **Custom PWA → Config → VAPID Keys Management**:
- **View keys**: See your public and private VAPID keys
- **Status check**: Visual indicator showing if keys are valid
- **Regenerate**: One-click regeneration if keys are compromised
- **Security**: Confirmation dialog and automatic subscription cleanup

⚠️ **Important**: Regenerating VAPID keys will invalidate all existing push subscriptions. Users will need to resubscribe.

### When to Regenerate Keys

- 🔒 Keys have been compromised or exposed
- 🧪 Testing different notification configurations
- 🔄 Migrating to a new environment
- 🆕 Starting fresh with subscriptions

### Web Push Library (Already Integrated)

The plugin uses the **minishlink/web-push** library for sending notifications:

```bash
# Library is included in the plugin
composer require minishlink/web-push
```

### Technical Details

For detailed information about push notification requirements, see:
- 📖 [PUSH-REQUIREMENTS.md](PUSH-REQUIREMENTS.md) - Complete guide about VAPID, PHP extensions, and verification
- 📖 [INSTALLATION.md](INSTALLATION.md) - Step-by-step installation and testing
- 📖 [assets/examples/README.md](assets/examples/README.md) - Frontend integration examples

## Troubleshooting

### Installation Issues

Go to **Custom PWA → Installation** for:
- Real-time status of required files
- Automatic installation results
- Manual installation instructions (FTP/SSH)
- File permissions troubleshooting
- Complete troubleshooting guide

### Service Worker Not Registering
- Ensure HTTPS is enabled (required for service workers)
- Verify `sw.js` is in site root (check Installation page)
- Check browser console for errors
- Clear browser cache and reload
- Verify file permissions (should be 644)

### Push Notifications Not Working
- Go to **Config → VAPID Keys Management** to verify keys exist
- Grant notification permission in browser
- Verify service worker is active (DevTools → Application)
- Check REST API endpoints are accessible:
  - `/wp-json/custom-pwa/v1/public-key`
  - `/wp-json/custom-pwa/v1/subscribe`
- Enable debug mode in Config for detailed logs
- Check browser compatibility (iOS Safari doesn't support Web Push)

### Manifest Not Loading
- Ensure PWA is enabled in Config
- Test URL: `/manifest.webmanifest`
- Check for 404 errors in browser console
- Flush rewrite rules (deactivate/reactivate plugin)
- Clear browser cache

### Icons Not Showing
- Upload a square icon (minimum 512x512px recommended)
- Or set a WordPress Site Icon (Appearance → Customize)
- Clear browser cache
- Verify icon file is accessible

### VAPID Keys Issues
- **Keys not generated**: Deactivate and reactivate the plugin
- **Keys invalid**: Use the Regenerate button in Config page
- **OpenSSL missing**: Check PHP extensions (see [PUSH-REQUIREMENTS.md](PUSH-REQUIREMENTS.md))
- **Subscriptions not working**: Regenerate keys and ask users to resubscribe

### File Permissions
If automatic file installation fails:
```bash
# Check current permissions
ls -la sw.js offline.html

# Fix permissions
chmod 644 sw.js offline.html
chown www-data:www-data sw.js offline.html  # Adjust user/group as needed
```

## Browser Support

| Feature | Chrome/Edge | Firefox | Safari | Opera |
|---------|-------------|---------|--------|-------|
| PWA Install | ✅ | ✅ | ✅ (macOS) | ✅ |
| Web Push | ✅ | ✅ | ❌ (iOS) | ✅ |
| Service Workers | ✅ | ✅ | ✅ | ✅ |
| Offline Support | ✅ | ✅ | ✅ | ✅ |

## Security

- ✅ Always use HTTPS in production (required for PWA and Web Push)
- 🔑 VAPID keys are stored securely in WordPress options
- 🔒 Private keys are truncated in admin display for security
- ✅ All admin actions protected with nonces and capability checks
- ✅ Validate and sanitize all inputs
- 🔄 Regenerate VAPID keys if compromised
- 📊 Monitor for failed push endpoints and clean up invalid subscriptions
- 🛡️ All features disabled by default - activate only what you need

### Best Practices

1. **Protect your VAPID private key**: Never expose it in client-side code or public repositories
2. **Use strong capability checks**: Only users with `manage_options` can access sensitive features
3. **Monitor subscriptions**: Regularly clean up invalid or expired subscriptions
4. **Enable debug mode carefully**: Only in development environments (logs may contain sensitive data)
5. **Test before production**: Use the test notification feature to verify setup
6. **Keep WordPress updated**: Ensure compatibility and security patches

## Contributing

This plugin provides a complete, production-ready solution for PWA and Web Push in WordPress:

- ✅ **Automatic setup**: No manual configuration required
- ✅ **VAPID keys**: Generated and managed automatically
- ✅ **File installation**: Service worker and offline page auto-copied
- ✅ **Admin interface**: Complete management UI for all features
- ✅ **Security-first**: All features protected and disabled by default
- ✅ **Well-documented**: Extensive documentation and examples
- ✅ **Extensible**: Hooks and filters for customization
- ✅ **Test suite**: Validation scripts for activation and features

Feel free to extend it for your specific needs or contribute improvements!

## Documentation

- 📖 [INSTALLATION.md](INSTALLATION.md) - Complete installation guide with testing
- 📖 [PUSH-REQUIREMENTS.md](PUSH-REQUIREMENTS.md) - Push notification requirements and verification
- 📖 [SSL-SETUP.md](SSL-SETUP.md) - HTTPS setup for local development
- 📖 [CHANGELOG.md](CHANGELOG.md) - Version history and changes
- 📖 [assets/examples/README.md](assets/examples/README.md) - Frontend integration examples

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
