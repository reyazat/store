# syntax=docker/dockerfile:1

# -------------------------
# Stage 1: Composer dependencies
# -------------------------
FROM php:8.3-cli-alpine AS composer_deps

WORKDIR /app

# Install build dependencies and PHP extensions required by composer packages
RUN apk add --no-cache \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    libxml2 \
    unzip \
    git \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    bcmath \
    gd \
    exif \
    pcntl \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Copy local packages (required for path repositories)
COPY packages/ ./packages/

# Install production dependencies
# --no-scripts is used because scripts may fail without full app context
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy full application and generate optimized autoloader
COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative


# -------------------------
# Stage 2: Frontend build (Node/Vite)
# -------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

# Copy package files for better layer caching
COPY package*.json ./
COPY vite.config.ts ./
COPY tsconfig*.json ./

# Install dependencies
RUN npm ci

# Copy source files
COPY resources/ ./resources/
COPY --from=composer_deps /app/vendor ./vendor

# Build frontend assets
RUN npm run build


# -------------------------
# Stage 3: Runtime (PHP-FPM + Nginx)
# -------------------------
FROM php:8.3-fpm-alpine AS runtime

WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    libxml2 \
    mariadb-client \
    tzdata \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    bcmath \
    gd \
    exif \
    opcache \
    pcntl \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Copy application from composer stage
COPY --from=composer_deps --chown=www-data:www-data /app /var/www/html

# Copy built frontend assets
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Copy configuration files
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Set permissions, create required directories and create Laravel storage link
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /run/nginx /var/log/supervisor \
    && mkdir -p storage/app/public \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p bootstrap/cache \
    && rm -rf public/storage \
    && ln -s ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache public/build \
    && chown -h www-data:www-data public/storage \
    && chmod -R 775 storage bootstrap/cache

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:80/health || exit 1

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
