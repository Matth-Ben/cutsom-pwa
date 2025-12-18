# Changelog

All notable changes to the Custom PWA plugin will be documented in this file.

## [1.0.4] - 2025-12-18

### Added
- **Custom Scenarios Management**: User-defined push notification scenarios
  - New "Manage Scenarios" tab in Custom PWA → Push admin page
  - Full CRUD interface for creating, editing, and deleting scenarios
  - Scope selection: Global (all post types) or specific post types
  - Trigger configuration: publication, major_update, status_change
  - Template fields: title, body, URL with placeholders support
  - Dynamic field management with Add/Remove buttons
  - Built-in scenarios (publication, major_update, status_change) remain available
  - Custom scenarios merge with built-in scenarios per post type
  - Storage in `custom_pwa_custom_scenarios` WordPress option
- **Tabbed Interface**: Push page now organized with tabs
  - "Post Type Configuration" tab: Configure notifications per post type
  - "Manage Scenarios" tab: Create and manage custom scenarios
  - Clean navigation between configuration and scenario management
- **Automatic Data Migration**: Old format to scenario-based format
  - Detects old data structure (pre-scenarios format)
  - Automatically converts to new format on first load
  - Preserves custom templates from old format in first scenario
  - Prevents "Impossible de charger" errors on upgrade

### Fixed
- **Page Load Error**: Fixed "Impossible de charger custom-pwa-push" fatal error
  - Changed URL parameter from `post_type=` to `cpt=` to avoid WordPress conflicts
  - WordPress reserves `post_type` parameter for its core post type management
  - Using custom `cpt` parameter prevents interference with WordPress admin system
  - Page now loads correctly when navigating between post types
- **Sidebar Navigation**: Fixed multiple active states in sidebar
  - Removed duplicate `active` class application
  - Ensures only one sidebar item is active at a time
  - Properly highlights selected post type on page reload
- **Accordion Toggle Bug**: Fixed double-click requirement for accordion
  - Removed duplicate jQuery event handlers
  - Accordion now opens/closes with single click
- **JavaScript Timing**: Fixed sidebar navigation not working
  - Moved JavaScript execution after HTML rendering
  - Sidebar items now properly respond to clicks
- **Post Type Rendering**: Fixed "Pages" not displaying content
  - Changed to render ALL enabled post types in loop
  - JavaScript now shows/hides appropriate content
  - All post types accessible via sidebar navigation
- **Missing Dependencies**: Added required `class-custom-scenarios.php` include
  - Added to `render_push_page()` method in class-admin-menu.php
  - Prevents class not found errors during migration

### Changed
- **Data Structure**: Enhanced rules structure to support scenarios
  - Old format: `array('post' => array('enabled', 'title', 'body', 'url'))`
  - New format: `array('post' => array('config' => array(...), 'scenarios' => array(...)))`
  - Backward compatible through automatic migration
- **URL Parameters**: Changed from `post_type=` to `cpt=` in admin URLs
  - Avoids conflicts with WordPress core parameter names
  - Example: `?page=custom-pwa-push&tab=post-types&cpt=post`

## [1.0.3] - 2025-12-18

### Added
- **VAPID Key Generation**: Automatic generation of VAPID keys on plugin activation
  - Uses OpenSSL to generate ES256 (P-256) key pairs
  - Keys stored securely in `custom_pwa_push` option
  - Public key is 65-byte raw EC point (uncompressed format)
  - Private key stored in PEM format for JWT signing
  - Helper method `generate_vapid_keys()` for key regeneration
- **SSL Helper Page**: New admin page for SSL certificate diagnostics
  - Location: Custom PWA → 🔒 SSL Helper
  - Detects SSL issues and provides installation guidance
  - Shows domain status, HTTPS status, mkcert installation status
  - Auto-displays admin notice with copy-to-clipboard installation command
  - Includes automated `install-mkcert.sh` script for one-click setup
  - Full diagnostic page with step-by-step instructions
