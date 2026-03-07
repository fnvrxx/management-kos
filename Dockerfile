FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libicu-dev \
    libpng-dev libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install \
    pdo pdo_pgsql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN rm -f .env

RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8080

ENTRYPOINT ["/bin/sh", "-c"]
CMD ["php artisan config:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]