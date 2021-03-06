FROM composer:1.10.13 AS composer
LABEL maintainer="Edwino Stein <edwino.stein@gmail.com>"

WORKDIR /tmp/app
COPY [ "composer.json", "composer.lock", "symfony.lock",  "./" ]

ARG wimboot_ver=2.6.0
ARG syslinux_ver=6.03
RUN wget https://git.ipxe.org/release/wimboot/wimboot-$wimboot_ver.zip -O /tmp/wimboot.zip \
    && wget https://mirrors.edge.kernel.org/pub/linux/utils/boot/syslinux/syslinux-$syslinux_ver.zip -O /tmp/syslinux.zip

ARG APP_ENV=dev
RUN set -xe \
    && if [ "$APP_ENV" = "prod" ]; then export ARGS="--no-dev"; fi \
    && composer install --prefer-dist --no-scripts --no-progress --no-suggest \
                        --no-interaction --ignore-platform-reqs $ARGS

COPY . /tmp/app
RUN composer dump-autoload --classmap-authoritative \
    && rm public/resources/.gitignore \
    && unzip /tmp/wimboot.zip -d /tmp/ \
    && mkdir -p public/resources/tools/wimboot/$wimboot_ver \
    && cp /tmp/wimboot-$wimboot_ver/wimboot public/resources/tools/wimboot/$wimboot_ver \
    && mkdir /tmp/syslinux \
    && unzip /tmp/syslinux.zip -d /tmp/syslinux/ \
    && mkdir -p public/resources/tools/syslinux/$syslinux_ver \
    && cp /tmp/syslinux/bios/memdisk/memdisk public/resources/tools/syslinux/$syslinux_ver/memdisk

FROM php:7.4.10-fpm-alpine3.12 AS server

ARG APP_ENV=dev
ARG APP_DEBUG=1
ARG NEXT_SERVER=192.168.2.1

ENV APP_ENV $APP_ENV
ENV APP_DEBUG $APP_DEBUG
ENV NEXT_SERVER $NEXT_SERVER

RUN rm -rf /var/www
WORKDIR /var/www
COPY --from=composer /tmp/app/ .

RUN php -d memory_limit=256M bin/console cache:clear \
    && mkdir -p public/resources/media

WORKDIR /var/www/public
EXPOSE 9000
CMD ["php-fpm"]
