#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/accounts_receivable"
PHP_BIN="/usr/bin/php"
WEB_SERVICE="nginx"
FPM_SERVICE="php8.3-fpm"
QUEUE_SERVICE="accounts-receivable-queue"
URL_CHECK="http://127.0.0.1"

# ---- helpers ----
hr() { echo "------------------------------------------------------------"; }
ok() { echo -e "✅ $*"; }
warn() { echo -e "⚠️  $*"; }
bad() { echo -e "❌ $*"; }

echo "Laravel Healthcheck @ $(date)"
echo "App: $APP_DIR"
hr

cd "$APP_DIR"

echo "== Services =="
for svc in "$WEB_SERVICE" "$FPM_SERVICE" "$QUEUE_SERVICE"; do
  if systemctl is-active --quiet "$svc"; then
    ok "$svc is active"
  else
    bad "$svc is NOT active"
    systemctl --no-pager -l status "$svc" | head -n 20 || true
  fi
done
hr

echo "== HTTP Check =="
if command -v curl >/dev/null 2>&1; then
  # sadece header
  HTTP_HEAD="$(curl -sS -I --max-time 5 "$URL_CHECK" || true)"
  if echo "$HTTP_HEAD" | grep -qE "^HTTP/"; then
    echo "$HTTP_HEAD" | head -n 12
    ok "HTTP reachable: $URL_CHECK"
  else
    bad "HTTP not reachable: $URL_CHECK"
  fi
else
  warn "curl not installed, skipping HTTP check"
fi
hr

echo "== Queue Failed Jobs =="
FAILED_OUT="$($PHP_BIN artisan queue:failed 2>&1 || true)"
if echo "$FAILED_OUT" | grep -q "No failed jobs found"; then
  ok "No failed jobs"
else
  warn "There are failed jobs:"
  echo "$FAILED_OUT"
fi
hr

echo "== Sync State + Counts (PostgreSQL) =="
# sync_state updated_at + credits/report counts (safe)
SYNC_JSON="$($PHP_BIN artisan tinker --execute="
\$k = app()->environment().'_credits_last_rv';
\$row = DB::connection('pgsql')->table('sync_state')->where('key', \$k)->first();
\$credits = DB::connection('pgsql')->table(config('sync.pgsql.credits_table'))->count();
\$avsho   = DB::connection('pgsql')->table(config('sync.pgsql.avshocrecat_table'))->count();
echo json_encode([
  'env' => app()->environment(),
  'state_key' => \$k,
  'state_value' => \$row?->value,
  'state_updated_at' => \$row?->updated_at,
  'credits_count' => \$credits,
  'avsho_count' => \$avsho,
], JSON_UNESCAPED_UNICODE);
" 2>/dev/null || true)"

if [[ -n "${SYNC_JSON}" ]] && echo "$SYNC_JSON" | grep -q "state_key"; then
  # json parse için basit grep (jq zorunlu değil)
  echo "$SYNC_JSON" | sed 's/[{}"]/ /g; s/,/\n/g' | sed 's/:/: /g'
  ok "Sync state fetched"
else
  warn "Could not fetch sync_state/counts (check DB creds/connection)"
fi
hr

echo "== Cron + Log =="
# www-data crontab içinde sync:run satırı var mı
CRON_LINE="$(sudo crontab -u www-data -l 2>/dev/null | grep -E 'artisan sync:run' || true)"
if [[ -n "$CRON_LINE" ]]; then
  ok "Cron found (www-data): $CRON_LINE"
else
  warn "Cron NOT found for www-data (artisan sync:run)"
fi

LOG_FILE="$APP_DIR/storage/logs/sync-cron.log"
if [[ -f "$LOG_FILE" ]]; then
  ok "sync-cron.log exists"
  echo "Last 15 lines:"
  tail -n 15 "$LOG_FILE"
else
  warn "sync-cron.log not found yet: $LOG_FILE"
fi
hr

echo "== Permissions (storage + bootstrap/cache) =="
# hızlı kontrol
STORAGE_OWNER="$(stat -c '%U:%G %a %n' "$APP_DIR/storage" 2>/dev/null || true)"
CACHE_OWNER="$(stat -c '%U:%G %a %n' "$APP_DIR/bootstrap/cache" 2>/dev/null || true)"
echo "$STORAGE_OWNER"
echo "$CACHE_OWNER"

# storage/framework/cache/data yazılabilir mi?
TEST_PATH="$APP_DIR/storage/framework/cache/healthcheck_write_test"
if sudo -u www-data bash -c "echo ok > '$TEST_PATH' 2>/dev/null"; then
  rm -f "$TEST_PATH" || true
  ok "www-data can write to storage/framework/cache"
else
  warn "www-data cannot write to storage/framework/cache (Cache::lock may fail)"
fi
hr

echo "== Disk & Logs Size =="
df -h "$APP_DIR" | tail -n 1
# log boyutları
du -h "$APP_DIR/storage/logs" 2>/dev/null | tail -n 5 || true
hr

echo "✅ Healthcheck completed."