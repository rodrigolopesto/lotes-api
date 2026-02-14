FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# copia TUDO que está na pasta "api/" do repo direto pra raiz do Apache
# => api/lotes.php vira /lotes.php
# => api/index.php vira /index.php
COPY api/ /var/www/html/

EXPOSE 80
