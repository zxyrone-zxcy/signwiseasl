#!/bin/sh
set -e
cd /app

# SQLite lives inside the container and is NOT durable: a fresh file is migrated and seeded on every start.
# Point DB_CONNECTION at a hosted database (e.g. Postgres) to keep data between restarts.
if [ "$DB_CONNECTION" = "sqlite" ]; then
  if [ ! -s "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
    php artisan migrate --force --no-interaction
    php artisan db:seed --force --no-interaction
  else
    php artisan migrate --force --no-interaction
  fi
else
  php artisan migrate --force --no-interaction
fi

php artisan config:cache
php artisan view:cache

exec frankenphp run --config /etc/caddy/Caddyfile
