# Installation and Activation

## 🚀 Automatic Plugin Activation

When you activate Custom PWA for the first time, the plugin **automatically performs all necessary installation steps without any intervention**.

### ✅ What Happens Automatically:

#### 1. **Essential Files Installation (v1.0.4+)**

The plugin automatically copies required files to your site root:

- ✅ `sw.js` - Service Worker (from `assets/examples/sw-example.js`)
- ✅ `offline.html` - Offline page (from `assets/examples/offline-example.html`)

**These files MUST be at the root** for PWA to work correctly. The plugin does this automatically for you!

**Smart Installation**:
- Files are only copied if they don't already exist (no overwrite)
- Automatic chmod 644 for proper permissions
- Error tracking if copy fails
- Status saved in `custom_pwa_file_copy_status` option

#### 2. **Database Table Creation**

```sql
CREATE TABLE wp_custom_pwa_subscriptions (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    endpoint text NOT NULL,
    user_public_key varchar(255) NOT NULL,
    user_auth_secret varchar(255) NOT NULL,
    user_agent text,
    ip_address varchar(45),
    subscribed_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY endpoint (endpoint(191))
)
```

This table stores user push notification subscriptions.

#### 3. **WordPress Options Initialization**

The following options are created in `wp_options`:

| Option | Description | Default Value |
|--------|-------------|---------------|
| `custom_pwa_config` | General configuration | PWA and Push disabled, debug mode off |
| `custom_pwa_settings` | PWA settings | Site name, colors, icons |
| `custom_pwa_push_rules` | Notification rules | Scenarios for all public post types |
| `custom_pwa_custom_scenarios` | Custom scenarios | Empty array `[]` |
| `custom_pwa_push` | VAPID keys | Automatically generated for Web Push |
| `custom_pwa_file_copy_status` | Copy status | Files copied, errors, timestamp |

#### 4. **VAPID Keys Generation (v1.0.0+)**

The plugin automatically generates a cryptographic key pair (VAPID) required for Web Push notifications:

- **Public key** (shared with browsers)
- **Private key** (kept secret on server)

These keys use the P-256 elliptic curve (prime256v1) for maximum security.

**Key Management (v1.0.5+)**:
- View keys in **Config → VAPID Keys Management**
- Visual status indicator (✅/❌)
- One-click regeneration with confirmation dialog
- Automatic subscription cleanup on regeneration

See [PUSH-REQUIREMENTS.md](PUSH-REQUIREMENTS.md) for more details.

#### 5. **Scenario Configuration by Post Type**

The plugin detects all **public post types** on your site and automatically creates appropriate scenarios.

##### Intelligent Detection Examples:

**Post type `post` (Blog/Articles)**:
- ✅ Publication (new post published)
- ✅ Major Update (post significantly updated)
- ✅ Featured (post featured)

**Post type `product` (E-commerce)**:
- ✅ Publication (new product)
- ✅ Price Drop (price decreased)
- ✅ Back in Stock (product restocked)
- ✅ Out of Stock (product unavailable)
- ✅ Low Stock (low inventory)
- ✅ End of Life (product discontinued)

**Post type `event` (Events)**:
- ✅ Publication (new event)
- ✅ Sales Open (tickets available)
- ✅ Last Tickets (few tickets remaining)
- ✅ Sold Out (no tickets available)
- ✅ Cancelled (event cancelled)
- ✅ Rescheduled (event rescheduled)

**Other Post Types (Generic)**:
- ✅ Publication
- ✅ Major Update
- ✅ Status Change

##### Automatic Mapping:

The plugin automatically detects post type roles:

```php
'post' → 'blog'
'product' → 'ecommerce'
'event', 'tribe_events' → 'events'
// Patterns in name:
*event* → 'events'
*product*, *shop* → 'ecommerce'
*post*, *article* → 'blog'
// Default:
* → 'generic'
```

#### 6. **Security by Default**

For your security, **everything is disabled by default**:

- ❌ PWA disabled
- ❌ Push disabled
- ❌ All post types disabled
- ❌ All scenarios disabled

You must **explicitly enable** what you want to use.

---

## 📊 Verify Installation

### Via Admin Interface (Recommended)

1. **Go to Custom PWA → Installation**
2. You'll see a table with the status of all files
3. If everything is green ✅, installation was successful!

