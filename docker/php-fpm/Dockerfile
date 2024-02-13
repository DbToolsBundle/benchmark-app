FROM php:8.1-fpm

RUN apt-get update && \
    apt-get install -y --no-install-recommends default-mysql-client acl iproute2 zip zlib1g-dev libzip-dev sqlite3 \
    libxml2-dev libpng-dev libghc-curl-dev libldb-dev libldap2-dev gpg postgresql postgresql-client libpq-dev git \
     -yqq
RUN pecl install apcu
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install -j$(nproc) pgsql pdo_pgsql pdo mysqli pdo_mysql zip xml intl gd curl bcmath opcache ldap
RUN docker-php-ext-enable apcu pdo_pgsql pdo_mysql sodium ldap
RUN pecl install xdebug-3.1.6 && docker-php-ext-enable xdebug
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Installing mariadb-client-11
RUN curl -LsS https://downloads.mariadb.com/MariaDB/mariadb_repo_setup | bash -s -- --mariadb-server-version=11.3
RUN apt-get update && apt-get install -y mariadb-client


COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
RUN chmod +x /usr/local/bin/docker-php-entrypoint

# ENTRYPOINT [ "init.sh" ]

WORKDIR /var/www

EXPOSE 9000
