FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# deixa a API em /api/lotes.php
RUN mkdir -p /var/www/html/api
COPY api/ /var/www/html/api/

EXPOSE 80
