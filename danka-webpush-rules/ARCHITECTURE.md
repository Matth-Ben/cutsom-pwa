# Plugin Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Danka WebPush Rules Plugin                        │
│                         (danka-webpush-rules)                        │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                          FRONTEND LAYER                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  User Browser                                                         │
│  ┌──────────────────────┐         ┌──────────────────────┐          │
│  │   Website Pages      │◄────────┤   Service Worker     │          │
│  │                      │         │      (sw.js)         │          │
│  │  ┌────────────────┐  │         └──────────────────────┘          │
│  │  │ Subscribe Btn  │  │                    │                      │
│  │  └────────────────┘  │                    │                      │
│  │         │             │         ┌──────────▼──────────┐          │
│  │         │             │         │ Push Notification   │          │
│  │         ▼             │         │    (Browser UI)     │          │
│  │  ┌────────────────┐  │         └─────────────────────┘          │
│  │  │ frontend.js    │◄─┤                                           │
│  │  │ - register SW  │  │                                           │
│  │  │ - request perm │  │                                           │
│  │  │ - subscribe    │  │                                           │
│  │  │ - unsubscribe  │  │                                           │
│  │  └────────────────┘  │                                           │
│  └───────┬──────────────┘                                           │
│          │                                                           │
└──────────┼───────────────────────────────────────────────────────────┘
           │
           │ HTTPS Request
           │
