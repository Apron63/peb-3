FROM php:8.3-fpm

RUN usermod -u 1000 www-data
RUN chown -R 1000 /var/www/html/

RUN apt-get update && apt-get -y install --no-install-recommends \
    git \
    && docker-php-ext-install pdo_mysql \
    && php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/local/bin --filename=composer \ && chmod +x /usr/local/bin/composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

#install some base extensions
RUN apt-get update && \
     apt-get install -y \
     libzip-dev \
     libxml2-dev \
     libpng-dev \
     unzip \
     && docker-php-ext-install zip

RUN apt-get install -y zlib1g-dev libicu-dev g++ \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-enable intl \
    && docker-php-ext-install iconv \
    && docker-php-ext-install dom \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

ADD ./php.ini /usr/local/etc/php/php.ini

CMD ["php-fpm"]

EXPOSE 9000
