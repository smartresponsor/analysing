FROM php:8.4-fpm
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev unzip git libonig-dev libsqlite3-dev \
    && docker-php-ext-install opcache mbstring pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress
COPY . .
