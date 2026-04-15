# Stage 1: Build PHP dependencies
FROM php:8.4-fpm-alpine AS deps

WORKDIR /var/www

RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ----------------------------

# Stage 2: Build frontend assets
FROM node:22-alpine AS assets

WORKDIR /var/www

COPY package.json package-lock.json ./

RUN npm install

COPY . .

COPY --from=deps /var/www/vendor /var/www/vendor

RUN npm run build

# ----------------------------

# Stage 3: Production (FrankenPHP)
FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev

RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Copy dependencies
COPY --from=deps /var/www/vendor ./vendor

# Copy built assets
COPY --from=assets /var/www/public/build ./public/build

# Optimize Laravel
RUN composer dump-autoload --no-dev --optimize

# Clear cache
RUN php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear

# Permission
RUN chown -R www-data:www-data storage bootstrap/cache

# Environment
ENV SERVER_NAME=:80
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

EXPOSE 80