The Installation page shows:
- ✅/❌ Real-time status of required files
- 📍 Exact file paths and URLs
- 📋 Automatic installation results
- 🔧 Manual installation instructions (if automatic copy failed)
- 🆘 Troubleshooting guide
- 🔗 Quick links to all configuration pages

### Via WP-CLI

After activation, you can verify installation with WP-CLI:

```bash
# Check created options
wp option get custom_pwa_config --format=json --allow-root
wp option get custom_pwa_push_rules --format=json --allow-root

# Check VAPID keys
wp option get custom_pwa_push --format=json --allow-root

# Check database table
wp db query "SHOW TABLES LIKE 'wp_custom_pwa_subscriptions';" --allow-root

# Complete installation verification (recommended)
wp eval-file wp-content/plugins/cutsom-pwa/test-complete-activation.php --allow-root
```

### 🧪 Test VAPID Keys (v1.0.5+)

```bash
# Test VAPID key management
wp eval-file wp-content/plugins/cutsom-pwa/test-vapid-management.php --allow-root
```

This tests:
- Current VAPID keys display
- New key generation
- Key uniqueness verification
- OpenSSL EC P-256 capabilities

---

## 🎯 First Configuration

After activation, follow these steps:

### 1. Verify Installation

**Go to Custom PWA → Installation**
- ✅ Check that `sw.js` and `offline.html` show green status
- 📍 Note file locations and URLs
- 🔧 Follow troubleshooting if any issues

### 2. Enable Features

**Go to Custom PWA → Config**
- ✅ Check "Enable PWA"
- ✅ Check "Enable Push Notifications"
- 🔑 **VAPID Keys Management**: View your keys (automatically generated)
  - Keys are displayed with status indicator
  - Private key is truncated for security
  - Can regenerate if needed (invalidates subscriptions)
- 🎯 Select post types to monitor

### 3. Configure PWA

**Go to Custom PWA → PWA**
- Configure application name
- Choose colors (theme, background)
- Upload icon (minimum 192x192px, 512x512px recommended)

### 4. Configure Push Notifications

**Go to Custom PWA → Push → Post Type Configuration**
- Select a post type (e.g., Post)
- ✅ Check "Enable Push Notifications for this post type"
- ✅ Enable desired scenarios
- 📝 Customize notification templates

### 5. Test!

- Publish a post
- Check logs: `tail -f wp-content/debug.log`
- Notifications should be sent automatically

---

## 🔄 Clean Reinstallation

If you want to start fresh:

```bash
# Deactivate plugin
wp plugin deactivate cutsom-pwa --allow-root

# Delete options
wp option delete custom_pwa_config --allow-root
wp option delete custom_pwa_settings --allow-root
wp option delete custom_pwa_push_rules --allow-root
wp option delete custom_pwa_custom_scenarios --allow-root
wp option delete custom_pwa_push --allow-root
wp option delete custom_pwa_file_copy_status --allow-root

# Delete table
wp db query "DROP TABLE IF EXISTS wp_custom_pwa_subscriptions;" --allow-root

# Delete files (optional)
rm -f sw.js offline.html

# Reactivate (complete reset)
wp plugin activate cutsom-pwa --allow-root
```

---

## ⚠️ Important Notes

### 1. OpenSSL Required

The plugin needs the PHP OpenSSL extension to generate VAPID keys. If OpenSSL is not available, keys will be empty and push notifications won't work.

**Check OpenSSL**:
```bash
php -m | grep openssl
```

If missing, install it:
- **Ubuntu/Debian**: `sudo apt-get install php-openssl`
- **CentOS/RHEL**: `sudo yum install php-openssl`

### 2. HTTPS Required

Push notifications and PWAs require HTTPS in production. The plugin automatically detects local environments (.local, .test, .dev, localhost) and enables development mode.

For local HTTPS setup, see [SSL-SETUP.md](SSL-SETUP.md).

### 3. Permalinks

The plugin flushes rewrite rules to register the `/manifest.webmanifest` endpoint. If you have issues:
1. Go to **Settings → Permalinks**
2. Click **Save Changes**

### 4. Custom Post Types

If you install a plugin that adds post types (WooCommerce, The Events Calendar, etc.) AFTER activating Custom PWA:
1. Deactivate Custom PWA
2. Reactivate Custom PWA
3. New post types will be automatically configured

### 5. Migrations

The plugin detects and automatically migrates old data format (pre-scenarios) to the new format on first admin page load.

### 6. VAPID Key Regeneration (v1.0.5+)

