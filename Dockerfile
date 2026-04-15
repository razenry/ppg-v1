# Stage 1: Build PHP dependencies
FROM php:8.4-fpm-alpine AS deps

WORKDIR /var/www

# Install system dependencies for composer
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ---

# Stage 2: Build frontend assets
FROM node:22-alpine AS assets

WORKDIR /var/www

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies
RUN npm install

# Copy application code for asset building (needs views for Tailwind)
COPY . .

# Build assets (needs vendor for Tailwind v4)
COPY --from=deps /var/www/vendor /var/www/vendor
RUN npm run build

# ---

# Stage 3: Final Production Image with FrankenPHP
FROM dunglas/frankenphp:1-php8.4-alpine AS production

WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd intl

# Install Composer (needed for dump-autoload)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . .

# Copy vendor from deps stage
COPY --from=deps /var/www/vendor ./vendor

# Copy built assets from assets stage
COPY --from=assets /var/www/public/build ./public/build

# Run composer autoloader and scripts
RUN composer dump-autoload --no-dev --optimize

# Production configuration
RUN cp .env.example .env && \
    php artisan key:generate

# FrankenPHP configuration
ENV SERVER_NAME=:80
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Setup permissions
RUN chown -R root:root . && \
    chmod -R 755 . && \
    chown -R www-data:www-data storage bootstrap/cache

# Change user if needed, but FrankenPHP often runs as root to bind port 80
# and then drops privileges or uses separate worker users.
# For Alpine, it uses www-data.

# Expose port 80
EXPOSE 80
EXPOSE 443

# Entrypoint is handled by FrankenPHP image
