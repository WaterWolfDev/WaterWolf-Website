#
# Base build (common steps)
#
FROM php:8.3-fpm-alpine3.19 AS base

ENV TZ=UTC

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions @composer gd curl xml zip mbstring pdo_mysql apcu

RUN apk add --no-cache zip git curl bash \
    supervisor \
    caddy \
    nodejs npm \
    supercronic \
    su-exec \
    mariadb-client \
    restic

# Set up App user
RUN mkdir -p /var/app/www \
    && addgroup -g 1000 app \
    && adduser -u 1000 -G app -h /var/app/ -s /bin/sh -D app \
    && addgroup app www-data \
    && mkdir -p /var/app/media /var/app/www /var/app/www_tmp /run/supervisord /logs \
    && chown -R app:app /var/app /logs

COPY --chown=app:app ./build/scripts/ /usr/local/bin
RUN chmod a+x /usr/local/bin/*

COPY ./build/supervisord.conf /etc/supervisord.conf
COPY ./build/services/ /etc/supervisor.d/

COPY --chown=app:app ./build/cron /etc/cron.d/app

COPY ./build/phpfpmpool.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./build/php.ini /usr/local/etc/php/php.ini

VOLUME ["/var/app/www_tmp"]
VOLUME ["/var/app/media"]

EXPOSE 8080

WORKDIR /var/app/www

COPY --chown=app:app . .

#
# Development Build
#
FROM base AS development

COPY ./build/dev/services/ /etc/supervisor.d/
COPY ./build/dev/Caddyfile /etc/Caddyfile
COPY ./build/dev/entrypoint.sh /var/app/entrypoint.sh

RUN apk add --no-cache shadow

RUN chmod a+x /var/app/entrypoint.sh

USER root

ENV APPLICATION_ENV=development

ENTRYPOINT ["/var/app/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]

#
# CI/Testing Build
#
FROM base AS testing

COPY ./build/testing/entrypoint.sh /var/app/entrypoint.sh
RUN chmod a+x /var/app/entrypoint.sh

USER root

ENV APPLICATION_ENV=testing

ENTRYPOINT ["/var/app/entrypoint.sh"]
CMD ["app_ci"]

#
# Production Build
#
FROM base AS production

COPY ./build/prod/Caddyfile /etc/Caddyfile
COPY ./build/prod/entrypoint.sh /var/app/entrypoint.sh

RUN chmod a+x /var/app/entrypoint.sh

USER app

RUN composer install --no-dev --no-ansi --no-autoloader --no-interaction \
    && composer dump-autoload --optimize --classmap-authoritative \
    && composer clear-cache

RUN npm ci --include=dev \
    && npm run build \
    && npm cache clean --force

USER root

ENV APPLICATION_ENV=production

ENTRYPOINT ["/var/app/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
