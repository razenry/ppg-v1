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

# Copy package files first (for npm install cache)
COPY package.json package-lock.json ./

# Install npm dependencies
RUN npm install

# Copy ALL application source (Blade views, CSS, JS) - any change here busts the build cache
COPY . .

# Copy vendor for Tailwind v4 (needs PHP package discovery)
COPY --from=deps /var/www/vendor /var/www/vendor

# Build assets - runs fresh whenever source files change
RUN npm run build

# ---

# Stage 3: Final Production Image
FROM php:8.4-fpm-alpine AS production

WORKDIR /var/www

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

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Setup permissions
RUN chown -R root:root . && \
    chmod -R 755 . && \
    chown -R www-data:www-data storage bootstrap/cache

# Change user to www-data for FPM
USER www-data

# Expose port 9000
EXPOSE 9000

CMD ["php-fpm"]
