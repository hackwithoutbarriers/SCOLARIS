FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources resources
COPY public public
COPY vite.config.js ./

RUN npm run build


FROM php:8.4-apache

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        libpq-dev \
        libxml2-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        dom \
        intl \
        mbstring \
        opcache \
        pdo_pgsql \
        zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts \
    && composer clear-cache

COPY --from=frontend /app/public/build ./public/build

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/scolaris-entrypoint
COPY docker/start-worker.sh /usr/local/bin/start-worker.sh
COPY docker/run-scheduler.sh /usr/local/bin/run-scheduler.sh

RUN sed -i 's/\r$//' \
        /usr/local/bin/scolaris-entrypoint \
        /usr/local/bin/start-worker.sh \
        /usr/local/bin/run-scheduler.sh \
    && chmod +x \
        /usr/local/bin/scolaris-entrypoint \
        /usr/local/bin/start-worker.sh \
        /usr/local/bin/run-scheduler.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php artisan about --only=environment >/dev/null || exit 1

ENTRYPOINT ["sh", "/usr/local/bin/scolaris-entrypoint"]
CMD ["apache2-foreground"]
