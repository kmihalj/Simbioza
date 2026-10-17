# EN: Disposable local CI runtime only; no application/private state is part of this image.
#
# HR: Samo potrošni lokalni CI runtime; image ne sadrži aplikaciju ni privatno stanje.
ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-cli
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libonig-dev libsqlite3-dev \
    && docker-php-ext-install -j2 mbstring zip pdo_sqlite pcntl \
    && rm -r /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev \
    && docker-php-ext-install -j2 intl \
    && rm -r /var/lib/apt/lists/*
RUN apt-get update && apt-get install -y --no-install-recommends rsync sudo \
    && pecl install pcov && docker-php-ext-enable pcov \
    && rm -r /var/lib/apt/lists/*
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /checks
