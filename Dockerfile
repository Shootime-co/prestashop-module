FROM composer:2.2 as composer

FROM php:5.6-cli

COPY . /usr/src/shootime

WORKDIR /usr/src/shootime

COPY --from=composer /usr/bin/composer /usr/bin/composer