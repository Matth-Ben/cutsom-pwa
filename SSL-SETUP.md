# SSL Setup Guide for Local Development

## 🚨 Problem

Service Workers and Web Push require **HTTPS** or **localhost**. When developing on a local domain like `mysite.local`, browsers block Service Workers with this error:

```
SecurityError: Failed to register a ServiceWorker: An SSL certificate error occurred
```

This happens because:
- Your local domain uses a self-signed SSL certificate
- Browsers don't trust self-signed certificates by default
- Service Workers have strict security requirements

## ✅ Solutions

### Option 1: Use the SSL Helper (Recommended)

The plugin includes an **automated SSL setup helper**:

1. Go to **WordPress Admin → Custom PWA → 🔒 SSL Helper**
2. The page will:
   - Detect if you're on a local domain (*.local, *.test, *.dev)
   - Check if HTTPS is enabled
   - Check if mkcert is installed
   - Show your SSL certificate status
3. If SSL issues detected, you'll see:
   - An admin notice with installation command
   - Copy-to-clipboard button for easy setup
   - Step-by-step diagnostic information

**One-Click Installation:**
```bash
# The SSL Helper will show you this exact command
cd /var/www/sites/labo/public/wp-content/plugins/cutsom-pwa
sudo bash install-mkcert.sh your-domain.local
```

The script will:
- ✅ Install mkcert tool automatically
- ✅ Generate trusted local certificates  
- ✅ Update your nginx configuration
- ✅ Reload nginx server
- ✅ Service Workers will work immediately!

### Option 2: Enable Local Development Mode

If you can't install mkcert, enable **Local Development Mode**:

1. Go to **WordPress Admin → Custom PWA → Config**
2. Check **"Local Development Mode"** ✓
3. Save settings

This mode:
- **Auto-detects** local domains (*.local, *.test, *.dev, localhost)
- Shows helpful console warnings about SSL
- Provides solution steps in browser console
- **NEVER enable this in production!**

Console output with Local Dev Mode:
```javascript
[Custom PWA] 🔓 Local Development Mode is ENABLED
[Custom PWA] SSL certificate checks are bypassed for Service Worker
[Custom PWA] ⚠️ NEVER use this mode in production!
```

### Option 3: Manual mkcert Installation

If the automated script doesn't work, install mkcert manually:

#### Step 1: Install mkcert

**Linux (Debian/Ubuntu):**
```bash
sudo apt install libnss3-tools
curl -JLO "https://dl.filippo.io/mkcert/latest?for=linux/amd64"
chmod +x mkcert-v*-linux-amd64
sudo mv mkcert-v*-linux-amd64 /usr/local/bin/mkcert
mkcert -install
```

**macOS:**
```bash
brew install mkcert
brew install nss # for Firefox
mkcert -install
```

**Windows (PowerShell as Admin):**
```powershell
choco install mkcert
mkcert -install
```

#### Step 2: Generate Certificate

```bash
cd /path/to/ssl/certificates
mkcert your-domain.local

# This creates:
# - your-domain.local.pem (certificate)
# - your-domain.local-key.pem (private key)
```

#### Step 3: Configure Web Server

**For nginx:**
```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.local;
    
    ssl_certificate /path/to/ssl/your-domain.local.pem;
    ssl_certificate_key /path/to/ssl/your-domain.local-key.pem;
    
    # ... rest of config
}
```

**For Apache:**
```apache
<VirtualHost *:443>
    ServerName your-domain.local
    
    SSLEngine on
    SSLCertificateFile /path/to/ssl/your-domain.local.pem
    SSLCertificateKeyFile /path/to/ssl/your-domain.local-key.pem
    
    # ... rest of config
</VirtualHost>
```

#### Step 4: Restart Web Server

```bash
# nginx
sudo systemctl restart nginx

# Apache
sudo systemctl restart apache2
```

#### Step 5: Restart Browser

**Important:** After installing certificates, you MUST completely restart your browser:
- Close ALL browser windows
- Quit the browser application
- Start browser again
- Visit your site

Certificates won't be recognized until browser restart!

### Option 4: Accept Self-Signed Certificate (Quick but Insecure)

⚠️ **Not recommended** - Only for quick testing:

1. Open **new tab** and navigate to: `https://your-domain.local/sw.js`
2. Click **"Advanced"** → **"Proceed to site (unsafe)"**
3. Return to your site and reload
4. Service Worker will register (but certificate still not trusted)

**Limitations:**
- Must repeat for each new browser session
- Browser shows "Not Secure" warning
- Not suitable for real development work
- Security risks for testing

