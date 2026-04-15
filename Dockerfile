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

# Build assets
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

# Setup user
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Copy application code
COPY --chown=www:www . .

# Copy vendor from deps stage
COPY --chown=www:www --from=deps /var/www/vendor /var/www/vendor

# Copy built assets from assets stage
COPY --chown=www:www --from=assets /var/www/public/build /var/www/public/build

# Run composer autoloader and scripts
RUN composer dump-autoload --no-dev --optimize

# Change current user to www
USER www

# Expose port 9000
EXPOSE 9000

CMD ["php-fpm"]
