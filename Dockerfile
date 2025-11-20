FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y git unzip zip \
    && docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite for Laravel/Clean URLs
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy everything
COPY . /var/www/html

# Copy Composer from its official image (multi-stage)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose Apache port 80
EXPOSE 80

# Run Apache in the foreground
CMD ["apache2-foreground"]
