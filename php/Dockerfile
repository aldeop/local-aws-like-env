FROM php:8.2-fpm

# Install system dependencies and common PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install pdo pdo_mysql zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY src/ /var/www/html/

