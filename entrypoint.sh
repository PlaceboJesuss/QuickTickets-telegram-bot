#!/bin/bash
set -e
set -x 

ENV_FILE="/var/config/.env"

# -------------------
# Если .env нет — копируем пример
# -------------------
if [ ! -f "$ENV_FILE" ]; then
    cp /var/config/.env.example "$ENV_FILE"
    echo "Создан .env из .env.example"
fi

# -------------------
# Получаем значение APP_KEY и убираем пробелы/CRLF
# -------------------
KEY_VALUE=$(grep -E '^APP_KEY=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '\r\n')

echo "Значение APP_KEY после очистки: '$KEY_VALUE'"

if [ -z "$KEY_VALUE" ]; then
    echo "APP_KEY пустой или отсутствует — генерируем"
    php /var/www/artisan key:generate --ansi
else
    echo "APP_KEY уже есть — генерация не нужна"
fi

# -------------------
# Права на storage и bootstrap/cache
# -------------------
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache


# Устанавливаем Composer, если его нет
if ! command -v composer >/dev/null 2>&1; then
    echo "Composer не найден — устанавливаем..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# -------------------
# Установка зависимостей Composer
# -------------------
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "Устанавливаем зависимости Composer..."
    composer install --no-interaction --optimize-autoloader
else
    echo "Composer dependencies уже установлены"
fi

# -------------------
# Ждём, пока MySQL станет доступен
# -------------------
echo "Ожидание MySQL..."
until php /var/www/artisan migrate:status >/dev/null 2>&1; do
    sleep 2
done

# -------------------
# Выполняем миграции (и сиды при необходимости)
# -------------------
php /var/www/artisan migrate --force
# php /var/www/artisan db:seed --force   # Раскомментировать, если нужны сиды

# -------------------
# Установка Telegram webhook
# -------------------
BOT_TOKEN=${TELEGRAM_BOT_TOKEN}
PUBLIC_IP=$(curl -s https://ifconfig.me)
WEBHOOK_URL="https://${PUBLIC_IP}/tg_webhook"

if [ -z "$BOT_TOKEN" ]; then
  echo "BOT_TOKEN не задан в .env"
  exit 1
fi

echo "Setting Telegram webhook..."
curl -s -X POST "https://api.telegram.org/bot$BOT_TOKEN/setWebhook?url=$WEBHOOK_URL"
echo "Webhook set to $WEBHOOK_URL"

# -------------------
# Запуск PHP-FPM
# -------------------
exec php-fpm