When to regenerate VAPID keys:
- 🔒 Keys have been compromised or exposed
- 🧪 Testing different configurations
- 🔄 Migrating to new environment
- 🆕 Starting fresh with subscriptions

⚠️ **Warning**: Regenerating keys **invalidates all existing subscriptions**. Users must resubscribe.

**How to regenerate**:
1. Go to **Custom PWA → Config**
2. Scroll to **VAPID Keys Management**
3. Click **Regenerate VAPID Keys**
4. Confirm in dialog
5. All subscriptions are automatically cleared

---

## 🆘 Troubleshooting

### Files Not Installed

**Symptoms**: Installation page shows ❌ for sw.js or offline.html

**Solutions**:
1. Check **Installation** page for detailed error messages
2. Check file permissions on site root
3. Try manual installation (instructions on Installation page)
4. Check Apache/Nginx user permissions

**Manual Installation**:
```bash
# Copy files manually
cp wp-content/plugins/cutsom-pwa/assets/examples/sw-example.js sw.js
cp wp-content/plugins/cutsom-pwa/assets/examples/offline-example.html offline.html

# Set permissions
chmod 644 sw.js offline.html
```

### Scenarios Not Created

**Symptoms**: No scenarios appear in Push configuration

**Solutions**:
1. Check logs: `wp-content/debug.log`
2. Look for "Custom PWA: Initialized default scenarios"
3. Deactivate and reactivate plugin

### Push Notifications Not Sent

**Symptoms**: Notifications don't arrive after publishing

**Checklist**:
1. ✅ Push enabled in **Config**
2. ✅ Post type enabled
3. ✅ At least one scenario enabled
4. ✅ At least one subscriber in database:
   ```bash
   wp db query "SELECT COUNT(*) FROM wp_custom_pwa_subscriptions;" --allow-root
   ```
5. ✅ VAPID keys exist and valid (check **Config** page)
6. ✅ Check logs for errors

### VAPID Keys Empty

**Symptoms**: No keys shown in Config page or keys are empty strings

**Solutions**:
1. Check OpenSSL: `php -m | grep openssl`
2. Check OpenSSL configuration: `php -i | grep -i openssl`
3. Verify P-256 curve support:
   ```bash
   php -r "var_dump(in_array('prime256v1', openssl_get_curve_names()));"
   ```
4. If OpenSSL is missing, install it and reactivate plugin
5. Use **Regenerate** button in Config page

### Manifest Returns 404

**Symptoms**: `/manifest.webmanifest` returns 404

**Solutions**:
1. Go to **Settings → Permalinks**
2. Click **Save Changes** (flushes rewrite rules)
3. Check that PWA is enabled in **Config**
4. Test URL: `https://yoursite.com/manifest.webmanifest`

### Service Worker Not Registering

**Symptoms**: Browser console shows Service Worker registration errors

**Solutions**:
1. Verify HTTPS is active (required for Service Workers)
2. Check `sw.js` exists at site root
3. Check `sw.js` is readable (permissions 644)
4. Clear browser cache
5. Check browser console for specific errors
6. Verify Service Worker code syntax

---

## 📚 Additional Documentation

- [README.md](README.md) - Complete plugin documentation
- [PUSH-REQUIREMENTS.md](PUSH-REQUIREMENTS.md) - Push notification requirements
- [SSL-SETUP.md](SSL-SETUP.md) - Local HTTPS setup guide
- [SCENARIOS-USAGE.md](SCENARIOS-USAGE.md) - Scenario configuration guide
- [CHANGELOG.md](CHANGELOG.md) - Version history
- [assets/examples/README.md](assets/examples/README.md) - Frontend integration examples

---

## 🎓 Next Steps

After successful installation:

1. **Read the scenarios guide**: [SCENARIOS-USAGE.md](SCENARIOS-USAGE.md)
2. **Configure your first notification scenario**
3. **Test with the built-in test tool** (Push settings page)
4. **Deploy to production** with HTTPS enabled
5. **Monitor subscriptions** in the database

---

## 🐛 Support

- **Documentation**: Check all `.md` files in plugin directory
- **GitHub Issues**: [Matth-Ben/cutsom-pwa](https://github.com/Matth-Ben/cutsom-pwa/issues)
- **Debug Mode**: Enable in **Config** for detailed logs

---

**Version**: 1.0.5  
**Last Updated**: December 22, 2024
