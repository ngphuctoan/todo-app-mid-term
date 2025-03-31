FROM php:fpm-alpine

RUN apk add --no-cache unzip && \
    docker-php-ext-install pdo pdo_mysql

COPY --from=composer:lts /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json ..
RUN composer install --no-interaction --prefer-dist --working-dir=..

CMD ["php-fpm"]