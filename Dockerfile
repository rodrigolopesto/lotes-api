FROM php:8.2-apache

# PDO Postgres
RUN apt-get update \
 && apt-get install -y libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# Copia o conteúdo da pasta /api do repo para a RAIZ do site
# Isso faz o arquivo api/lotes.php virar /lotes.php no servidor
COPY api/ /var/www/html/

EXPOSE 80
