#!/bin/sh
set -e

# Папки для certbot
CERTBOT_ETC=/etc/letsencrypt
CERTBOT_VAR=/var/lib/letsencrypt
WEBROOT=/var/www/certbot

mkdir -p $WEBROOT

# Проверяем, задан ли домен
if [ -z "$APP_DOMAIN" ]; then
  echo "APP_DOMAIN not set in environment"
  exit 1
fi

if [ -z "$APP_EMAIL" ]; then
  echo "APP_EMAIL not set in environment"
  exit 1
fi

# Получаем сертификат, если его ещё нет
if [ ! -f "$CERTBOT_ETC/live/$APP_DOMAIN/fullchain.pem" ]; then
  echo "Requesting certificate for $APP_DOMAIN..."
  certbot certonly --webroot -w $WEBROOT -d $APP_DOMAIN --email $APP_EMAIL --agree-tos --non-interactive
else
  echo "Certificate already exists for $APP_DOMAIN"
fi

# Контейнер просто ждёт, чтобы nginx мог использовать сертификаты
tail -f /dev/null
