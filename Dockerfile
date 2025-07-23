FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    zip unzip curl libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo_mysql mysqli mbstring zip exif pcntl posix

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
