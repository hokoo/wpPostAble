ARG PHP_BASE_IMAGE=wodby/wordpress-php:8.4-4.80.9

FROM ${PHP_BASE_IMAGE}

USER root

RUN set -eux; \
    mkdir -p /home/wodby/.wp-cli /var/log/php; \
    touch /var/log/php/error.log /var/log/php/xdebug.log; \
    chown -R wodby:wodby /home/wodby/.wp-cli /var/log/php

COPY php.ini /usr/local/etc/php/conf.d/zzzz-local-dev.ini
COPY --chown=wodby:wodby wp-cli.yml /home/wodby/.wp-cli/config.yml

WORKDIR /srv/web

USER wodby
