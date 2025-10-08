#!/bin/sh
set -e

# Подставляем APP_DOMAIN из окружения в шаблон nginx.conf.template
envsubst '${APP_DOMAIN}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

CERT_PATH="/etc/letsencrypt/live/${APP_DOMAIN}/fullchain.pem"
KEY_PATH="/etc/letsencrypt/live/${APP_DOMAIN}/privkey.pem"
DH_PATH="/etc/letsencrypt/ssl-dhparams.pem"
SSL_OPTIONS="/etc/letsencrypt/options-ssl-nginx.conf"

# Создаём placeholder-файлы, если их нет
mkdir -p /etc/letsencrypt

if [ ! -f "$SSL_OPTIONS" ]; then
  echo "⚙️  Creating placeholder SSL options config..."
  touch "$SSL_OPTIONS"
fi

if [ ! -f "$DH_PATH" ]; then
  echo "⚙️  Creating DH parameters..."
  wget -q https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem -O "$DH_PATH"
fi

# Проверяем, есть ли сертификаты
if [ ! -f "$CERT_PATH" ] || [ ! -f "$KEY_PATH" ]; then
  echo "SSL certificates not found for ${DOMAIN}. Starting temporary HTTP server..."
  nginx -g 'daemon off;' &
  TEMP_PID=$!

  echo "Waiting for SSL certificates to be created..."
  while [ ! -f "$CERT_PATH" ] || [ ! -f "$KEY_PATH" ]; do
    sleep 2
  done

  echo "Certificates are ready! Restarting Nginx with SSL..."
  kill "$TEMP_PID"
  sleep 2
fi

# Запускаем основной процесс Nginx с SSL
echo "Starting Nginx with SSL for ${DOMAIN}..."
exec nginx -g 'daemon off;'
