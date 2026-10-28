# EN: Build only the sanitized context produced by scripts/build_container_context.php.
# Never send HFC private configuration, data, credentials or live vendor links to Docker.
#
# HR: Graditi samo očišćeni kontekst iz scripts/build_container_context.php.
# Privatni HFC config, podatke, tajne i žive vendor linkove nikada ne slati Dockeru.
ARG PHP_IMAGE=php:8.4-apache-trixie@sha256:901b0dbcd2419cc9cd307ea05e57403449ce722ccd5b32335da6e8d39f2b1ee0
# EN: Refresh inherited OS packages too; installing build dependencies alone can leave base CVEs.
# The builder and application both inherit these updates. CI still blocks fixable high/critical findings.
#
# HR: Nadograditi i naslijeđene OS pakete; samo instaliranje build ovisnosti može ostaviti ranjivosti osnove.
# Builder i aplikacija nasljeđuju ove zakrpe. CI i dalje blokira popravljive visoke/kritične nalaze.
FROM ${PHP_IMAGE} AS php-runtime
RUN apt-get update \
    && apt-get upgrade -y --no-install-recommends \
    && apt-get install -y --no-install-recommends \
    libicu-dev libzip-dev libpq-dev libonig-dev libsqlite3-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 intl mbstring zip pdo_pgsql pdo_mysql pdo_sqlite gd opcache pcntl \
    && a2enmod rewrite headers \
    && apt-mark manual libicu76 libzip5 libpq5 libonig5 libsqlite3-0 \
        libpng16-16t64 libjpeg62-turbo libfreetype6 \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
        libicu-dev libzip-dev libpq-dev libonig-dev libsqlite3-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libc6-dev libcrypt-dev linux-libc-dev rpcsvc-proto \
    && rm -r /var/lib/apt/lists/*

FROM php-runtime AS builder
RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -r /var/lib/apt/lists/*
COPY --from=composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac /usr/bin/composer /usr/local/bin/composer
WORKDIR /opt/simbioza
COPY app/ ./
COPY packages/ /opt/simbioza-build/packages/
RUN composer update --no-dev --no-interaction --no-progress --no-scripts --prefer-dist \
    --optimize-autoloader && composer check-platform-reqs --no-dev \
    && php docker/finalize_identity.php \
    && test ! -L vendor/aaieduhr/heartphrame-framework \
    && find vendor -type d -name .git -prune -exec rm -r {} +

FROM php-runtime AS application
ARG VERSION=0.3.21
ARG REVISION=unknown
LABEL org.opencontainers.image.title="Simbioza" \
    org.opencontainers.image.source="https://github.com/kmihalj/Simbioza" \
    org.opencontainers.image.version="${VERSION}" \
    org.opencontainers.image.revision="${REVISION}" \
    org.opencontainers.image.licenses="EUPL-1.2"
WORKDIR /opt/simbioza
COPY --from=builder /opt/simbioza /opt/simbioza
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/simbioza.ini
RUN mkdir -p /opt/simbioza/data /runtime /opt/simplesamlphp \
    && find /opt/simbioza -type d -exec chmod 0755 {} + \
    && find /opt/simbioza -type f -exec chmod 0644 {} + \
    && chmod -R a-w /opt/simbioza \
    && sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
ENV HPH_APP_PATH=/opt/simbioza \
    HPH_CONFIG_PATH=/opt/simbioza/config \
    HPH_CONFIG_OVERRIDE_PATHS=/runtime/config \
    HPH_DEPLOYMENT_MODE=immutable \
    APACHE_RUN_DIR=/tmp/apache2 \
    APACHE_LOCK_DIR=/tmp/apache2 \
    APACHE_PID_FILE=/tmp/apache2/apache2.pid \
    SIMPLESAMLPHP_CONFIG_DIR=/runtime/saml/config
USER 33:33
EXPOSE 8080
ENTRYPOINT ["php", "/opt/simbioza/docker/entrypoint.php"]
CMD ["serve"]
