#!/bin/bash

###############################################################################
# mkcert Installation Script for Local Development
# 
# This script installs mkcert and generates a valid SSL certificate
# for local development with Service Workers.
#
# Usage: sudo bash install-mkcert.sh labo.local
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Domain parameter (default to labo.local if not provided)
DOMAIN="${1:-labo.local}"

echo -e "${GREEN}=====================================${NC}"
echo -e "${GREEN}  mkcert SSL Certificate Setup${NC}"
echo -e "${GREEN}=====================================${NC}"
echo ""
echo -e "Domain: ${YELLOW}${DOMAIN}${NC}"
echo ""

# Check if running as root for nginx config
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Error: This script must be run as root (sudo)${NC}"
    echo "Usage: sudo bash $0 [domain]"
    exit 1
fi

# Get the actual user (not root when using sudo)
ACTUAL_USER="${SUDO_USER:-$USER}"
ACTUAL_HOME=$(eval echo ~$ACTUAL_USER)

echo -e "${YELLOW}[1/6]${NC} Installing dependencies..."
apt-get update -qq
apt-get install -y libnss3-tools wget > /dev/null 2>&1
echo -e "${GREEN}✓${NC} Dependencies installed"

echo -e "${YELLOW}[2/6]${NC} Downloading mkcert..."
if [ ! -f "/usr/local/bin/mkcert" ]; then
    wget -q https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-linux-amd64 -O /tmp/mkcert
    mv /tmp/mkcert /usr/local/bin/mkcert
    chmod +x /usr/local/bin/mkcert
    echo -e "${GREEN}✓${NC} mkcert downloaded and installed"
else
    echo -e "${GREEN}✓${NC} mkcert already installed"
fi

echo -e "${YELLOW}[3/6]${NC} Installing local Certificate Authority..."
# Run as actual user, not root
sudo -u $ACTUAL_USER mkcert -install > /dev/null 2>&1
echo -e "${GREEN}✓${NC} CA installed in $ACTUAL_HOME/.local/share/mkcert"

echo -e "${YELLOW}[4/6]${NC} Generating certificate for ${DOMAIN}..."
cd /tmp
sudo -u $ACTUAL_USER mkcert $DOMAIN > /dev/null 2>&1

# Move certificates to nginx directory
mkdir -p /etc/nginx/ssl
mv /tmp/${DOMAIN}.pem /etc/nginx/ssl/
mv /tmp/${DOMAIN}-key.pem /etc/nginx/ssl/
chmod 644 /etc/nginx/ssl/${DOMAIN}.pem
chmod 600 /etc/nginx/ssl/${DOMAIN}-key.pem

echo -e "${GREEN}✓${NC} Certificates generated:"
echo "   - /etc/nginx/ssl/${DOMAIN}.pem"
echo "   - /etc/nginx/ssl/${DOMAIN}-key.pem"

echo -e "${YELLOW}[5/6]${NC} Updating nginx configuration..."

# Find nginx config file
NGINX_CONFIG=""
if [ -f "/etc/nginx/sites-available/${DOMAIN}" ]; then
    NGINX_CONFIG="/etc/nginx/sites-available/${DOMAIN}"
elif [ -f "/etc/nginx/sites-enabled/${DOMAIN}" ]; then
    NGINX_CONFIG="/etc/nginx/sites-enabled/${DOMAIN}"
elif [ -f "/etc/nginx/sites-available/default" ]; then
    NGINX_CONFIG="/etc/nginx/sites-available/default"
else
    echo -e "${YELLOW}⚠${NC}  Could not find nginx config automatically"
    echo "   Please manually add to your nginx config:"
    echo ""
    echo "   ssl_certificate /etc/nginx/ssl/${DOMAIN}.pem;"
    echo "   ssl_certificate_key /etc/nginx/ssl/${DOMAIN}-key.pem;"
    echo ""
    NGINX_CONFIG=""
fi

if [ -n "$NGINX_CONFIG" ]; then
    echo "   Found config: $NGINX_CONFIG"
    
    # Backup original config
    cp "$NGINX_CONFIG" "${NGINX_CONFIG}.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Update SSL certificate paths
    sed -i "s|ssl_certificate .*;|ssl_certificate /etc/nginx/ssl/${DOMAIN}.pem;|g" "$NGINX_CONFIG"
    sed -i "s|ssl_certificate_key .*;|ssl_certificate_key /etc/nginx/ssl/${DOMAIN}-key.pem;|g" "$NGINX_CONFIG"
    
    echo -e "${GREEN}✓${NC} nginx configuration updated"
fi

echo -e "${YELLOW}[6/6]${NC} Testing and reloading nginx..."
nginx -t
if [ $? -eq 0 ]; then
    systemctl reload nginx
    echo -e "${GREEN}✓${NC} nginx reloaded successfully"
else
    echo -e "${RED}✗${NC} nginx configuration test failed"
    echo "   Please check the configuration manually"
    exit 1
fi

echo ""
echo -e "${GREEN}=====================================${NC}"
echo -e "${GREEN}  Installation Complete! ✓${NC}"
echo -e "${GREEN}=====================================${NC}"
echo ""
echo -e "${GREEN}✓${NC} Valid SSL certificate installed for ${YELLOW}${DOMAIN}${NC}"
echo -e "${GREEN}✓${NC} Chrome/Firefox will trust this certificate"
echo -e "${GREEN}✓${NC} Service Workers will work without errors"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Restart your browser completely"
echo "2. Visit https://${DOMAIN}"
echo "3. Check console (F12) - no more SSL errors!"
echo ""
echo -e "${GREEN}Done!${NC}"
