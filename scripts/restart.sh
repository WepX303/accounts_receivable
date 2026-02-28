#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/accounts_receivable"
PHP_BIN="/usr/bin/php"
FPM_SERVICE="php8.3-fpm"
QUEUE_SERVICE="accounts-receivable-queue"
WEB_SERVICE="nginx"
APP_USER="www-data"
APP_GROUP="www-data"

echo "==> [1/8] Go to app dir: $APP_DIR"
cd "$APP_DIR"

echo "==> [2/8] Put app into maintenance mode (optional)"
# İstersen aktif et (kısa kesinti için)
$PHP_BIN artisan down --render="errors::503" 2>/dev/null || true

echo "==> [3/8] Fix permissions (storage + bootstrap/cache)"
sudo chown -R "$APP_USER:$APP_GROUP" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
sudo find "$APP_DIR/storage" -type d -exec chmod 775 {} \;
sudo find "$APP_DIR/storage" -type f -exec chmod 664 {} \;
sudo find "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;
sudo find "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} \;

echo "==> [4/8] Clear & rebuild caches"
$PHP_BIN artisan config:clear || true
$PHP_BIN artisan cache:clear || true
$PHP_BIN artisan route:clear || true
$PHP_BIN artisan view:clear || true

# Production’da genelde cache’lemek iyi:
$PHP_BIN artisan config:cache || true
$PHP_BIN artisan route:cache || true
$PHP_BIN artisan view:cache || true

echo "==> [5/8] Run migrations (force)"
$PHP_BIN artisan migrate --force

echo "==> [6/8] Restart services (php-fpm, queue, nginx)"
sudo systemctl restart "$FPM_SERVICE"
sudo systemctl restart "$QUEUE_SERVICE"

sudo nginx -t
sudo systemctl reload "$WEB_SERVICE"

echo "==> [7/8] Reset PHP opcache (if enabled)"
# opcache_reset sadece FPM’de etkili olur, bu CLI çağrı sembolik.
# Yine de deniyoruz:
$PHP_BIN -r 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache_reset OK\n"; } else { echo "opcache not enabled\n"; }' || true

echo "==> [8/8] Bring app up"
$PHP_BIN artisan up 2>/dev/null || true

echo ""
echo "✅ Done."
echo "==> Quick status:"
sudo systemctl --no-pager -l status "$WEB_SERVICE" | head -n 10 || true
sudo systemctl --no-pager -l status "$FPM_SERVICE" | head -n 10 || true
sudo systemctl --no-pager -l status "$QUEUE_SERVICE" | head -n 12 || true

echo ""
echo "==> Recent cron sync log (last 20 lines):"
tail -n 20 "$APP_DIR/storage/logs/sync-cron.log" 2>/dev/null || echo "(sync-cron.log not found yet)"