FROM php:8.2-apache

# Extensões necessárias para OcoMon 5.0 + Supabase (PostgreSQL)
RUN apt-get update && apt-get install -y \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
        libfreetype6-dev \
        libcurl4-openssl-dev \
        libonig-dev \
        libxml2-dev \
        unzip \
        curl \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        gd \
        zip \
        curl \
        xml \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar módulos Apache necessários
RUN a2enmod rewrite headers expires deflate

# Configuração Apache para o OcoMon
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Configuração PHP
COPY .docker/php.ini /usr/local/etc/php/conf.d/ocomon.ini

# Copiar código da aplicação
COPY . /var/www/html/

# Garantir que config.inc.php NÃO está na imagem (será montado via volume)
RUN rm -f /var/www/html/includes/config.inc.php \
    && rm -f /var/www/html/test_conn.php

# Permissões
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && mkdir -p /var/www/html/includes/logs \
    && chmod 775 /var/www/html/includes/logs \
    && chown www-data:www-data /var/www/html/includes/logs

EXPOSE 80
