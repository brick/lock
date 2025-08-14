ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli

ARG UID
ARG GID

RUN apt update

# pdo_mysql
RUN docker-php-ext-install pdo_mysql

# pdo_pgsql
RUN apt install -y libpq-dev
RUN docker-php-ext-install pdo_pgsql

# unzip & git for composer
RUN apt install -y unzip git

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN groupadd -g ${GID} user
RUN useradd -u ${UID} -g ${GID} -m -s /bin/bash user

USER user

CMD sleep infinity
