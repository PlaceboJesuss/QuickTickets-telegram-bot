FROM php:8.2-fpm

# -------------------
# Устанавливаем зависимости и PHP расширения
# -------------------
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip bash supervisor \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# -------------------
# Копируем Laravel в контейнер
# -------------------
WORKDIR /var/www/
# COPY ./laravel /var/www

# -------------------
# Копируем entrypoint и supervisor конфиг
# -------------------
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# -------------------
# Expose порт
# -------------------
EXPOSE 9000

# -------------------
# Запуск через entrypoint
# -------------------
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
