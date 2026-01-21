# syntax=docker/dockerfile:1.6

# FrankenPHP Docker Image for News Microservice
# Replaces nginx+php-fpm with single container architecture
# Supports worker mode for improved performance

FROM dunglas/frankenphp:1.3-php8.4-alpine

LABEL maintainer="Development Team"
LABEL description="FrankenPHP worker mode for News Microservice"

WORKDIR /app

# Layer 1: Install runtime dependencies only (rarely changes, cached)
RUN apk add --no-cache \
    postgresql-libs \
    icu-libs \
    icu-data-full \
    libzip \
    rabbitmq-c \
    curl

# Layer 2: Build dependencies + PHP extensions + cleanup (single layer)
# Note: igbinary installed BEFORE redis to enable binary serialization
RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        postgresql-dev \
        icu-dev \
        libzip-dev \
        rabbitmq-c-dev \
        linux-headers \
        $PHPIZE_DEPS \
    # Install core PHP extensions
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        intl \
        opcache \
        zip \
        bcmath \
        sockets \
    # Install PECL extensions (igbinary first for Redis optimization)
    && pecl install igbinary-3.2.16 \
    && pecl install redis-6.1.0 --configureoptions 'enable-redis-igbinary="yes"' \
    && pecl install amqp-2.1.2 \
    && pecl install msgpack-3.0.0 \
    && pecl install apcu-5.1.24 \
    # OpenTelemetry for distributed tracing, metrics, and logs
    && pecl install opentelemetry \
    && docker-php-ext-enable igbinary redis amqp msgpack apcu opentelemetry \
    # Cleanup build dependencies (removed from final image)
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/* /usr/src/*

# PHP configuration for worker mode (readable by non-root user)
COPY --chmod=644 .docker/frankenphp/php.ini /usr/local/etc/php/conf.d/99-worker.ini

# Copy Caddyfile (readable by non-root user)
COPY --chmod=644 .docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy composer files first for layer caching
COPY composer.json composer.lock symfony.lock ./

# Install dependencies (production)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY --chown=www-data:www-data . /app

# Generate optimized autoloader
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# Warmup cache (production)
RUN APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup --env=prod || true

# Create OPcache file cache directory
RUN mkdir -p /tmp/opcache && chown www-data:www-data /tmp/opcache

# Set permissions
RUN chown -R www-data:www-data /app/var /app/public

# Environment variables
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV SERVER_NAME=":80"
ENV PHP_OPCACHE_PRELOAD=/app/config/preload.php
ENV PHP_OPCACHE_PRELOAD_USER=www-data

# Expose HTTP and HTTPS ports
EXPOSE 80 443

# Health check
HEALTHCHECK --interval=10s --timeout=3s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Graceful shutdown signal (allows worker to finish current requests)
STOPSIGNAL SIGQUIT

# Run FrankenPHP with Caddyfile
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
