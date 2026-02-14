# ---- Stage 1: Build Laravel + Vite ----
FROM php:8.4-fpm AS builder

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y \
    git zip unzip curl libpq-dev nodejs npm postgresql-client \
    && docker-php-ext-install pdo pdo_pgsql opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy project files
COPY . .

# Copy custom php.ini
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies
RUN npm ci

# Build Vite assets (Wayfinder will run because PHP exists)
RUN npm run build

# ---- Stage 2: Production image ----
FROM php:8.4-fpm

# Install PHP extensions only
RUN apt-get update && apt-get install -y libpq-dev postgresql-client \
    && docker-php-ext-install pdo pdo_pgsql opcache

WORKDIR /var/www

# Copy built Laravel app from builder
COPY --from=builder /var/www /var/www

# Ensure permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Copy custom php.ini
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy entrypoint
COPY ./docker/start.sh /start.sh
RUN chmod +x /start.sh

# Copy scheduler entrypoint
COPY ./docker/schedule.sh /schedule.sh
RUN chmod +x /schedule.sh

ENTRYPOINT ["/start.sh"]
