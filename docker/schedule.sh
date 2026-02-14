#!/bin/sh

echo "Waiting for database..."

# Wait for PostgreSQL to be ready
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-pastecord}" >/dev/null 2>&1; do
  echo "Database not ready, retrying in 3s..."
  sleep 3
done

# Ensure APP_KEY exists (generate once if missing)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
  php artisan key:generate --force
fi

# Run the scheduler worker
exec php artisan schedule:work
