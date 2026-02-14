#!/bin/sh

echo "Waiting for database..."

# Wait for PostgreSQL to be ready
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-laracord}" >/dev/null 2>&1; do
  echo "Database not ready, retrying in 3s..."
  sleep 3
done

php artisan migrate --force

# Ensure APP_KEY exists (generate once if missing)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
  php artisan key:generate --force
fi

# Cache Laravel config for production only
if [ "${APP_ENV}" = "production" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

# Start PHP-FPM
exec php-fpm
