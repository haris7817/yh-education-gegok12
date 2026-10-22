FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    curl \
    zip \
    libxml2-dev \
    libicu-dev \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ----------------------------
# Node.js is intentionally NOT installed in the app (php-fpm) image.
# Frontend assets are built in the dedicated `node` service (node:24).
# This also removes a network-fragile NodeSource step from the build.
# ----------------------------

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Fix Git "dubious ownership" issue automatically
RUN git config --system --add safe.directory /var/www

# Ensure correct permissions for Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www

# Switch to www-data user (recommended for Laravel)
USER www-data
