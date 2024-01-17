FROM php:7.4-apache

RUN apt-get update && apt-get install -y git libzip-dev zip unzip \
 && docker-php-ext-install zip pdo_mysql \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && useradd -u 1000 -U -m dev \
 && sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/' /etc/apache2/sites-available/000-default.conf
USER dev:dev
WORKDIR /var/www/html
