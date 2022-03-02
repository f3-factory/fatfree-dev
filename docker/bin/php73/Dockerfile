FROM php:7.3-fpm-buster

ARG DEBIAN_FRONTEND=noninteractive

# Update
RUN apt-get -y update --fix-missing && \
    apt-get upgrade -y && \
    apt-get --no-install-recommends install -y apt-utils && \
    rm -rf /var/lib/apt/lists/*

# Install and set up system locales
RUN apt-get update && apt-get -y install locales
RUN sed -i '/en_US.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/en_GB.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/es_ES.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/da_DK.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/de_DE.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/fr_FR.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/it_IT.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/nl_NL.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/ru_RU.UTF-8/s/^# //g' /etc/locale.gen && \
    sed -i '/tr_TR.UTF-8/s/^# //g' /etc/locale.gen && \
    locale-gen

ENV LANG=en_US.UTF-8 \
    LANGUAGE=en_US:en \
    LC_ALL=en_US.UTF-8

# install extensions, see https://github.com/mlocati/docker-php-extension-installer#supported-php-extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    calendar \
    gd \
    xdebug-^3 \
    apcu \
    exif \
    gd \
    geoip \
    igbinary \
    imagick \
    intl \
    ldap \
    memcache \
    memcached \
    mongodb \
    pcntl \
    pdo_dblib \
    pdo_mysql \
    pdo_oci \
    pdo_odbc \
    pdo_pgsql \
    pdo_sqlsrv \
    redis \
    sockets \
    xmlrpc \
    zip
