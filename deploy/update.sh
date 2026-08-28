#!/usr/bin/env bash
# Redeploy after a push.  Run as root (or a deploy user with sudo):
#   bash /var/www/agency/deploy/update.sh
set -euo pipefail
cd /var/www/agency

php artisan down --render="errors::503" || true

git fetch --all
git reset --hard origin/main

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

supervisorctl restart agency-worker:* || true
systemctl reload php8.3-fpm

php artisan up
echo "==> update complete"
