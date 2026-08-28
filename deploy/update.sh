#!/usr/bin/env bash
# Redeploy after a push.  Run as root (or a deploy user with sudo):
#   bash /var/www/agency/deploy/update.sh
set -euo pipefail
cd /var/www/agency

# --- Stage 1: fast-forward the working tree, then re-exec the fresh script ----
# `git reset --hard` rewrites THIS file mid-run; bash keeps reading the old
# file descriptor by byte offset and executes a mangled hybrid. So do only the
# pull here, then hand off to the updated copy (guarded by a flag so we do it
# once).
if [ -z "${DEPLOY_REEXEC:-}" ]; then
    git fetch --all
    git reset --hard origin/main
    exec env DEPLOY_REEXEC=1 bash deploy/update.sh "$@"
fi

# --- Stage 2: the actual deploy (running from the just-pulled script) --------

# The web server runs as www-data. Every `php artisan` call that writes into
# storage/ or bootstrap/cache/ MUST run as www-data — a single root-owned file
# under storage/framework/views/livewire/ makes Livewire's runtime class
# compiler fail with "tempnam(): file created in the system's temporary
# directory" -> 500 on the affected page. Run artisan as www-data; chown at the
# very end as a safety net.
WWW=www-data
art() { sudo -u "$WWW" php artisan "$@"; }

art down --render="errors::503" || true

composer install --no-dev --optimize-autoloader --no-interaction

# Asset build. Peer-dep graph needs --legacy-peer-deps (vite 7 vs plugin-vue 5).
# A build failure must NOT abort the deploy and strand the site in maintenance
# mode — the previously built public/build assets stay serviceable.
( npm ci --legacy-peer-deps && npm run build ) || echo "WARN: asset build failed, keeping existing public/build"

art migrate --force

art config:cache
art route:cache
art view:cache
art event:cache || true

supervisorctl restart agency-worker:* || true
systemctl reload php8.5-fpm || systemctl reload 'php*-fpm' || true

art up

# Safety net: everything above ran as www-data, but re-assert ownership and
# setgid so nothing can be left root-owned or group-unwritable.
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod g+s {} +

echo "==> update complete"
