FROM ghcr.io/roadrunner-server/roadrunner:latest AS roadrunner
FROM php:8.4-cli

COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr


ARG DEBIAN_FRONTEND=noninteractive

# Update
RUN apt-get -y update --fix-missing && \
    apt-get upgrade -y && \
    apt-get --no-install-recommends install -y apt-utils zip unzip wget && \
    rm -rf /var/lib/apt/lists/*

# Install and set up system locales
RUN apt-get update && apt-get -y install locales
RUN sed -i '/en_US.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/en_GB.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '$ a\en    en_US.UTF-8' /etc/locale.alias && \
    sed -i '/es_ES.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '$ a\es    es_ES.UTF-8' /etc/locale.alias && \
    sed -i '/da_DK.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '$ a\da    da_DK.UTF-8' /etc/locale.alias && \
    sed -i '/de_DE.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '$ a\de    de_DE.UTF-8' /etc/locale.alias && \
    sed -i '/fr_FR.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '$ a\fr    fr_FR.UTF-8' /etc/locale.alias && \
    sed -i '/it_IT.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '$ a\it    it_IT.UTF-8' /etc/locale.alias && \
    sed -i '/nl_NL.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '$ a\nl    nl_NL.UTF-8' /etc/locale.alias && \
    sed -i '/ru_RU.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '$ a\ru    ru_RU.UTF-8' /etc/locale.alias && \
    sed -i '/tr_TR.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '$ a\tr    tr_TR.UTF-8' /etc/locale.alias && \
    locale-gen

ENV LANG=en_US.UTF-8 \
    LANGUAGE=en_US:en \
    LC_ALL=en_US.UTF-8

# install extensions, see https://github.com/mlocati/docker-php-extension-installer#supported-php-extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    apcu \
    xdebug \
    calendar \
    exif \
    gd \
    igbinary \
    intl \
    ldap \
    memcache \
    memcached \
    mongodb \
    pcntl \
    pdo_mysql \
    pdo_oci \
    pdo_odbc \
    pdo_pgsql \
    pdo_sqlsrv \
    redis \
    sockets \
    xmlrpc \
    zip

# install package plugins
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html/

CMD rr serve -c .rr.yaml