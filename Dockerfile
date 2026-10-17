# Runs the app with PHP's built-in server. Fine for review; production would
# put php-fpm behind nginx instead.
FROM php:8.4-cli-alpine

# unzip and git let Composer fetch packages; nothing else is needed (curl, json,
# mbstring and openssl are built into the official image).
RUN apk add --no-cache unzip git
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first so this layer is reused when only app code changes.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist --no-progress

COPY . .
RUN composer dump-autoload --optimize \
    && cp .env.example .env \
    && php artisan key:generate --force

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
