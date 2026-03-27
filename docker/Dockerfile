FROM php:8.2-fpm
RUN docker-php-ext-install opcache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress
COPY . .
