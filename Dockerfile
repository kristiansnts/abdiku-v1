# Stage 1: Install PHP dependencies
FROM php:8.4-cli-alpine AS composer

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install required extensions for composer install
RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    && docker-php-ext-install intl zip

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

COPY . .

RUN composer run-script post-autoload-dump 2>/dev/null || true

# Stage 2: Build frontend assets (after composer so we can copy pagination views)
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

# Copy all files needed for Tailwind CSS v4 to scan
COPY resources/ ./resources/
COPY vite.config.js ./
COPY postcss.config.js* ./
COPY tailwind.config.js* ./

# Copy Blade templates for Tailwind to scan (required by @source directives)
COPY app/ ./app/

# Copy pagination views from composer stage for Tailwind @source directive
COPY --from=composer /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

# Create empty storage views directory (generated at runtime)
RUN mkdir -p storage/framework/views

RUN npm run build

# Stage 3: Production image with FrankenPHP (Octane)
# Use php:8.4-alpine (same as Stage 1 — cached) and download FrankenPHP binary from GitHub
FROM php:8.4-alpine

# Install PHP extensions via standard Alpine tooling
RUN apk add --no-cache \
    libzip-dev \
    icu-dev \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpq-dev \
    oniguruma-dev \
    curl \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        gd \
        zip \
        intl \
        opcache \
        pcntl \
        bcmath \
        mbstring \
        exif

# Install redis extension via PECL
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Create custom PHP config
RUN echo "memory_limit=256M" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "upload_max_filesize=64M" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "post_max_size=64M" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "max_execution_time=300" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "opcache.enable=1" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "opcache.memory_consumption=256" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "opcache.interned_strings_buffer=64" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "opcache.max_accelerated_files=32531" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "opcache.validate_timestamps=0" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "opcache.save_comments=1" >> "$PHP_INI_DIR/conf.d/99-app.ini" && \
    echo "opcache.enable_file_override=1" >> "$PHP_INI_DIR/conf.d/99-app.ini"

# Download FrankenPHP binary from GitHub releases (avoids Docker Hub / ghcr.io)
RUN ARCH=$(uname -m) && \
    curl -fsSL "https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-${ARCH}" \
        -o /usr/local/bin/frankenphp && \
    chmod +x /usr/local/bin/frankenphp

WORKDIR /app

# Copy application files
COPY --from=composer /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app/storage \
    && chmod -R 755 /app/bootstrap/cache

# Create required directories
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Copy and set entrypoint
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# Set environment variables
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV OCTANE_SERVER=frankenphp

EXPOSE 8000

# Run entrypoint script (handles caching, optional migrations, then starts Octane)
CMD ["/docker-entrypoint.sh"]
