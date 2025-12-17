FROM php:8.2-fpm

COPY php.ini /usr/local/etc/php/

RUN apt-get update \
    && apt-get install -y \
    default-mysql-client \
    zlib1g-dev \
    libzip-dev \
    unzip \
    tzdata \
    && docker-php-ext-install pdo_mysql zip \
    && ln -sf /usr/share/zoneinfo/Asia/Tokyo /etc/localtime \
    && echo "Asia/Tokyo" > /etc/timezone \
    && echo "date.timezone=Asia/Tokyo" > /usr/local/etc/php/conf.d/99-timezone.ini \
    && dpkg-reconfigure -f noninteractive tzdata \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www
