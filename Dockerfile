# Multi-stage Dockerfile for Laravel (PHP 8.4 FPM + Nginx in one image)
# - Builds Composer deps and Vite assets during image build
# - Runs php-fpm and nginx via supervisord

###############################################
# Stage 1: PHP dependencies (Composer install)
###############################################
FROM php:8.4-cli-alpine AS composer_deps

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PATH="/root/.composer/vendor/bin:${PATH}"

# Install required build deps for composer (git, zip)
RUN apk add --no-cache git zip unzip gmp libzip \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS libzip-dev gmp-dev

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Ensure required PHP extensions for composer
RUN docker-php-ext-configure zip \
    && docker-php-ext-install -j$(nproc) zip gmp \
    && apk del .build-deps

# Copy full application so composer scripts (artisan) can run
COPY . .

# Ensure Laravel required writable directories exist for composer scripts
RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data

# Install PHP dependencies without dev for production (scripts enabled)
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-progress \
    --no-interaction \
    --optimize-autoloader

###############################################
# Stage 2: Frontend build (Vite)
###############################################
FROM node:20-alpine AS frontend_build

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
# Build assets (expects Vite config to output to public/build)
RUN npm run build

###############################################
# Stage 3: Runtime (PHP-FPM 8.4 + Nginx + Supervisor)
###############################################
FROM php:8.4-fpm-alpine AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    PHP_MEMORY_LIMIT=256M \
    PHP_MAX_EXECUTION_TIME=60 \
    PHP_POST_MAX_SIZE=32M \
    PHP_UPLOAD_MAX_FILESIZE=32M

# System packages and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    git \
    unzip zip \
    tzdata \
    icu-data-full icu-libs \
    libpng libjpeg-turbo freetype \
    oniguruma \
    libzip \
    gmp \
    shadow \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev libzip-dev gmp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
       bcmath opcache intl gd gmp zip mbstring pdo pdo_mysql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Composer in runtime for future installs
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Configure PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-custom.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Configure Nginx
RUN mkdir -p /run/nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Configure Supervisor
COPY docker/supervisord.conf /etc/supervisord.conf

WORKDIR /var/www/html

# Copy application code
COPY . .

# Overwrite vendor with production deps from composer stage
COPY --from=composer_deps /app/vendor ./vendor

# Copy built frontend assets
COPY --from=frontend_build /app/public/build ./public/build

# Ensure correct permissions for Laravel writable dirs
RUN usermod -u 1000 www-data || true \
    && groupmod -g 1000 www-data || true \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && find storage -type d -exec chmod 775 {} \; \
    && find storage -type f -exec chmod 664 {} \; \
    && chmod -R 775 bootstrap/cache

# Regenerate autoloads in the final path to avoid path mismatches
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload -o

# HTTP endpoint for a host port or reverse proxy
EXPOSE 8080

# Healthcheck: verify nginx responds
HEALTHCHECK --interval=30s --timeout=3s --retries=3 CMD curl -fsS http://127.0.0.1:8080/up || exit 1

RUN chmod +x docker/entrypoint.sh
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]

# Start supervisor which launches php-fpm and nginx
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
