#!/bin/sh
set -e

# Render (and many PaaS) inject $PORT at runtime. Default to 8000.
PORT="${PORT:-8000}"

# Replace the placeholder in the nginx template
sed -e "s/\${PORT}/${PORT}/g" /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Ensure Laravel storage is writable (in case of mounted volumes or restarts)
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Laravel runtime optimizations.
# NOTE: On Render, env vars are injected at runtime, so we cache here in the entrypoint.
# If you change env vars frequently without redeploying, remove config:cache.
php artisan config:cache --ansi || true
php artisan route:cache --ansi || true
php artisan view:cache --ansi || true

# Database migrations (idempotent, safe to run on every boot)
echo "Running database migrations..."
php artisan migrate --force --ansi

# Optional: seed on first run only. Uncomment if you want auto-seeding.
# php artisan db:seed --force --ansi || true

echo "Starting services on port ${PORT}..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
