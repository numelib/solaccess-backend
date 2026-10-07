FROM php:8.4-fpm-alpine

RUN apk add icu-dev
RUN docker-php-ext-install intl pdo_mysql

COPY --from=composer /usr/bin/composer /usr/bin/composer
