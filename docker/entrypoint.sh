#!/bin/sh
set -e

# Wait for the DB to accept connections (up to ~60s) before migrating.
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    for i in $(seq 1 30); do
        if php -r "new PDO('mysql:host=$DB_HOST;port=${DB_PORT:-3306}', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; then
            echo "Database is up."
            break
        fi
        sleep 2
    done
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# public/storage isn't part of the image (it's a symlink into the
# storage_data volume) so it has to be (re)created per-container; without
# it every uploaded file — staff photos, portfolio images, blog/testimonial
# images, settings logo/favicon — 404s even though the upload itself works.
[ -L public/storage ] || php artisan storage:link

if [ "$RUN_MIGRATIONS" != "false" ]; then
    php artisan migrate --force
fi

exec "$@"