- **Push Notification Sending**: Complete Web Push implementation
  - Real notification sending to subscribed devices
  - VAPID authentication with JWT (ES256 signature)
  - Support for Mozilla, Chrome, and other push services
  - Test notification button in admin: Custom PWA → Push
  - Shows sent/failed count after sending
  - Debug mode logging for troubleshooting
- **Separate Service Worker Registration**: New `sw-register.js` script
  - Decouples Service Worker registration from push notifications
  - Allows PWA installation even when push is disabled
  - Proper logging and error handling
  - Fixes Chrome installability requirements

### Fixed
- **PWA Installability**: Fixed Service Worker not registering for PWA-only mode
  - Service Worker now registers when PWA is enabled, regardless of push status
  - Separated SW registration logic from push notification logic
  - Chrome now properly detects the app as installable
  - Install prompt appears in Chrome menu (⋮ → "Install Labo...")
- **Script Loading**: Fixed `customPwaData` variable conflicts
  - Added `pwaEnabled` property to all `wp_localize_script` calls
  - Prevents later scripts from overwriting SW registration data
  - Consistent data structure across all frontend scripts
- **Manifest Icons**: Fixed icon size declaration errors
  - Icons now declare actual image dimensions (256x256)
  - Removed fake size declarations (192x192, 512x512)
  - Fixed "Actual size does not match specified size" Chrome warnings
  - Changed from `any maskable` to separate `any` and `maskable` entries
  - Chrome DevTools no longer shows icon errors
- **Service Worker Installation**: Fixed SW not activating due to cache errors
  - Removed pre-caching of non-existent files
  - Added immediate `skipWaiting()` and `clients.claim()`
  - Service Worker now activates instantly without timeout
- **Service Worker Timeout**: Fixed 5-second timeout when subscribing
  - Issue was caused by SW never reaching "ready" state
  - Removed problematic cache operations that blocked activation
  - Subscription now completes in under 1 second
- **VAPID Key Format**: Fixed "Invalid raw ECDSA P-256 public key" error
  - Changed from PEM/DER format to raw 65-byte EC point
  - Extracted X and Y coordinates properly (32 bytes each)
  - Added 0x04 prefix for uncompressed point format
  - Keys now compatible with Web Push specification
- **REST API Public Key Endpoint**: Fixed key retrieval
  - Changed response from `publicKey` (camelCase) to `public_key` (snake_case)
  - Removed hardcoded VAPID constant, now uses database values
  - Fixed "No VAPID key configured" error in frontend
- **Web Push Protocol**: Fixed HTTP 400/401 errors
  - Added required `Crypto-Key` header with VAPID public key
  - Fixed Authorization header format: `WebPush [jwt]` (not `vapid`)
  - Removed incorrect `Content-Type: application/json` header
  - Payload now sent empty (no encryption yet) for compatibility
  - Successfully receiving HTTP 201 responses from push services
- **PHP cURL Extension**: Added requirement and installation instructions
  - Plugin now requires `php-curl` extension
  - Error handling for missing cURL with helpful message
  - Installation command: `sudo apt-get install php8.3-curl`

### Changed
- **Service Worker Registration Architecture**: Separated concerns
  - Created dedicated `sw-register.js` for Service Worker registration
  - `frontend-subscribe.js` now assumes SW is already registered
  - Improved script dependencies: subscribe depends on SW registration
  - Better error handling and console logging
- **Service Worker Cache Strategy**: Simplified to no-cache approach
  - Removed `STATIC_ASSETS` array (was causing 404 errors)
  - Service Worker focuses on push notifications only
  - Cache features can be added later as needed
- **JWT Implementation**: Custom ES256 signature without external libraries
  - Implemented DER to raw signature conversion
  - Added base64url encoding/decoding helpers
  - No Composer dependencies required
  - Proper ECDSA P-256 signature handling
- **Notification Payload**: Currently sending empty payload
  - Full encryption (ECDH + AES-GCM) not yet implemented
  - Service Worker uses default notification data
  - For production, recommend using `web-push-php` library
