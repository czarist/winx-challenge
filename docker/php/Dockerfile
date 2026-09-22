FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-libs libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql bcmath opcache zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-deps

# pcov só instrumenta app/ (mesmo escopo do <source> em phpunit.xml): restringe
# o custo de cobertura a esse diretório e não afeta requisições normais do
# PHP-FPM — só entra em ação quando os testes rodam com --coverage.
RUN echo 'pcov.directory=/var/www/html/app' > /usr/local/etc/php/conf.d/pcov.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
