ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli

ARG UID
ARG GID

RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_pgsql

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN groupadd -g ${GID} user
RUN useradd -u ${UID} -g ${GID} -m -s /bin/bash user

USER user

CMD sleep infinity
