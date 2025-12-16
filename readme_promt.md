# Custom PWA Plugin - Original Prompt

Context:
I want to build a WordPress plugin (PHP 8+, WordPress 6+) named "custom-pwa" located in /wp-content/plugins/custom-pwa/. This plugin must handle BOTH:
1) PWA configuration (manifest, icons, head meta, example service worker + offline page).
2) Web Push notifications for different post types (including custom post types).

The plugin will be used in a classic WordPress setup (not headless only). Code must be clean, extensible, and follow WordPress coding standards.

Goal:
Generate a plugin skeleton inside /wp-content/plugins/custom-pwa/ that:
- Registers as a standard WordPress plugin (main file: custom-pwa.php).
- Adds a top-level admin menu "Custom PWA" in the WordPress sidebar, with three submenus:
  - "PWA"  → PWA configuration.
  - "Push" → Web Push notifications rules, templates, test tools.
  - "Config" → Global plugin configuration (CPT selection, site type, advanced options).
- Creates and maintains a custom DB table for Web Push subscriptions on activation.
- Exposes a small REST API to subscribe/unsubscribe devices to Web Push.
- Generates a dynamic manifest (manifest.webmanifest) based on admin settings, and injects it into the site <head> only when PWA is enabled.
- Ships example files for service worker and offline handling that the developer can copy to the site root.

Main features to implement:

1) Plugin structure
Create the following structure under /wp-content/plugins/custom-pwa/:
- custom-pwa.php (bootstrap)
- /includes/
  - class-admin-menu.php        (registers "Custom PWA" menu + "PWA" / "Push" / "Config" subpages)
  - class-pwa-settings.php      (PWA settings UI + manifest output logic)
  - class-push-settings.php     (Web Push settings UI: rules, templates, test tools)
  - class-config-settings.php   (global configuration UI)
  - class-subscriptions.php     (subscription storage + REST endpoints)
  - class-dispatcher.php        (build and send push notifications)
- /assets/js/frontend-subscribe.js        (handles push subscription in the browser)
- /assets/examples/sw-example.js          (example service worker file)
- /assets/examples/offline-example.html   (example offline page)
- /assets/examples/README.md              (explains how to copy/configure the examples)

Use a clear prefix/namespace like CUSTOM_PWA_ / Custom_PWA_ / custom_pwa_ for all classes, functions, and options.

2) Global plugin configuration ("Config" submenu)
- In class-config-settings.php, create a settings page "Config" under the "Custom PWA" menu.
- Use the WordPress Settings API and store options per site (not network-wide).
- Global options should include:
  - Checkbox: "Enable PWA features".
  - Checkbox: "Enable Web Push notifications".
  - "Site type" select: generic, ecommerce, events (concerts), custom.
  - Multiselect or list of checkboxes: "Post types to manage with Web Push" (all public post types including CPTs).
  - Optional: checkbox "Enable debug mode" (to control logging verbosity).
- Expose a filter like 'custom_pwa_config_options' so developers can add additional config fields.

3) PWA configuration ("PWA" submenu)
- In class-pwa-settings.php, create a "PWA" settings page under "Custom PWA".
- Use the Settings API, but only apply PWA settings if global "Enable PWA features" is checked.
- PWA options:
  - App name (name).
  - Short name (short_name).
  - Description.
  - Start URL (default to home_url('/')).
  - Theme color (hex).
  - Background color (hex).
  - Display mode (select: standalone, fullscreen, minimal-ui, browser).
  - Icon: media uploader field (stores attachment ID).
    - If empty, fallback to WordPress Site Icon via get_site_icon_url().
- Dynamic manifest:
  - Add a rewrite rule so /manifest.webmanifest routes to a custom handler.
  - Add a query var (e.g. custom_pwa_manifest).
  - In template_redirect, if this query var is present:
    - Send Content-Type: application/manifest+json.
    - Build the manifest array from PWA settings:
      - name, short_name, description, start_url, display, background_color, theme_color.
      - icons: at least 192x192 and 512x512 derived from chosen icon or Site Icon.
    - Apply a filter like 'custom_pwa_manifest_data' before encoding to JSON.
    - Echo the JSON and exit.
- Head injection:
  - Hook into wp_head.
  - If global "Enable PWA features" is ON:
    - Output <link rel="manifest" href=".../manifest.webmanifest">.
    - Output <meta name="theme-color" content="...">.
    - Output <link rel="apple-touch-icon" href="...">.
    - Optionally add:
      - <meta name="apple-mobile-web-app-capable" content="yes">
      - <meta name="apple-mobile-web-app-status-bar-style" content="default">
      - <meta name="apple-mobile-web-app-title" content="App name">

4) Web Push subscriptions storage & REST API
- In class-subscriptions.php:
  - On plugin activation, create a subscriptions table using dbDelta with fields:
    - id (bigint, primary key, autoincrement)
    - blog_id (for multisite)
    - endpoint (text)
    - p256dh (text)
    - auth (text)
    - lang (varchar)
    - platform (varchar: android, ios, mac, windows, other)
    - user_agent (varchar)
    - active (tinyint, default 1)
    - created_at, updated_at (datetime)
  - Store the DB version in an option and update the table on version changes.
  - Ensure multisite-awareness: use $wpdb->base_prefix and get_current_blog_id().
  - Register REST routes under namespace "custom-pwa/v1":
    - GET /public-key → returns a VAPID public key constant (placeholder).
    - POST /subscribe → creates/updates a subscription row.
    - POST /unsubscribe → marks a subscription as inactive.
  - Sanitize all inputs (sanitize_text_field, esc_url_raw, etc.) and validate the platform values.

