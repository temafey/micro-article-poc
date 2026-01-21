ARG DOCKER_SERVER_HOST=docker.local
ARG DOCKER_PROJECT_PATH=expo-micro-news
ARG DOCKER_PHP_VERSION=8.4
ARG DOCKER_IMAGE_VERSION=master

FROM ${DOCKER_SERVER_HOST}/${DOCKER_PROJECT_PATH}/php${DOCKER_PHP_VERSION}-fpm-composer:${DOCKER_IMAGE_VERSION}

RUN apk add --no-cache graphviz \
    && install-php-extensions xdebug ast pcov

WORKDIR /app
