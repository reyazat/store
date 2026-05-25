#!/bin/sh
set -e

cd /var/www/html

echo "Setting up storage directories..."
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create storage link only if doesn't exist
if [ ! -L "public/storage" ]; then
    echo "Creating storage link..."
    php artisan storage:link --no-interaction 2>/dev/null || true
fi

# Cache only in production and when APP_KEY is available
if [ "${APP_ENV:-production}" != "local" ] && [ -n "${APP_KEY:-}" ]; then
    echo "Caching Laravel configuration..."
    php artisan optimize:clear --no-interaction 2>/dev/null || true
    php artisan optimize --no-interaction 2>/dev/null || true
elif [ "${APP_ENV:-production}" != "local" ]; then
    echo "Warning: APP_KEY not set. Skipping optimization."
fi

# Run migrations if requested (disabled by default)
if [ "${RUN_MIGRATIONS:-false}" = "true" ] && [ -n "${APP_KEY:-}" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction || true
fi

echo "Entrypoint setup complete."
exec "$@"