- **SSL Helper Menu**: Fixed menu slug consistency
  - Changed from `custom_pwa` (underscore) to `custom-pwa` (hyphen)
  - Menu now appears correctly under Custom PWA parent

### Security
- VAPID private key stored securely in database (base64url encoded)
- JWT tokens expire after 12 hours
- Proper ECDSA signature validation
- No VAPID keys hardcoded in source code

### Developer Notes
- **mkcert Installation**: Automated script available at `install-mkcert.sh`
  - Installs mkcert binary, generates certificates, updates nginx
  - One-command setup: `sudo bash install-mkcert.sh labo.local`
- **Testing Push Notifications**: Use Custom PWA → Push → Send Test Notification
  - Shows real-time success/failure count
  - Debug logs available when Debug Mode enabled
  - Check browser console for Service Worker messages

## [1.0.2] - 2025-12-18

### Fixed
- **Critical**: Fixed REST API authentication error causing 403 loop in admin
  - Issue was caused by HTTPS local environment with self-signed certificate
  - Added cookie configuration in `wp-config.php` (COOKIE_DOMAIN, COOKIEPATH, etc.)
  - Resolved `/wp-json/wp/v2/users/me` 403 (Forbidden) error
  - Fixed infinite loop in block editor (Gutenberg) console
  - Improved REST API cookie authentication handling
- **Manifest 404 error**: Fixed manifest.webmanifest not being served
  - Requires running `wp rewrite flush` after plugin activation
  - Rewrite rules properly registered for `/manifest.webmanifest` endpoint
  - Added note in documentation about permalink flush requirement
- **Service Worker not registering**: Fixed push notifications not working
  - Service Worker was never registered automatically
  - Added `navigator.serviceWorker.register()` in frontend-subscribe.js
  - Service Worker now auto-registers on page load when push is enabled
  - Fixed "Service Worker timeout" error in notification popup

### Changed
- Removed REST API authentication filters from plugin (not needed, was wp-config issue)
- Cleaned up plugin code to focus on PWA functionality only
- Service Worker registration is now automatic (no manual setup required)

### Added
- Activation notice to confirm permalinks flush and manifest availability
- **Local Development Mode**: New configuration option for local environments
  - Auto-detects localhost, 127.0.0.1, *.local, *.test, *.dev domains
  - Enabled automatically on installation for local domains
  - Displays helpful console messages when SSL certificate errors occur
  - Guides developers to solutions (accept cert, use mkcert, etc.)
  - Available in: Custom PWA → Config → Local Development Mode
  - Warning displayed: "Only enable in local development, never in production"
- SSL Setup Documentation: Complete guide for local SSL configuration
  - Created `SSL-SETUP.md` with step-by-step instructions
  - Solutions: Accept certificate, mkcert, localhost HTTP, Chrome flags
  - Troubleshooting section for common SSL issues

## [1.0.1] - 2025-12-16

### Fixed
- **Critical**: Fixed manifest generation returning HTML instead of JSON
  - Changed hook from `template_redirect` to `parse_request` for earlier execution
  - Added output buffer cleaning to prevent HTML contamination
  - Added proper HTTP headers (`X-Robots-Tag: noindex`)
- Added missing `id` field in manifest (set to `/` for proper PWA identification)
- Added missing `scope` field in manifest (set to `/` for full site scope)
- Fixed icon sizes to use actual image dimensions instead of hardcoded sizes
- Added `purpose: "any maskable"` to icons for better compatibility
- Limited description to 300 characters to prevent truncation in manifest
- **Fixed notification popup not closing after accepting**
  - Added permanent localStorage flag after acceptance
  - Popup now never shows again after user accepts
  - Added verification check before displaying popup
  - Auto-close error messages after 3 seconds
  - Improved closing logic with better error handling
  - Added console logs for debugging
  - **Fixed**: Popup now closes even if service worker fails to load
  - **Fixed**: Added 5-second timeout for service worker readiness
  - **Fixed**: Better handling of permission states (granted/denied/default)
  - **Fixed**: Only mark as "dismissed for 30 days" when explicitly denied
  - Popup closes successfully even if VAPID key is not configured
  - Permission granted = popup closes, regardless of technical errors

