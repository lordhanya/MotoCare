# Use the official PHP image with Apache
FROM php:8.2-apache

# Install PDO MySQL extension inside the container
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module (optional, needed for frameworks)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all code into the container
COPY . /var/www/html

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader \
    && rm composer-setup.php

# Expose Apache port
EXPOSE 80

# Run Apache in the foreground
CMD ["apache2-foreground"]
