#!/bin/sh
set -e

# -----------------------------
# Configuration
# -----------------------------
CERTBOT_DOMAIN=${APP_DOMAIN}
CERTBOT_EMAIL=${APP_EMAIL}
WEBROOT_PATH=/var/www/certbot

# -----------------------------
# Получение нового сертификата, если его нет
# -----------------------------
if [ ! -d "/etc/letsencrypt/live/$CERTBOT_DOMAIN" ]; then
    echo ">>> Obtaining new SSL certificate for $CERTBOT_DOMAIN..."
    sleep 10
    certbot certonly --webroot -w $WEBROOT_PATH --email $CERTBOT_EMAIL --agree-tos --no-eff-email -d $CERTBOT_DOMAIN
else
    echo ">>> SSL certificate already exists, skipping initial obtain."
fi

# -----------------------------
# Автообновление сертификата каждые 12 часов
# -----------------------------
echo ">>> Starting auto-renewal loop..."
while :; do
    certbot renew --webroot -w $WEBROOT_PATH --quiet
    sleep 12h & wait $${!}
done
