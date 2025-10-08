FROM php:8.2-fpm

# -------------------
# Устанавливаем системные зависимости и PHP расширения
# -------------------
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip bash \
    && docker-php-ext-install pdo_mysql

# -------------------
# Копируем Laravel в контейнер
# -------------------
WORKDIR /var/www/
COPY ./laravel /var/www
COPY ./.env /var/config/.env

# -------------------
# Копируем entrypoint для инициализации Laravel
# -------------------
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# -------------------
# Expose порт
# -------------------
EXPOSE 9000

# -------------------
# Запуск через entrypoint
# -------------------
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
