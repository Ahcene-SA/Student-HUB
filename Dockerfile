FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /app

# Copier composer.json et composer.lock d'abord (pour le cache)
COPY composer.json composer.lock* ./

# Installer les dépendances Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copier tout le projet
COPY . /app

# Activer mod_rewrite
RUN a2enmod rewrite

# Exposer le port 80
EXPOSE 80

# Démarrer Apache
CMD ["apache2-foreground"]