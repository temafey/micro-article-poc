FROM php:8.4-cli-alpine

# RoadRunner version to install
ARG RR_VERSION=2025.1.6

# Fix timezone
RUN set -eux \
    && echo "date.timezone=UTC" > /usr/local/etc/php/php.ini

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install system dependencies and PHP extensions
RUN set -eux \
    && apk update \
    && apk upgrade \
    && apk add --no-cache bash procps curl libstdc++ \
    && chmod +x /usr/local/bin/install-php-extensions \
    # Core extensions for PHP 8.4 microservices (same as PHP-FPM)
    && install-php-extensions \
        mbstring \
        mcrypt \
        bcmath \
        pcntl \
        curl \
        igbinary \
        zip \
        pdo_pgsql \
        redis \
        uuid \
        intl \
        sockets \
        msgpack \
        opcache \
        apcu \
        # OpenTelemetry for distributed tracing, metrics, and logs
        opentelemetry \
    # Create OPcache file cache directory
    && mkdir -p /tmp/opcache && chmod 1777 /tmp/opcache

# Install envsubst for template config files
RUN curl -L https://github.com/a8m/envsubst/releases/download/v1.4.3/envsubst-Linux-x86_64 -o envsubst \
    && chmod +x envsubst \
    && mv envsubst /usr/local/bin

# Download and install RoadRunner binary
RUN set -eux \
    && curl -L "https://github.com/roadrunner-server/roadrunner/releases/download/v${RR_VERSION}/roadrunner-${RR_VERSION}-linux-amd64.tar.gz" -o /tmp/roadrunner.tar.gz \
    && tar -xzf /tmp/roadrunner.tar.gz -C /tmp \
    && mv /tmp/roadrunner-${RR_VERSION}-linux-amd64/rr /usr/local/bin/rr \
    && chmod +x /usr/local/bin/rr \
    && rm -rf /tmp/roadrunner* \
    && rr -v

###
### Verify PHP installation
###
RUN set -eux \
    && echo "date.timezone=UTC" > /usr/local/etc/php/php.ini \
    && php -v | grep -oE 'PHP\s[.0-9]+' | grep -oE '[.0-9]+' | grep '^8.4' \
    \
    && PHP_ERROR="$( php -v 2>&1 1>/dev/null )" \
    && if [ -n "${PHP_ERROR}" ]; then echo "${PHP_ERROR}"; false; fi \
    && PHP_ERROR="$( php -i 2>&1 1>/dev/null )" \
    && if [ -n "${PHP_ERROR}" ]; then echo "${PHP_ERROR}"; false; fi \
    \
    && rm -f /usr/local/etc/php/php.ini \
    && true

###
### User/Group
###
ENV MY_USER="app" \
    MY_GROUP="app" \
    MY_UID="1000" \
    MY_GID="1000"

RUN set -eux \
    && addgroup -g ${MY_GID} ${MY_GROUP} \
    && adduser -u ${MY_UID} -G ${MY_GROUP} -s /bin/sh -D ${MY_USER} \
    && true

ENV BASH_ENV=/etc/profile.d/bash_profile.sh
COPY ./data/bash_profile.sh /etc/profile.d/bash_profile.sh

###
### Copy PHP configuration optimized for RoadRunner
###
COPY ./data/php.ini /usr/local/etc/php/conf.d/default-php.ini

###
### Working directory
###
WORKDIR /app

COPY --chmod=755 ./docker-entrypoint.sh /docker-entrypoint.sh

# Expose ports:
# 8080 - HTTP server
# 9180 - Prometheus metrics
# 2114 - Health check/status
# 6001 - RPC (internal)
EXPOSE 8080 9180 2114

# Health check using RoadRunner's built-in status endpoint
HEALTHCHECK --interval=10s --timeout=3s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:2114/health?plugin=http || exit 1

# Graceful shutdown (SIGTERM allows RoadRunner to finish active requests)
STOPSIGNAL SIGTERM

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["rr", "serve", "-c", ".rr.yaml"]
