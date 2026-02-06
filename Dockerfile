# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY resources/ ./resources/
COPY vite.config.js ./
COPY postcss.config.js* ./
COPY tailwind.config.js* ./

RUN npm run build

# Stage 2: Install PHP dependencies
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
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

# Stage 3: Production image with FrankenPHP (Octane)
FROM dunglas/frankenphp:1-php8.4-alpine

# Install PHP extensions
RUN install-php-extensions \
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
    exif \
    redis

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

EXPOSE 8080

# Run entrypoint script (handles migrations, caching, then starts Octane)
CMD ["/docker-entrypoint.sh"]
