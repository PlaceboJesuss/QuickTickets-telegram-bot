#!/bin/sh
set -e

# Подставляем DOMAIN из окружения в шаблон nginx.conf.template
envsubst '${DOMAIN}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

CERT_PATH="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
KEY_PATH="/etc/letsencrypt/live/${DOMAIN}/privkey.pem"

# Проверяем, есть ли сертификаты
if [ ! -f "$CERT_PATH" ] || [ ! -f "$KEY_PATH" ]; then
  echo "SSL certificates not found for ${DOMAIN}. Starting temporary HTTP server..."
  # Запуск Nginx без SSL, чтобы certbot мог получить сертификаты
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

# Запускаем основной процесс Nginx
echo "Starting Nginx with SSL for ${DOMAIN}..."
exec nginx -g 'daemon off;'
