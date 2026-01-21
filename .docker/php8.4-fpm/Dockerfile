FROM php:8.4-fpm-alpine

# Fix timezone (only required for testing to stop php -v and php-fpm -v from complaining to stderr)
RUN set -eux \
    && echo "date.timezone=UTC" > /usr/local/etc/php/php.ini

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
# Fetch the latest apk manifests
# Update existing packages
# Cleanup after ourselves to keep this layer as small as possible
# Details: https://wiki.alpinelinux.org/wiki/Alpine_Linux_package_management
RUN set -eux \
    && apk update \
    && apk upgrade \
    && apk add --no-cache bash procps curl libstdc++ \
    && chmod +x /usr/local/bin/install-php-extensions \
    # Core extensions for PHP 8.4 microservices
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
        # gRPC/Protobuf for OTLP transport (optional, HTTP works without)
        #grpc protobuf \
        # OpenTelemetry for distributed tracing, metrics, and logs
        opentelemetry \
    # Create OPcache file cache directory
    && mkdir -p /tmp/opcache && chmod 1777 /tmp/opcache
# --------------------------------------------------------------------------
# ---------------- Installing envsubst for template config file ------------

RUN curl -L https://github.com/a8m/envsubst/releases/download/v1.4.3/envsubst-Linux-x86_64 -o envsubst \
    && chmod +x envsubst \
    && mv envsubst /usr/local/bin \
# --------------------------------------------------------------------------
# ---------------- Installing php-fpm healthcheck --------------------------
    && echo "pm.status_path = /status" >> /usr/local/etc/php-fpm.d/zz-docker.conf \
    && curl https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck > /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck \
    && apk add fcgi busybox grep \
    && true
###
### Verify
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
### Envs
###
ENV MY_USER="app" \
    MY_GROUP="app" \
    MY_UID="1000" \
    MY_GID="1000"

####
#### User/Group
####
RUN set -eux \
    && addgroup -g ${MY_GID} ${MY_GROUP} \
    && adduser -u ${MY_UID} -G ${MY_GROUP} -s /bin/sh -D ${MY_USER} \
    && true

ENV BASH_ENV=/etc/profile.d/bash_profile.sh
COPY ./data/bash_profile.sh /etc/profile.d/bash_profile.sh

###
### Copy files
###
COPY ./data/php.ini /usr/local/etc/php/conf.d/default-php.ini
COPY ./data/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./data/docker.conf /usr/local/etc/php-fpm.d/docker.conf
COPY ./data/global.conf /usr/local/etc/php-fpm.d/global.conf

RUN set -eux \
    && chown -R ${MY_GID}:${MY_GROUP} /usr/local/etc/php-fpm.d/ \
    && chown -R ${MY_GID}:${MY_GROUP} /usr/local/etc/php/conf.d/ \
    && true

COPY ./docker-entrypoint.sh /docker-entrypoint.sh
COPY ./docker-entrypoint.d /docker-entrypoint.d

RUN chmod +x /docker-entrypoint.sh \
    && chmod +x /docker-entrypoint.d/*.sh

# Health check using pre-installed php-fpm-healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# Graceful shutdown signal (allows FPM to finish current requests)
STOPSIGNAL SIGQUIT

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["php-fpm"]
