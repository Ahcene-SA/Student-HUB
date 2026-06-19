FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . /app

RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]