### Added
- **Notification permission popup**: Modern popup automatically displayed on frontend
  - Beautiful, responsive design with dark mode support
  - Smart display logic (only shown once per 30 days if dismissed)
  - Checks notification permission status before showing
  - "Activate notifications" and "Later" buttons
  - Loading states and success/error messages
  - Auto-closes after successful subscription
  - Keyboard support (ESC to close)
- `display_override` support with `window-controls-overlay` for desktop PWAs
- Screenshots field in admin interface (mobile and desktop)
- Support for richer PWA install UI with screenshots
- Filter `custom_pwa_manifest_screenshots` for programmatic screenshot management
- CSS file for popup styling (`assets/css/notification-popup.css`)
- JavaScript file for popup logic (`assets/js/notification-popup.js`)

### Changed
- Improved manifest generation reliability
- Enhanced HTTP response handling for manifest endpoint
- Icons now use actual file dimensions detected via `getimagesize()`
- Screenshots support wide/narrow form factors for different devices
- Frontend scripts now load popup CSS and JS automatically when push is enabled

## [1.0.0] - 2025-12-16

### Initial Release

#### Added
- Complete PWA configuration system
  - Dynamic web app manifest generation
  - Customizable app name, colors, and icons
  - Support for multiple display modes
  - Automatic meta tag injection for iOS/Android
  - Fallback to WordPress Site Icon

- Web Push notification system
  - Custom database table for subscriptions
  - REST API endpoints for subscribe/unsubscribe
  - Per-post-type notification rules
  - Customizable notification templates
  - Template placeholder system
  - Multi-platform support (Android, iOS, Mac, Windows)
  - Test notification functionality

- Admin interface
  - Top-level "Custom PWA" menu with smartphone icon
  - Three organized submenus: PWA, Push, Config
  - WordPress Settings API integration
  - Media uploader for icon selection
  - Real-time test tools

- Developer features
  - Extensive filter and action hooks
  - Clean, modular architecture
  - Well-documented code
  - Example service worker
  - Example offline page
  - Comprehensive documentation

- Frontend functionality
  - Automatic push subscription handling
  - Platform detection
  - Browser support detection
  - Service worker integration

- Example files
  - sw-example.js - Complete service worker implementation
  - offline-example.html - Styled offline fallback page
  - Detailed setup instructions

#### Developer Hooks
- Filters: `custom_pwa_config_options`, `custom_pwa_manifest_data`, `custom_pwa_push_rules`, `custom_pwa_notification_context`, `custom_pwa_push_payload`, `custom_pwa_site_types`
- Actions: `custom_pwa_init`, `custom_pwa_activated`, `custom_pwa_deactivated`, `custom_pwa_admin_menu_registered`, `custom_pwa_head_tags_injected`, `custom_pwa_config_fields_registered`

#### Notes
- Requires PHP 8.0+ and WordPress 6.0+
- HTTPS required for service workers and push notifications
- Real Web Push sending requires integration of external library (stub implementation included)
- VAPID keys must be generated for production use

---

## Upgrade Instructions

### From Nothing to 1.0.0
1. Upload plugin to `/wp-content/plugins/custom-pwa/`
2. Activate plugin
3. Configure settings in Custom PWA menu
4. Copy service worker to site root
5. Test functionality

---

## Future Enhancements (Planned)

- [ ] Built-in VAPID key generator
- [ ] Notification scheduling
- [ ] User-specific notification preferences
- [ ] Analytics integration
- [ ] Push notification campaigns
- [ ] A/B testing for notifications
- [ ] Rich media support (images, actions)
- [ ] Notification history viewer
- [ ] Export/import settings
- [ ] Multi-language notification templates

---

## Support

For bug reports and feature requests, please refer to the plugin documentation or contact the developer.

## License

GPL v2 or later
