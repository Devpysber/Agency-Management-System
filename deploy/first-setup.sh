#!/usr/bin/env bash
# First-time provisioning for psyber.in on a fresh Ubuntu 22.04/24.04 VPS.
# Run as root:  bash deploy/first-setup.sh
# It STOPS at the manual steps (MySQL password, .env secrets) — read the prompts.
set -euo pipefail

APP_DIR=/var/www/agency
REPO=https://github.com/Devpysber/Agency-Management-System.git
PHP=8.3

echo "==> [1/8] Base packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y software-properties-common curl git unzip
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y nginx mysql-server supervisor \
  php${PHP}-fpm php${PHP}-cli php${PHP}-mysql php${PHP}-mbstring php${PHP}-xml \
  php${PHP}-curl php${PHP}-zip php${PHP}-gd php${PHP}-bcmath php${PHP}-intl

echo "==> [2/8] Composer"
if ! command -v composer >/dev/null; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "==> [3/8] Node 20"
if ! command -v node >/dev/null || [[ "$(node -v)" != v20* ]]; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y nodejs
fi

echo "==> [4/8] MySQL database + user"
read -rsp "   New password for MySQL user 'agency': " DBPASS; echo
mysql <<SQL
CREATE DATABASE IF NOT EXISTS agency CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'agency'@'localhost' IDENTIFIED BY '${DBPASS}';
GRANT ALL PRIVILEGES ON agency.* TO 'agency'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> [5/8] Clone code"
mkdir -p /var/www
if [[ ! -d "$APP_DIR/.git" ]]; then
  git clone "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"
git config core.quotepath off

echo "==> [6/8] .env"
if [[ ! -f .env ]]; then
  cp .env.production.example .env
  sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DBPASS}|" .env
fi
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force

echo "==> [7/8] Build + migrate"
npm ci
npm run build
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> [8/8] Permissions + services"
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

cp deploy/nginx-psyber.conf /etc/nginx/sites-available/agency
ln -sf /etc/nginx/sites-available/agency /etc/nginx/sites-enabled/agency
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

cp deploy/agency-worker.conf /etc/supervisor/conf.d/agency-worker.conf
supervisorctl reread
supervisorctl update
supervisorctl start agency-worker:* || true

( crontab -u www-data -l 2>/dev/null | grep -v 'artisan schedule:run' ; \
  echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1" ) \
  | crontab -u www-data -

cat <<DONE

==> Base deploy done. Site should answer on http://psyber.in once DNS points here.
    Next:
      1. Cloudflare DNS: A psyber.in -> THIS_VPS_IP  (grey cloud / DNS only for now)
                         A www       -> THIS_VPS_IP
      2. Turn OFF "Under Attack Mode" in Cloudflare (Security > Settings).
      3. SSL:  apt-get install -y certbot python3-certbot-nginx
               certbot --nginx -d psyber.in -d www.psyber.in --redirect \\
                 -m admin@psyber.in --agree-tos -n
      4. Create the first admin:
               php artisan tinker --execute="\\App\\Models\\User::create(['name'=>'Admin','email'=>'admin@psyber.in','password'=>bcrypt('CHANGE_ME'),'role'=>'admin']);"
         (or:  php artisan db:seed --force   for full demo data)
DONE
