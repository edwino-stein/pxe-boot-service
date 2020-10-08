FROM composer:1.10.13 AS composer

LABEL maintainer="Edwino Stein <edwino.stein@gmail.com>"

WORKDIR /tmp/deploy/application
COPY [ "application/composer.json", "application/composer.lock", "application/symfony.lock",  "./" ]

RUN composer install --prefer-dist --no-scripts --no-progress --no-suggest \
                     --no-interaction --ignore-platform-reqs

FROM php:7.4.11-cli-alpine3.12 AS server

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-2.8.1 \
    && docker-php-ext-enable xdebug

WORKDIR /var/www/localhost/
COPY --from=composer /tmp/deploy/vendor/ ./vendor
RUN mkdir var
COPY .env* ./

WORKDIR /var/www/localhost/application
CMD php bin/console cache:clear \
    && cd public \
    && php -S 0.0.0.0:8080 router.php
