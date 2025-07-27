FROM php:8.2-cli

RUN apt-get update && apt-get install -y libpq-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

COPY worker.php .

CMD ["php", "-S", "0.0.0.0:80", "worker.php"]
