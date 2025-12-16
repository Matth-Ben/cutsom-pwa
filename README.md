# cutsom-pwa

Plugin WordPress de notifications Web Push configurables : choisissez les CPT à notifier, définissez des templates de titres/textes/URL par type de contenu, adaptez les payloads selon le type de site (e-commerce, spectacle, etc.) et la plateforme (mobile/desktop) pour envoyer des alertes ciblées et automatisées.

## Danka WebPush Rules Plugin

This repository contains **danka-webpush-rules**, a complete WordPress plugin for managing Web Push notifications.

### Features

- ✅ **Custom Database Table** - Stores push notification subscriptions
- ✅ **REST API Endpoints** - Subscribe/Unsubscribe endpoints
- ✅ **Admin Interface** - Configure notifications per post type
- ✅ **Template System** - Customizable title/body/URL templates with placeholders
- ✅ **Site Type Support** - Generic, E-commerce, and Events with conditional fields
- ✅ **Service Worker** - Ready-to-use service worker for push notifications
- ✅ **Frontend JavaScript** - Automatic subscription management
- ✅ **PHP 8+ & WordPress 6+** - Modern, secure code

### Quick Start

1. **Install**: Copy `danka-webpush-rules` folder to `/wp-content/plugins/`
2. **Activate**: Enable the plugin in WordPress admin
3. **Configure**: Go to WebPush Rules menu and configure settings
4. **Generate Keys**: Run `npx web-push generate-vapid-keys`
5. **Deploy**: Add subscribe button to your theme

### Documentation

- 📖 [Plugin README](danka-webpush-rules/README.md) - Complete feature documentation
- 📦 [Installation Guide](danka-webpush-rules/INSTALLATION.md) - Step-by-step setup
- 💻 [Integration Examples](danka-webpush-rules/example-integration.php) - Code examples
- 🎮 [Demo](danka-webpush-rules/demo.html) - Frontend demo page

### Requirements

- PHP 8.0 or higher
- WordPress 6.0 or higher
- HTTPS enabled (required for Web Push API)

### License

GPL v2 or later
