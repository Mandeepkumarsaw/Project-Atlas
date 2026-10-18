FROM php:8.2-apache

# Install curl, mysqli, pdo, and pdo_mysql extensions along with utilities
RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    wget \
    unzip \
    && docker-php-ext-install curl mysqli pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Vendor official CodeIgniter 3.1.13 system core
WORKDIR /tmp
RUN wget --no-check-certificate -q https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/3.1.13.tar.gz \
    && tar -xzf 3.1.13.tar.gz \
    && mv CodeIgniter-3.1.13/system /var/www/ci3_system \
    && rm -rf /tmp/*

# Configure Apache document root
WORKDIR /var/www/html

# Copy application files
COPY php-portal /var/www/html

# Ensure system directory is populated from vendor cache if not in repository
RUN if [ ! -d /var/www/html/system ]; then cp -r /var/www/ci3_system /var/www/html/system; fi \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]

