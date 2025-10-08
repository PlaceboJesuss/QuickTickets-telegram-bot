#!/bin/sh
set -e

# -----------------------------
# Подставляем переменные из .env в nginx конфиг
# -----------------------------
envsubst '$APP_DOMAIN' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# -----------------------------
# Запускаем nginx в форграунд
# -----------------------------
echo ">>> Starting Nginx..."
exec nginx -g 'daemon off;'