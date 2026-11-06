# Laravel (PHP 8.4) on FrankenPHP for the Vercel container runtime.

# ---- Build the Vite assets (no PHP needed) ----
FROM node:22-alpine AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---- PHP runtime ----
FROM dunglas/frankenphp:1-php8.4-alpine AS base
WORKDIR /app
RUN install-php-extensions pdo_sqlite pdo_mysql pdo_pgsql opcache zip

FROM base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

FROM base AS runtime
COPY --from=dependencies /app/vendor ./vendor
COPY . .
COPY --from=assets /build/public/build ./public/build
COPY Caddyfile /etc/caddy/Caddyfile
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Drop any compiled caches that came from a developer machine (they list dev-only packages such as Boost),
# then regenerate everything against the production-only vendor directory.
RUN rm -f bootstrap/cache/*.php \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
    && composer dump-autoload --no-dev --optimize --no-scripts --no-interaction \
    && php artisan package:discover --ansi \
    && chmod -R 777 storage bootstrap/cache database

# Sensible production defaults; override any of these in the Vercel project's environment variables.
# APP_KEY must be set there (php artisan key:generate --show).
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=cookie \
    CACHE_STORE=array \
    QUEUE_CONNECTION=sync \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/database/database.sqlite \
    PORT=80

COPY docker/start.sh /usr/local/bin/start-app
RUN chmod +x /usr/local/bin/start-app
CMD ["start-app"]
