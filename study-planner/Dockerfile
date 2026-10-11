FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    git \
    unzip \
    && docker-php-ext-install pdo_pgsql pgsql mbstring bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock artisan ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .

RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache \
    && php artisan package:discover --ansi

EXPOSE 8000
