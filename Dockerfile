FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# Cria um index simples só pra não dar 403 na raiz
RUN printf '%s\n' \
'<?php' \
'header("Content-Type: application/json; charset=utf-8");' \
'echo json_encode([' \
'  "ok" => true,' \
'  "service" => "API Lotes - Solotes",' \
'  "endpoints" => ["/api/lotes.php?loteamento=ACAPULCO"],' \
'  "note" => "Se der erro 500, confira as ENV no EasyPanel: DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS"' \
'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);' \
> /var/www/html/index.php

COPY api/ /var/www/html/api/

EXPOSE 80
