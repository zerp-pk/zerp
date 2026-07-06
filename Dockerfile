# Build context is the PARENT directory of this repo (see docker-compose.yml),
# so both zerp/ and the sibling ZerpPackages/ (module packages) are visible —
# composer.json's path repositories resolve to ../ZerpPackages/<module> from
# this repo's root, i.e. /ZerpPackages inside these build stages.

########################################
# Stage 1: PHP/Composer dependencies
########################################
FROM composer:2 AS vendor
WORKDIR /app
COPY ZerpPackages /ZerpPackages
COPY zerp/composer.json zerp/composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --ignore-platform-reqs

########################################
# Stage 2: frontend assets (module JS entries are discovered via
# vendor/zerp/*/src/Resources/js/app.tsx, so vendor/ must exist here too)
########################################
FROM node:20-slim AS assets
WORKDIR /app
COPY ZerpPackages /ZerpPackages
COPY zerp/package.json zerp/package-lock.json* ./
RUN npm install
COPY zerp/ ./
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

########################################
# Stage 3: runtime image
########################################
FROM php:8.2-apache AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev git unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Laravel's public/ is the document root, not the repo root.
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public/!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY ZerpPackages /ZerpPackages
COPY zerp/ ./
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
