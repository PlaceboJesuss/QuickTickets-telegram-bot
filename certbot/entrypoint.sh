#!/bin/sh
set -e

# -----------------------------
# Configuration
# -----------------------------
CERTBOT_DOMAIN=${APP_DOMAIN}
CERTBOT_EMAIL=${APP_EMAIL}

# -----------------------------
# Получение нового сертификата, если его нет
# -----------------------------
if [ ! -d "/etc/letsencrypt/live/$CERTBOT_DOMAIN" ]; then
    echo ">>> No SSL certificate found for $CERTBOT_DOMAIN."
    echo ">>> Starting standalone certbot to obtain initial certificate..."

    # certbot сам слушает порт 80, nginx должен быть остановлен
    certbot certonly --standalone \
        --preferred-challenges http \
        --email "$CERTBOT_EMAIL" \
        --agree-tos --no-eff-email \
        -d "$CERTBOT_DOMAIN" -d "www.$CERTBOT_DOMAIN"
else
    echo ">>> SSL certificate already exists, skipping initial obtain."
fi

# -----------------------------
# Запуск nginx
# -----------------------------
echo ">>> Starting Nginx..."
nginx -g 'daemon off;' &

# -----------------------------
# Автообновление сертификата каждые 12 часов
# -----------------------------
echo ">>> Starting auto-renewal loop..."
while :; do
    certbot renew --standalone --quiet
    nginx -s reload || true
    sleep 12h & wait $!
done
