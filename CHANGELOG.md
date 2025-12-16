# Changelog

All notable changes to the Custom PWA plugin will be documented in this file.

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
