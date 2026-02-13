FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    nodejs \
    npm \
    postgresql-client \
    libpq-dev \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif bcmath gd zip

# Enable Apache rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy app
COPY . .

# Composer runs as root inside the container
ENV COMPOSER_ALLOW_SUPERUSER=1

# ✅ FIX PERMISSIONS FIRST
# Make sure folders exist first
RUN mkdir -p /var/www/html/bootstrap/cache \
    /var/www/html/storage/logs \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 /var/www/html/storage \
 && chmod -R 775 /var/www/html/bootstrap/cache


# ✅ INSTALL DEV PACKAGES (IMPORTANT)
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Frontend
RUN npm ci
RUN npm run build \
 && npm cache clean --force \
 && rm -rf node_modules

# Apache public folder
RUN sed -i 's|/var/www/html|/var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD php artisan migrate --force \
    && php artisan db:seed --class=AgenciesSeeder --force \
    && php artisan db:seed --class=OfficesSeeder --force \
    && apache2-foreground
