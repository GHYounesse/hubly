# Demo / development image: PHP 8.3 + the built Tailwind CSS.
# For production, use a real web server (nginx + php-fpm) instead of PHP's built-in one.

# ---- Stage 1: compile the Tailwind CSS ---------------------------------------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY tailwind.config.js ./
COPY assets ./assets
COPY templates ./templates
RUN mkdir -p public/build && npm run build

# ---- Stage 2: the application -----------------------------------------------------
FROM php:8.3-cli-alpine
RUN apk add --no-cache git unzip icu-dev libpq-dev postgresql-client \
    && docker-php-ext-install intl pdo_pgsql opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

COPY composer.json composer.lock* symfony.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN cp .env.example .env \
    && composer dump-autoload --optimize \
    && chmod +x docker/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
