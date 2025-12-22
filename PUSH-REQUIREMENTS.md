# Push Notifications Requirements

## ❌ mkcert is NOT Used for Push Notifications

**mkcert** is a tool that generates SSL/TLS certificates **for local HTTPS development only**.

- ✅ Useful for: Having `https://localhost` or `https://labo.local` in development
- ❌ NOT used for: Sending push notifications
- ℹ️ Role: Only allows Service Workers to function (they require HTTPS)

## ✅ What is ACTUALLY Used: VAPID

### Web Push Protocol (RFC 8292)

Web push notifications use the **VAPID** (Voluntary Application Server Identification) protocol:

```
┌─────────────────────┐
│  WordPress Plugin   │
│                     │
│  1. Generate VAPID  │──── OpenSSL (PHP extension)
│     EC P-256 keys   │     Curve: prime256v1 (P-256)
│                     │
│  2. Sign requests   │──── JWT (JSON Web Token)
│     with keys       │     Header: ES256 algorithm
│                     │
│  3. Send push to    │──── cURL or wp_remote_post()
│     browser service │     HTTPS to push service
└─────────────────────┘
```

### Required System Dependencies

#### 1. **PHP Extensions** (✅ All Standard)

| Extension | Usage | Status |
|-----------|-------|--------|
| `openssl` | VAPID key generation (EC P-256) | ✅ Standard |
| `curl` | Send push requests to browsers | ✅ Standard |
| `json` | Encode notification payloads | ✅ Standard |
| `mbstring` | Binary data handling | ✅ Standard |

> **Note**: These extensions are included in standard PHP 8.0+ installations. No external tools needed!

#### 2. **OpenSSL Capabilities** (✅ All Supported)

```php
// The plugin uses:
- openssl_pkey_new()       → Key pair generation
- openssl_pkey_export()    → Export private key in PEM format
- openssl_pkey_get_details() → Extract EC coordinates
- Curve 'prime256v1'       → P-256 (65 bytes, uncompressed)
```

**Verification**:
```bash
php -r "var_dump(in_array('prime256v1', openssl_get_curve_names()));"
# Expected result: bool(true) ✅
```

#### 3. **PHP Version** (✅ Compatible)

- **Required**: PHP >= 8.0
- **Recommended**: PHP 8.1+
- **Tested with**: PHP 8.4

#### 4. **HTTPS** (⚠️ Required in Production)

- **Why**: Service Workers only work over HTTPS
- **Local Development**: 
  - ✅ Use `mkcert` to generate local certificates
  - ✅ Or self-signed certificates
  - See [SSL-SETUP.md](SSL-SETUP.md) for details
- **Production**:
  - ✅ Let's Encrypt (free)
  - ✅ Commercial SSL/TLS certificate

**Note**: HTTPS is required for the browser to **register** the Service Worker, but not for the server to **send** push notifications.

## 🔐 How VAPID Keys are Generated

### Automatic Generation on Plugin Activation (v1.0.0+)

The plugin automatically generates VAPID keys when activated:

```php
// In custom-pwa.php, activate() method
private function generate_vapid_keys() {
    // 1. Configure elliptic curve P-256
    $config = array(
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name'       => 'prime256v1',
    );
    
    // 2. Generate key pair
    $key_resource = openssl_pkey_new($config);
    
    // 3. Export private key (PEM format)
    openssl_pkey_export($key_resource, $private_key_pem);
    
    // 4. Extract public key (raw EC point)
    $key_details = openssl_pkey_get_details($key_resource);
    $ec_key = $key_details['ec'];
    
    // 5. Build uncompressed public key
    // Format: 0x04 + X (32 bytes) + Y (32 bytes) = 65 bytes
    $public_key_raw = "\x04" . $ec_key['x'] . $ec_key['y'];
    
    // 6. Encode in base64url (Web Push standard)
    $public_key_base64url = base64url_encode($public_key_raw);
    $private_key_base64url = base64url_encode($private_key_pem);
    
    return array(
        'public_key'  => $public_key_base64url,
        'private_key' => $private_key_base64url,
    );
}
```

### VAPID Key Management Interface (v1.0.5+)

Go to **Custom PWA → Config → VAPID Keys Management** to:

- **View keys**: See your public and private VAPID keys
- **Check status**: Visual indicator (✅/❌) showing if keys are valid
- **View details**: Key lengths, algorithm information (P-256, RFC 8292)
- **Regenerate keys**: One-click regeneration with confirmation dialog
- **Security features**: Private key truncated in display, warning labels