### Option 5: Use Localhost with HTTP

If you're testing on `localhost` or `127.0.0.1`:

```bash
# In /etc/hosts
127.0.0.1 localhost
```

Service Workers work on **http://localhost** without SSL! But this doesn't work for custom domains like `mysite.local`.

### Option 6: Chrome Flags (Last Resort)

⚠️ **Very insecure** - Only use temporarily:

1. Open: `chrome://flags/#allow-insecure-localhost`
2. Enable: **"Allow invalid certificates for resources loaded from localhost"**
3. Restart Chrome
4. Visit your site

**Major security risk** - disable immediately after testing!

## 🔍 Troubleshooting

### Service Worker still not registering?

**Check 1: SSL Certificate Status**
```bash
# Check if certificate is valid
openssl s_client -connect your-domain.local:443 -servername your-domain.local

# Look for:
# - Verify return code: 0 (ok)  ✅ Good
# - Verify return code: 18 (self signed certificate)  ❌ Bad
```

**Check 2: Browser Console**
Open browser console (F12) and look for:
```javascript
// Good ✅
[Custom PWA] Service Worker registered successfully
[Custom PWA] Service Worker is ready

// Bad ❌  
SecurityError: Failed to register a ServiceWorker
DOMException: The operation is insecure
```

**Check 3: mkcert Installation**
```bash
mkcert -version
# Should output: v1.4.4 or similar

ls -la ~/.local/share/mkcert/
# Should show rootCA.pem and rootCA-key.pem
```

**Check 4: nginx Configuration**
```bash
sudo nginx -t
# Should output: configuration file is ok

sudo systemctl status nginx
# Should be: active (running)
```

**Check 5: PHP cURL Extension**
```bash
php -m | grep curl
# Should output: curl

# If missing, install:
sudo apt-get install php8.3-curl
sudo systemctl restart php8.3-fpm
```

### Browser-Specific Issues

**Firefox:**
- Install `nss` tools: `sudo apt install libnss3-tools`
- Restart Firefox completely after mkcert install

**Chrome/Chromium:**
- Check `chrome://net-internals/#hsts` and delete domain if present
- Clear SSL state: Settings → Privacy → Clear browsing data → Cached images/files

**Safari:**
- mkcert should automatically add to macOS Keychain
- If not working: Keychain Access → System → Find cert → Always Trust

### Still Getting Errors?

1. **Verify plugin version:** 1.0.5 or higher
2. **Check SSL Helper page:** Custom PWA → 🔒 SSL Helper
3. **Enable Debug Mode:** Custom PWA → Config → Debug Mode
4. **View logs:**
   ```bash
   # nginx error log
   sudo tail -f /var/log/nginx/error.log | grep "Custom PWA"
   
   # PHP error log
   sudo tail -f /var/log/php8.3-fpm.log
   ```
5. **Test notification manually:**
   - Go to: Custom PWA → Push
   - Click "Send Test Notification"
   - Check browser for notification
   - Check admin for success message

## 📚 Additional Resources

- [mkcert GitHub](https://github.com/FiloSottile/mkcert)
- [Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Web Push Protocol](https://datatracker.ietf.org/doc/html/rfc8030)
- [VAPID Specification](https://datatracker.ietf.org/doc/html/rfc8292)

## ⚙️ Requirements

**Minimum Requirements:**
- WordPress 6.0+
- PHP 8.0+
- PHP Extensions:
  - OpenSSL (for VAPID key generation)
  - cURL (for push notifications)
  - JSON

**Check Requirements:**
```bash
# PHP version
php -v

# PHP extensions
php -m | grep -E "(openssl|curl|json)"

# Install missing extensions
sudo apt-get install php8.3-curl php8.3-json
sudo systemctl restart php8.3-fpm
```

## 🔐 Security Notes

1. **Never commit mkcert root CA** to version control
2. **Keep VAPID private keys secure** (stored in database)
3. **Disable Local Dev Mode in production**
4. **Use real SSL certificates in production** (Let's Encrypt, etc.)
5. **JWT tokens expire after 12 hours** for security

## 🎯 Quick Start Summary

**Fastest setup (30 seconds):**
```bash
# 1. Run automated installer
cd /var/www/sites/your-site/public/wp-content/plugins/cutsom-pwa
sudo bash install-mkcert.sh your-domain.local

# 2. Restart browser completely

# 3. Test push notifications
# Go to: WP Admin → Custom PWA → Push → Send Test Notification
```

That's it! Service Workers and Push Notifications should now work perfectly! 🎉
