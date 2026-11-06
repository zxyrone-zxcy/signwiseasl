#!/bin/sh
set -e
cd /app

# SQLite lives inside the container and is NOT durable: a fresh file is created on every start.
# Set DB_CONNECTION=pgsql (e.g. Supabase) to keep data between restarts.
if [ "$DB_CONNECTION" = "sqlite" ] && [ ! -s "$DB_DATABASE" ]; then
  touch "$DB_DATABASE"
fi

php artisan migrate --force --no-interaction

# Lessons only (idempotent: matched by title). The demo-learner seeder is deliberately NOT run here,
# because it creates an admin account with a known password.
php artisan db:seed --class=LessonSeeder --force --no-interaction

php artisan config:cache
php artisan view:cache

exec frankenphp run --config /etc/caddy/Caddyfile
