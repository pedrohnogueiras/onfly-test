#!/bin/sh
set -e

cd /var/www/html

echo "Starting Laravel container..."

mkdir -p \
    storage/logs \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache

if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -f "artisan" ]; then
    echo "Fixing Laravel permissions..."
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache

    if grep -q "APP_KEY=$" .env 2>/dev/null || ! grep -q "^APP_KEY=" .env 2>/dev/null; then
        echo "Generating application key..."
        php artisan key:generate --force
    fi

    echo "Clearing Laravel cache..."
    php artisan optimize:clear || true

    if [ "$RUN_MIGRATIONS" = "true" ]; then
        echo "Running database migrations..."
        php artisan migrate --force || true
        php artisan db:seed
    fi

    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache
fi

exec "$@"