┌──────────▼───────────────────────────────────────────────────────────┐
│                          REST API LAYER                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  WordPress REST API                                                   │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  /wp-json/danka-webpush/v1/subscribe                        │   │
│  │  - Validate input                                            │   │
│  │  - Save subscription to DB                                   │   │
│  │  - Return success/error                                      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  /wp-json/danka-webpush/v1/unsubscribe                      │   │
│  │  - Validate input                                            │   │
│  │  - Delete subscription from DB                               │   │
│  │  - Return success/error                                      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
└───────────────────────────────┬───────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        DATABASE LAYER                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  wp_danka_webpush_subscriptions                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  Columns:                                                    │   │
│  │  - id (PK)                                                   │   │
│  │  - endpoint (text)          ← Main identifier                │   │
│  │  - public_key (varchar)     ← P256DH key                     │   │
│  │  - auth_token (varchar)     ← Auth secret                    │   │
│  │  - user_agent (text)        ← Client info                    │   │
│  │  - ip_address (varchar)     ← Client IP                      │   │
│  │  - user_id (bigint)         ← WP User (if logged in)         │   │
│  │  - created_at (datetime)                                     │   │
│  │  - updated_at (datetime)                                     │   │
│  │                                                               │   │
│  │  Indexes:                                                     │   │
│  │  - endpoint_index (endpoint)                                 │   │
│  │  - user_id_index (user_id)                                   │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                          ADMIN LAYER                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  WordPress Admin Panel                                                │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  WebPush Rules Menu                                          │   │
│  │  ┌──────────────────────────────────────────────────────┐   │   │
│  │  │  Site Type Selector                                  │   │   │
│  │  │  ○ Generic  ○ E-commerce  ○ Events                   │   │   │
│  │  └──────────────────────────────────────────────────────┘   │   │
│  │                                                               │   │
│  │  ┌──────────────────────────────────────────────────────┐   │   │
│  │  │  Post Type Configuration                             │   │   │
│  │  │  ☑ Posts                                             │   │   │
│  │  │    Title: {{post_title}}                             │   │   │
│  │  │    Body: {{post_excerpt}}                            │   │   │
│  │  │    URL: {{post_url}}                                 │   │   │
│  │  │                                                       │   │   │
│  │  │  ☑ Pages                                             │   │   │
│  │  │    Title: New page: {{post_title}}                   │   │   │
│  │  │    Body: {{post_excerpt}}                            │   │   │
│  │  │    URL: {{post_url}}                                 │   │   │
│  │  │                                                       │   │   │
│  │  │  ☑ Products (Custom Post Type)                       │   │   │
│  │  │    Title: {{post_title}} - {{product_price}}         │   │   │
│  │  │    Body: {{post_excerpt}}                            │   │   │
│  │  │    URL: {{post_url}}                                 │   │   │
│  │  └──────────────────────────────────────────────────────┘   │   │
│  │                                                               │   │
│  │  ┌──────────────────────────────────────────────────────┐   │   │
│  │  │  Extra Fields (Conditional)                          │   │   │
│  │  │  E-commerce:                                         │   │   │
│  │  │    Currency: USD                                     │   │   │
│  │  │    ☑ Show price in notifications                     │   │   │
│  │  │                                                       │   │   │
│  │  │  Events:                                             │   │   │
│  │  │    Date Format: F j, Y                               │   │   │
│  │  │    ☑ Show location in notifications                  │   │   │
│  │  └──────────────────────────────────────────────────────┘   │   │
│  │                                                               │   │
│  │  ┌──────────────────────────────────────────────────────┐   │   │
│  │  │  Statistics                                           │   │   │
│  │  │  Total active subscriptions: 1,234                   │   │   │
│  │  └──────────────────────────────────────────────────────┘   │   │
│  │                                                               │   │
│  │  [Save Settings]                                              │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  Settings stored in wp_options:                                      │
│  - danka_webpush_site_type                                           │
│  - danka_webpush_enabled_post_types                                  │
│  - danka_webpush_templates                                           │
│  - danka_webpush_extra_fields                                        │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      NOTIFICATION SENDING                            │
│                    (External Implementation)                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  Server-Side Script (PHP/Node.js)                                    │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  1. Trigger Event (e.g., new post published)                │   │
│  │  2. Get template for post type                              │   │
│  │  3. Replace placeholders with post data                     │   │
│  │  4. Query subscriptions from database                       │   │
│  │  5. Send notifications via Web Push Protocol                │   │
│  │  6. Handle errors and remove invalid subscriptions          │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                       │
│  Libraries:                                                           │
│  - PHP: minishlink/web-push                                          │
│  - Node.js: web-push                                                 │
│                                                                       │
│  VAPID Authentication:                                                │
│  - Public Key (shared with frontend)                                 │
│  - Private Key (server-side only)                                    │
│  - Subject (mailto:admin@site.com)                                   │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      DATA FLOW EXAMPLE                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  1. User Subscription Flow:                                          │
│     User clicks "Subscribe" → frontend.js requests permission        │
│     → Permission granted → Register service worker                   │
│     → Subscribe to push manager → Send subscription to REST API      │
│     → API saves to database → Success response                       │
│                                                                       │
│  2. Notification Sending Flow:                                       │
│     New post published → Hook triggered                              │
│     → Get post type template → Replace placeholders                  │
│     → Query subscribers → For each subscription:                     │
│       → Send push notification → Service worker receives             │
│       → Show notification → User clicks                              │
│       → Open URL → Track engagement                                  │
│                                                                       │
│  3. Unsubscribe Flow:                                                │
│     User clicks "Unsubscribe" → frontend.js calls API               │
│     → API deletes from database → Service worker unsubscribes        │
│     → Success response                                               │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      SECURITY LAYERS                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ✓ HTTPS Required (Web Push API standard)                           │
│  ✓ WordPress Nonces (CSRF protection)                                │
│  ✓ Input Sanitization (XSS prevention)                               │
│  ✓ Prepared SQL Statements (SQL injection prevention)                │
│  ✓ IP Validation (Anti-spoofing)                                     │
│  ✓ Permission Checks (Authorization)                                 │
│  ✓ VAPID Keys (Push service authentication)                          │
│  ✓ Origin Validation (Service worker scope)                          │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

## Key Integration Points

### 1. Frontend to Backend
- JavaScript API → REST endpoints
- Service worker → Push manager
- Browser → VAPID authentication

### 2. Backend to Database
- REST handlers → CRUD operations
- Admin page → Settings storage
- Validation → Security checks

### 3. Admin to Configuration
- UI interactions → Settings updates
- Template editor → Placeholder system
- Statistics display → Query subscriptions

### 4. External to Plugin
- Web Push libraries → Database queries
- Post publish hooks → Notification triggers
- Template engine → Data replacement
