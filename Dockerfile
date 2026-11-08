# syntax=docker/dockerfile:1
# ─────────────────────────────────────────────────────────────────────────────
# Melody — image de production (PHP-FPM + Symfony 6.4)
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1

# Extensions PHP nécessaires à Symfony, Doctrine et VichUploader
RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" intl pdo_mysql opcache zip \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-melody.ini

WORKDIR /var/www/html

# 1. Dépendances (couche mise en cache tant que composer.lock ne change pas)
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

# 2. Code applicatif
COPY . .

# 3. Autoload optimisé + assets + cache chaud
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php bin/console assets:install public --no-interaction \
    && php bin/console cache:warmup --env=prod --no-debug

# 4. Répertoires inscriptibles + graine des fichiers publics
RUN mkdir -p var/cache var/log public/media/images public/media/morceaux \
    && cp -a public /opt/public-seed \
    && chown -R www-data:www-data var public/media /opt/public-seed

COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

USER www-data

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

EXPOSE 9000