### Verifying Generated Keys

```bash
# Check that keys exist in WordPress options
wp option get custom_pwa_push --format=json --allow-root

# Expected result:
{
  "public_key": "BHSsbnKredB5f9LrRcIMiWIKAY75VTydzXxi6pyJUgyF...",
  "private_key": "LS0tLS1CRUdJTiBQUklWQVRFIEtFWS0tLS0tCk1JR0hBZ0VBT..."
}
```

Or simply visit **Custom PWA → Config** in your WordPress admin to see the keys visually.

## � When to Regenerate VAPID Keys

You may need to regenerate your VAPID keys in these situations:

1. 🔒 **Keys Compromised**: Private key has been exposed or leaked
2. 🧪 **Testing**: Want to start fresh with clean subscription data
3. 🔄 **Migration**: Moving to a new environment
4. 🆕 **Fresh Start**: Clear all subscriptions and start over

⚠️ **Warning**: Regenerating VAPID keys will **invalidate all existing push subscriptions**. Users will need to resubscribe to receive notifications.

### How to Regenerate Keys

1. Go to **Custom PWA → Config**
2. Scroll to **VAPID Keys Management** section
3. Click the **"Regenerate VAPID Keys"** button
4. Confirm in the dialog (JavaScript confirmation)
5. Keys are regenerated and all subscriptions are automatically cleared

## 📦 What is Automatically Installed?

### On Plugin Activation:

1. ✅ **VAPID Keys** → Automatically generated (OpenSSL)
2. ✅ **Database Table** → `wp_custom_pwa_subscriptions` created
3. ✅ **WordPress Options** → `custom_pwa_push`, `custom_pwa_config`, etc.
4. ✅ **PWA Files** → `sw.js`, `offline.html` copied to site root (v1.0.4+)
5. ✅ **Scenarios** → Configured for all public post types
6. ✅ **Default Settings** → Safe defaults with all features disabled

### What is NOT Installed:

- ❌ **mkcert** → External tool, not needed for push notifications
- ❌ **Third-party library** → Plugin uses native PHP OpenSSL
- ❌ **Node.js or npm** → No server-side JavaScript dependencies
- ❌ **Composer packages** → All functionality built-in (web-push library included in future versions)

## 🔍 Complete Environment Verification

### Method 1: Use the Test Script (Recommended)

```bash
cd wp-content/plugins/custom-pwa
wp eval-file test-complete-activation.php --allow-root
```

This script checks:
- ✅ All required PHP extensions
- ✅ OpenSSL capabilities (P-256 curve support)
- ✅ PHP version compatibility
- ✅ Database table creation
- ✅ VAPID keys existence and validity
- ✅ File installation status
- ✅ WordPress options

### Method 2: Manual Checks

#### Check PHP Extensions:
```bash
php -m | grep -E '(openssl|curl|json|mbstring)'
```

Expected output:
```
curl
json
mbstring
openssl
```

#### Check OpenSSL P-256 Support:
```bash
php -r "var_dump(in_array('prime256v1', openssl_get_curve_names()));"
```

Expected: `bool(true)`

#### Check PHP Version:
```bash
php -v
```

Expected: `PHP 8.0.0` or higher

#### Check VAPID Keys via WP-CLI:
```bash
wp option get custom_pwa_push --format=json --allow-root
```

#### Check Installation via Admin:
1. Go to **Custom PWA → Installation**
2. Verify all files show ✅
3. Check VAPID keys in **Config** page

## 🚀 Production Deployment Checklist

Before deploying to production, verify:

