FROM php:8.2-fpm

# -------------------
# Устанавливаем системные зависимости и PHP расширения
# -------------------
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip \
    && docker-php-ext-install pdo_mysql

# -------------------
# Копируем Laravel в контейнер
# -------------------
WORKDIR /var/www/
COPY ./laravel /var/www
COPY ./.env /var/config/.env


# -------------------
# Устанавливаем Composer и зависимости
# -------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader

# -------------------
# Права на storage и bootstrap/cache
# -------------------
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

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
