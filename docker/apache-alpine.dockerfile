FROM composer:1.10.13 AS composer

LABEL maintainer="Edwino Stein <edwino.stein@gmail.com>"

ARG wimboot_ver=2.6.0
ARG syslinux_ver=6.03
RUN wget https://git.ipxe.org/release/wimboot/wimboot-$wimboot_ver.zip -O /tmp/wimboot.zip \
    && wget https://mirrors.edge.kernel.org/pub/linux/utils/boot/syslinux/syslinux-$syslinux_ver.zip -O /tmp/syslinux.zip

WORKDIR /tmp/app
COPY . .

WORKDIR /tmp/app/application
ARG APP_ENV=dev
RUN set -xe \
    && if [ "$APP_ENV" = "prod" ]; then export ARGS="--no-dev"; fi \
    && composer install --prefer-dist --no-scripts --no-progress --no-suggest \
                        --no-interaction --ignore-platform-reqs $ARGS

RUN composer dump-autoload --classmap-authoritative \
    && rm -f public/resources/.gitignore \
    && unzip /tmp/wimboot.zip -d /tmp/ \
    && mkdir -p public/resources/tools/wimboot/$wimboot_ver \
    && cp /tmp/wimboot-$wimboot_ver/wimboot public/resources/tools/wimboot/$wimboot_ver \
    && mkdir /tmp/syslinux \
    && unzip /tmp/syslinux.zip -d /tmp/syslinux/ \
    && mkdir -p public/resources/tools/syslinux/$syslinux_ver \
    && cp /tmp/syslinux/bios/memdisk/memdisk public/resources/tools/syslinux/$syslinux_ver/memdisk

FROM alpine:3.12 AS server

RUN apk add --no-cache apache2 \
            --repository http://dl-cdn.alpinelinux.org/alpine/edge/community php \
            php7-apache2 php7-mbstring php7-ctype php7-session php7-xml php7-dom \
            php7-json php7-tokenizer

ARG DOCUMENTO_ROOT=/var/www/localhost/application/public

RUN sed -i "s/#LoadModule\ rewrite_module/LoadModule\ rewrite_module/" /etc/apache2/httpd.conf \
    && sed -i "s#/var/www/localhost/htdocs#$DOCUMENTO_ROOT#" /etc/apache2/httpd.conf \
    && printf "\n<Directory \"$DOCUMENTO_ROOT\">\n\tAllowOverride All\n</Directory>\n" >> /etc/apache2/httpd.conf \
    && mkdir -p $DOCUMENTO_ROOT

WORKDIR /var/www/localhost
COPY --from=composer /tmp/app/ .

WORKDIR /var/www/localhost/application
RUN php -d memory_limit=256M bin/console cache:clear \
    && mkdir -p public/resources/media

EXPOSE 80
CMD ["-D","FOREGROUND"]
ENTRYPOINT ["/usr/sbin/httpd"]
