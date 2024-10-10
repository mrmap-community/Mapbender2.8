FROM php:7.4-apache

ENV MAPBENDER_PATH "data"

############################################################
# create folder structure
############################################################
RUN mkdir -pv "${MAPBENDER_PATH}" && \
    mkdir -pv ${MAPBENDER_PATH}svn/  && \
    mkdir -pv ${MAPBENDER_PATH}access/ && \
    mkdir -p ${MAPBENDER_PATH}mapbender/http/tmp/wmc

WORKDIR "$MAPBENDER_PATH"

############################################################
# install needed debian packages
############################################################
RUN apt-get update \
    && apt-get install -y \
    rename \
    g++ \
    make \
    bison \
    bzip2 \
    unzip \
    zip \
    gdal-bin \
    cgi-mapserver \
    default-mysql-server \
    imagemagick \
    locate \
    mc \
    links \
    w3m \
    lynx \
    arj \
    xpdf \
    dbview \
    odt2txt \
    ca-certificates \
    oidentd \
    gettext \
    gkdebconf \
    subversion \
    subversion-tools \
    memcached \
    curl \
    libproj-dev \
    libapache2-mod-security2 \
    libmagickwand-dev \
    libz-dev \
    libmemcached-dev \
    libmemcached-tools \
    libpq-dev \
    netcat-traditional \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*
RUN printf "\n" | pecl install imagick\
    && pecl install memcached \
    && pecl install apcu \
    && pecl install apcu_bc-1.0.3
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    #pdo_mysql \
    pdo_pgsql \
    pgsql \
    mysqli \
    curl \
    gettext \
    #imagick \
    #memcache \
    #memcached \
    #apcu \
    #apcu_bc \
    && docker-php-ext-enable imagick memcached \
    && docker-php-ext-enable apcu --ini-name 10-docker-php-ext-apcu.ini \
    && docker-php-ext-enable apc --ini-name 20-docker-php-ext-apc.ini
    # && apt-get install libapache2-mod-php7.4 
RUN apt-get update && apt-get install -y postgresql postgresql-contrib
############################################################
# adopt php logging - only log errors - needed here, else the installation will throw many notices
############################################################
# RUN cp /etc/php/7.4/cli/php.ini /etc/php/7.4/cli/php.ini_geoportal_backup && \
#     cp /etc/php/7.4/apache2/php.ini /etc/php/7.4/apache2/php.ini_geoportal_backup && \
#     sed -i "s/error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT/error_reporting = E_ERROR/g" /etc/php/7.4/cli/php.ini  && \
#     sed -i "s/;error_log = php_errors.log/error_log = \/tmp\/php7_cli_errors\.log/g" /etc/php/7.4/cli/php.ini


ENTRYPOINT [ "/docker/bash_scripts/wait_db.sh", "/docker/bash_scripts/init_db.sh" ]
