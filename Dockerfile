FROM php:8.2-apache

RUN apt-get update && apt-get install -y sqlite3 libsqlite3-dev
RUN docker-php-ext-install pdo_sqlite
RUN a2enmod rewrite

COPY . /var/www/html/
RUN chmod -R 777 /var/www/html

EXPOSE 80