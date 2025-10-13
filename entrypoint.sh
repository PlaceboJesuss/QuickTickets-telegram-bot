#!/bin/bash
set -e
set -x 

ENV_FILE="/var/config/.env"

# -------------------
# Copy .env.example if .env does not exist
# -------------------
if [ ! -f "$ENV_FILE" ]; then
    cp /var/config/.env.example "$ENV_FILE"
    echo ".env created from .env.example"
fi

# -------------------
# Read APP_KEY and strip spaces/CRLF
# -------------------
KEY_VALUE=$(grep -E '^APP_KEY=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r\n')

echo "APP_KEY value after cleanup: '$KEY_VALUE'"

if [ -z "$KEY_VALUE" ]; then
    echo "APP_KEY is empty or missing — generating..."
    php /var/www/artisan key:generate --ansi
else
    echo "APP_KEY already exists — skipping generation"
fi

# -------------------
# Set permissions for storage and bootstrap/cache
# -------------------
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# -------------------
# Install Composer if not found
# -------------------
if ! command -v composer >/dev/null 2>&1; then
    echo "Composer not found — installing..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# -------------------
# Install Composer dependencies if missing
# -------------------
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --optimize-autoloader
else
    echo "Composer dependencies already installed"
fi

# -------------------
# Wait for MySQL to become available
# -------------------
echo 'Waiting for MySQL via Laravel...'

until php /var/www/artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" >/dev/null 2>&1; do
    echo 'Waiting for MySQL...'
    sleep 2
done

# -------------------
# Run migrations (and seed if needed)
# -------------------
php /var/www/artisan migrate --force
# php /var/www/artisan db:seed --force   # Uncomment if seeds are needed

# -------------------
# Set Telegram webhook
# -------------------
BOT_TOKEN=$(grep -E '^TELEGRAM_BOT_TOKEN=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r\n')
SECRET_TOKEN=$(grep -E '^TELEGRAM_BOT_SECRET_TOKEN=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r\n')
APP_DOMAIN=$(grep -E '^APP_DOMAIN=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r\n')
WEBHOOK_URL="https://${APP_DOMAIN}/api/tg_webhook"

if [ -z "$BOT_TOKEN" ]; then
  echo "BOT_TOKEN not set in .env"
  exit 1
fi

if [ -z "$SECRET_TOKEN" ]; then
  SECRET_TOKEN=$(head /dev/urandom | tr -dc A-Za-z0-9 | head -c 32)
  echo "🔑 Генерируем TELEGRAM_BOT_SECRET_TOKEN..."
  # Удалим старую строку (если есть пустая) и добавим новую
  sed -i '/^TELEGRAM_BOT_SECRET_TOKEN=/d' .env
  echo "TELEGRAM_BOT_SECRET_TOKEN=${SECRET_TOKEN}" >> .env
  echo "✅ Новый токен добавлен в .env"
fi

echo "Setting Telegram webhook..."
curl -s -X POST "https://api.telegram.org/bot$BOT_TOKEN/setWebhook?url=$WEBHOOK_URL&secret_token=$SECRET_TOKEN"
echo "Webhook set to $WEBHOOK_URL"

# -------------------
# Background job loop: check tickets every 30 seconds
# -------------------
(
  LOG_FILE="/var/www/storage/logs/tickets.log"
  MAX_LINES=1000
  echo "🕒 Starting ticket check loop..." | tee -a "$LOG_FILE"

  while true; do
    TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$TIMESTAMP] Running check-quick-tickets..." | tee -a "$LOG_FILE"

    php /var/www/artisan app:check-quick-tickets >>"$LOG_FILE" 2>&1 || echo "[$TIMESTAMP] ⚠️ check-quick-tickets failed" >>"$LOG_FILE"

    # -------------------
    # Auto-truncate log to last MAX_LINES
    # -------------------
    if [ -f "$LOG_FILE" ]; then
      tail -n $MAX_LINES "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
    fi

    sleep 30
  done
) &


# -------------------
# Start PHP-FPM (main process)
# -------------------
exec php-fpm
