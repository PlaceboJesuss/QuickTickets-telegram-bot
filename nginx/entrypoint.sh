#!/bin/sh
set -e

DOMAIN="${APP_DOMAIN}"
EMAIL="${APP_EMAIL}"

CONF_TEMPLATE="/etc/nginx/conf.d/default.conf.template"
CONF_FILE="/etc/nginx/conf.d/default.conf"

CERT_PATH="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
KEY_PATH="/etc/letsencrypt/live/${DOMAIN}/privkey.pem"
DH_PATH="/etc/letsencrypt/ssl-dhparams.pem"
SSL_OPTIONS="/etc/letsencrypt/options-ssl-nginx.conf"

# -----------------------------------------------
# 1. Подставляем APP_DOMAIN в шаблон конфига
# -----------------------------------------------
echo "🔧 Generating Nginx config for domain: ${DOMAIN}"
envsubst '${APP_DOMAIN}' < "$CONF_TEMPLATE" > "$CONF_FILE"

# -----------------------------------------------
# 2. Подготавливаем SSL вспомогательные файлы
# -----------------------------------------------
mkdir -p /etc/letsencrypt /var/www/certbot

if [ ! -f "$SSL_OPTIONS" ]; then
  echo "⚙️  Creating placeholder SSL options config..."
  cat > "$SSL_OPTIONS" <<EOF
ssl_session_cache shared:le_nginx_SSL:10m;
ssl_session_timeout 1440m;
EOF
fi

if [ ! -f "$DH_PATH" ]; then
  echo "⚙️  Downloading DH parameters..."
  wget -q https://raw.githubusercontent.com/certbot/certbot/master/certbot/ssl-dhparams.pem -O "$DH_PATH" || {
    echo "⚠️  Failed to download DH params, creating empty placeholder."
    touch "$DH_PATH"
  }
fi

# -----------------------------------------------
# 3. Проверяем наличие сертификатов
# -----------------------------------------------
if [ ! -f "$CERT_PATH" ] || [ ! -f "$KEY_PATH" ]; then
  echo "🔒 SSL certificates not found for ${DOMAIN}."

  echo "🚀 Trying to obtain certificates via certbot (standalone)..."
  docker compose run --rm certbot certonly --standalone \
    --email "${EMAIL}" --agree-tos --no-eff-email \
    -d "${DOMAIN}" -d "www.${DOMAIN}" || {
      echo "❌ Failed to obtain certificates. Exiting."
      exit 1
    }

  echo "✅ Certificates successfully obtained for ${DOMAIN}."
else
  echo "✅ SSL certificates already exist, skipping obtain."
fi

# -----------------------------------------------
# 4. Запускаем Nginx с SSL
# -----------------------------------------------
echo "🚀 Starting Nginx with SSL for ${DOMAIN}..."
exec nginx -g 'daemon off;'