5) Web Push rules & templates ("Push" submenu)
- In class-push-settings.php, create a "Push" settings page under "Custom PWA".
- Only show/handle options if global "Enable Web Push notifications" is ON.
- Load the list of post types enabled in the "Config" page.
- For each enabled post type:
  - Checkbox: "Enable notifications for this post type".
  - Text input: "Title template".
  - Textarea: "Body template".
  - Text input: "URL template".
- Templates must support simple placeholders such as:
  - {post_title}, {permalink}, {excerpt}, {post_type}.
- For "events" site type, prepare additional placeholders (for later integration):
  - {event_date}, {venue}, {status_label}.
- Store all push rules in a single option (array), and expose a filter like 'custom_pwa_push_rules' for extension.
- Additional useful features for the "Push" page:
  - A "Send test notification" button:
    - Calls a REST endpoint (e.g. /custom-pwa/v1/test-push) or an admin-ajax action.
    - Uses dummy payload to verify that subscriptions + service worker + showNotification work.
  - A simple "Log viewer" section (optional):
    - Reads last N entries from a plugin-specific log file or from an option, to help debugging push issues (e.g., failed endpoints).

6) Frontend subscription JS
- Implement assets/js/frontend-subscribe.js:
  - Detect support for serviceWorker and PushManager.
  - Wait for navigator.serviceWorker.ready (do NOT register here if registration is already handled elsewhere; instead, optionally allow a configurable SW path passed via localized script data).
  - Fetch the VAPID public key from GET /custom-pwa/v1/public-key.
  - Request Notification permission.
  - Subscribe with registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey }).
  - Build a payload with:
    - endpoint, keys (from subscription.toJSON().keys),
    - lang (from site language),
    - platform (detected from navigator.userAgent: android, ios, mac, windows, other),
    - userAgent (raw navigator.userAgent).
  - POST this data to /custom-pwa/v1/subscribe.
- Enqueue this script via wp_enqueue_scripts on the frontend.
- Use wp_localize_script to pass:
  - REST base URL,
  - site language,
  - expected service worker path (e.g. /sw.js),
  - flags like "pushEnabled".
- Add clear comments in JS explaining that:
  - The developer must copy sw-example.js to /sw.js (or adjust the path).
  - Subscription logic can be triggered automatically on page load or attached to a custom "Enable notifications" button.

7) Notification dispatch logic
- In class-dispatcher.php:
  - Hook into transition_post_status (or similar) to detect when a post is first published:
    - If old status was not "publish" and new status is "publish", and post_type is one of the enabled CPTs and enabled in "Push" rules, then prepare a notification.
  - Build a context array for the post:
    - post_title, permalink, excerpt, post_type.
    - Optionally event_date, venue, status_label if site type is "events" (these can be retrieved from post meta, even if initially dummy).
  - Implement a simple template renderer that:
    - Replaces placeholders like {post_title} with real values from the context.
  - For each active subscription row in the DB:
    - Optionally adapt payload by platform (android, ios, desktop), e.g. shorter body for ios.
    - Build a JSON payload with:
      - title, body, url, icon (icon can reuse the PWA icon URL).
  - For now, implement a stub "send" method that:
    - Does NOT send real Web Push.
    - Logs the endpoint + payload via error_log with a clear TODO comment explaining where to plug in a library like Minishlink/WebPush later.
- Optionally add a filter like 'custom_pwa_push_payload' so other code can adjust the payload before sending/logging.

8) Example service worker & offline page
- In /assets/examples/sw-example.js:
  - Implement minimal service worker logic:
    - 'install' and 'activate' events (with self.skipWaiting / clients.claim if appropriate).
    - 'push' event:
      - Parse event.data.json() (fallback to a default message if parsing fails).
      - Call registration.showNotification(title, options).
    - 'notificationclick' event:
      - Close the notification.
      - Focus an existing client window or open a new one with the URL from notification data.
- In /assets/examples/offline-example.html:
  - Provide a simple offline fallback page with a short explanation text.
- In /assets/examples/README.md:
  - Explain clearly:
    - Copy sw-example.js to the site root as /sw.js (or configure another path to match frontend-subscribe.js).
    - Optionally register an offline route and cache strategies.
    - Basic steps to test:
      - Enable PWA + Web Push in "Config".
      - Configure a basic template in "Push".
      - Subscribe from the frontend.
      - Publish a post and check browser notifications.

General requirements:
- Use modern PHP with reasonable type hints while respecting WordPress coding standards (escaping, sanitizing, nonces for forms, capabilities checks).
- Separate responsibilities strictly:
  - Admin menu wiring.
  - PWA settings + manifest.
  - Push config + rules.
  - Global config.
  - Subscriptions/REST.
  - Dispatch logic.
- Add English docblocks for all public methods and main classes, describing responsibilities and extension points (actions/filters).
- Do NOT write files outside the plugin folder at runtime: example files are only shipped as templates.

Please generate the initial file structure and stub implementations for all these components so I can activate the "custom-pwa" plugin in WordPress, see the "Custom PWA" menu with "PWA", "Push", and "Config" subpages, and then progressively replace stubs with real PWA and Web Push logic.

Create a readme_promt.md and copy paste this prompt