- [ ] **PHP Extensions**: openssl, curl, json, mbstring installed
- [ ] **PHP Version**: >= 8.0 (8.1+ recommended)
- [ ] **HTTPS Active**: Valid SSL/TLS certificate (Let's Encrypt recommended)
- [ ] **VAPID Keys**: Generated and valid (check Config page)
- [ ] **Service Worker**: Accessible at `https://yoursite.com/sw.js`
- [ ] **Manifest**: Accessible at `https://yoursite.com/manifest.webmanifest`
- [ ] **File Permissions**: sw.js and offline.html readable (644)
- [ ] **Firewall**: Allows outbound HTTPS connections to push services
- [ ] **Testing**: Test notification sent successfully
- [ ] **Browser Support**: Verified on Chrome, Firefox, Edge (not iOS Safari)

## 🧪 Testing VAPID Functionality

Use the dedicated test script:

```bash
cd wp-content/plugins/custom-pwa
wp eval-file test-vapid-management.php --allow-root
```

This tests:
- Current VAPID keys display
- New key generation
- Key uniqueness verification
- OpenSSL EC P-256 capabilities
- Base64url encoding

## 📚 Resources

### Official Specifications
- [Web Push Protocol (RFC 8292)](https://datatracker.ietf.org/doc/html/rfc8292)
- [VAPID Specification](https://datatracker.ietf.org/doc/html/draft-thomson-webpush-vapid-02)
- [Service Workers API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Push API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)

### Plugin Documentation
- [README.md](README.md) - Complete plugin documentation
- [INSTALLATION.md](INSTALLATION.md) - Step-by-step installation guide
- [SSL-SETUP.md](SSL-SETUP.md) - Local HTTPS setup with mkcert
- [CHANGELOG.md](CHANGELOG.md) - Version history

## 🛠️ Troubleshooting

### Keys Not Generated

**Symptoms**: No VAPID keys shown in Config page

**Solutions**:
1. Deactivate and reactivate the plugin
2. Check PHP error logs: `tail -f /var/log/php-fpm/error.log`
3. Verify OpenSSL extension: `php -m | grep openssl`
4. Check OpenSSL configuration: `php -i | grep -i openssl`

### OpenSSL Extension Missing

**Symptoms**: Error about openssl functions not found

**Solutions**:

**Ubuntu/Debian**:
```bash
sudo apt-get install php8.2-openssl
sudo systemctl restart php8.2-fpm
```

**CentOS/RHEL**:
```bash
sudo yum install php-openssl
sudo systemctl restart php-fpm
```

**Check installation**:
```bash
php -m | grep openssl
```

### P-256 Curve Not Supported

**Symptoms**: Error about prime256v1 curve not available

**Solution**: Update OpenSSL:
```bash
# Check current version
openssl version

# Update (Ubuntu/Debian)
sudo apt-get update
sudo apt-get upgrade openssl libssl-dev

# Rebuild PHP if necessary
```

### Push Notifications Not Sending

**Symptoms**: Keys exist but notifications don't arrive

**Debug checklist**:
1. ✅ Check VAPID keys are valid (Config page)
2. ✅ Enable debug mode in Config settings
3. ✅ Check WordPress debug log: `wp-content/debug.log`
4. ✅ Verify HTTPS is active
5. ✅ Test with Test Notification feature
6. ✅ Check browser console for Service Worker errors
7. ✅ Verify subscription exists in database:
   ```bash
   wp db query "SELECT * FROM wp_custom_pwa_subscriptions;" --allow-root
   ```

### Subscriptions Invalid After Key Regeneration

**This is normal behavior!**

When you regenerate VAPID keys:
- ✅ Old subscriptions become invalid (different key pair)
- ✅ Plugin automatically clears all subscriptions
- ✅ Users must resubscribe with new keys

**Action**: Ask users to refresh the page and resubscribe to notifications.

## ⚠️ Important Notes

1. **mkcert is ONLY for local HTTPS development** - It has no role in sending push notifications
2. **VAPID keys are generated by PHP/OpenSSL** - No external installation required
3. **HTTPS is required for Service Workers** - But not for server-side push sending
4. **All dependencies are standard** - Included in modern PHP 8.0+ installations
5. **Keys are stored securely** - In WordPress options, never expose private key in frontend
6. **Regeneration invalidates subscriptions** - Always warn users before regenerating
7. **iOS Safari doesn't support Web Push** - Only works on macOS Safari 16+, not iOS

## ✅ Quick Verification Checklist

Run these commands to verify everything:

```bash
# 1. Check PHP version
php -v | grep "PHP 8"

# 2. Check required extensions
php -m | grep -E '(openssl|curl|json|mbstring)'

# 3. Check P-256 curve support
php -r "var_dump(in_array('prime256v1', openssl_get_curve_names()));"

# 4. Check VAPID keys exist
wp option get custom_pwa_push --format=json --allow-root

# 5. Run complete test
cd wp-content/plugins/custom-pwa && wp eval-file test-complete-activation.php --allow-root

# 6. Check installation status
wp eval-file test-vapid-management.php --allow-root
```

---

**Conclusion**: The plugin is **100% self-contained** and requires no external tools (like mkcert) to send push notifications. It only uses standard PHP extensions (OpenSSL, cURL) that are present in virtually all modern hosting environments. VAPID keys are automatically generated, can be managed through a user-friendly interface, and can be regenerated at any time if needed.
