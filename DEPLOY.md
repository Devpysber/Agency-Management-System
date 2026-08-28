# Deploying to psyber.in (Ubuntu VPS + MySQL)

Target: Hostinger VPS `srv1891796.hstgr.cloud`, root SSH, plain Ubuntu.
DNS: **psyber.in is on Cloudflare** — keep it there, do **not** switch nameservers
to Hostinger.

---

## 1. DNS (Cloudflare dashboard, not Hostinger)

Get the VPS public IP from Hostinger → VPS → Overview.

In Cloudflare → **DNS → Records**:

| Type | Name | Content | Proxy |
|------|--------|-------------|--------------------------|
| A | `psyber.in` (`@`) | `<VPS_IP>` | **DNS only** (grey) for now |
| A | `www` | `<VPS_IP>` | **DNS only** (grey) for now |

Grey-cloud first so `certbot` HTTP-01 works and you see real errors. After HTTPS
is confirmed you can flip to **Proxied** (orange) and set Cloudflare
**SSL/TLS mode = Full (strict)**.

Then in Cloudflare:
- **Security → Settings → turn OFF "Under Attack Mode"** (currently ON — it shows
  every visitor an interstitial and makes the site look dead).
- Ignore Hostinger's "connect domain / change nameservers to
  hermes/artemis.dns-parking.com" prompt. Not needed; Cloudflare stays the DNS host.

---

## 2. Server — first time

```bash
ssh root@<VPS_IP>
git clone https://github.com/Devpysber/Agency-Management-System.git /var/www/agency
cd /var/www/agency
bash deploy/first-setup.sh
```

`first-setup.sh` installs nginx + PHP 8.3-FPM + MySQL + Node 20 + Composer +
Supervisor, creates the `agency` database, writes `.env` from
`.env.production.example`, runs `composer install --no-dev`, `npm run build`,
`migrate --force`, wires the nginx vhost, the queue worker (Supervisor) and the
scheduler cron. It pauses for the MySQL password.

After it finishes:

```bash
# SSL
apt-get install -y certbot python3-certbot-nginx
certbot --nginx -d psyber.in -d www.psyber.in --redirect -m admin@psyber.in --agree-tos -n

# First admin login (pick one)
php artisan tinker --execute="\App\Models\User::create(['name'=>'Admin','email'=>'admin@psyber.in','password'=>bcrypt('CHANGE_ME'),'role'=>'admin']);"
#   or full demo data (companies, staff, deals, test@example.com / password):
php artisan db:seed --force
```

Log in at `https://psyber.in/`.

---

## 3. Redeploy after every push

```bash
bash /var/www/agency/deploy/update.sh
```
Pulls `origin/main`, reinstalls deps, rebuilds assets, migrates, rebuilds caches,
restarts the worker and PHP-FPM.

---

## Files in `deploy/`

| File | Goes to |
|------|---------|
| `first-setup.sh` | run once on the VPS |
| `update.sh` | run on each redeploy |
| `nginx-psyber.conf` | `/etc/nginx/sites-available/agency` (script copies it) |
| `agency-worker.conf` | `/etc/supervisor/conf.d/` (script copies it) |
| `../.env.production.example` | copied to `.env` by the script |

---

## Notes / gotchas

- **PHP**: app needs `^8.2`; the scripts use 8.3 from `ppa:ondrej/php`. If
  `composer install` ever complains about the platform, add
  `--ignore-platform-req=php`.
- **Blade filenames** carry a literal `⚡` (U+26A1). `git config core.quotepath off`
  is set by the script; Linux checks them out fine.
- **`APP_DEBUG=false`** is mandatory in prod (`.env.production.example` sets it).
- **Uploads 404** → `php artisan storage:link` didn't run or `storage/` isn't
  writable by `www-data`.
- **Queue**: `QUEUE_CONNECTION=database`; the Supervisor `agency-worker` program
  must be running or queued jobs (welcome alerts, etc.) never fire.
- **Scheduler**: the `* * * * * php artisan schedule:run` cron (added for
  `www-data`) drives the attendance auto-absence sweep.
- **Sessions**: `SESSION_SECURE_COOKIE=true` — only works once HTTPS is live.
- Do **not** commit the real `.env`. It stays server-only.
