FROM php:8.2-apache

# Install MySQL extensions for PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite

# Configure Apache document root
WORKDIR /var/www/html

# Copy PHP CodeIgniter application
COPY php-portal /var/www/html

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
