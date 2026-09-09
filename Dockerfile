FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY public public
COPY vite.config.js ./
RUN npm run build

FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.2-apache
WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev libonig-dev \
    && docker-php-ext-install bcmath intl mbstring opcache pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY docker/entrypoint.sh docker/start-worker.sh docker/run-scheduler.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/scolaris-entrypoint /usr/local/bin/start-worker.sh /usr/local/bin/run-scheduler.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php artisan about --only=environment >/dev/null || exit 1
ENTRYPOINT ["scolaris-entrypoint"]
CMD ["apache2-foreground"]
