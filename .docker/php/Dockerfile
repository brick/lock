ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli

RUN apt update && apt install -y libpq-dev

RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_pgsql

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

CMD sleep infinity
