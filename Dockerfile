# Usa la imagen oficial de WordPress con PHP 8.2 y Apache
FROM wordpress:6.8.2-php8.3-apache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install additional utilities for Azure
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    ca-certificates \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# Descargar certificado SSL de Azure MySQL
RUN mkdir -p /var/www/html/ssl \
    && curl -o /var/www/html/ssl/DigiCertGlobalRootCA.crt.pem \
       https://www.digicert.com/CACerts/DigiCertGlobalRootCA.crt \
    && chmod 644 /var/www/html/ssl/DigiCertGlobalRootCA.crt.pem

    # Copy PHP configuration for uploads and performance
COPY ./config/php.ini /usr/local/etc/php/conf.d/uploads.ini
COPY ./config/apache.conf /etc/apache2/conf-available/custom.conf

# Enable Apache modules and custom configuration
RUN a2enmod rewrite headers deflate expires \
    && a2enconf custom \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf


# Copiar tus temas y plugins personalizados

# Create wp-content directories first
RUN mkdir -p /var/www/html/wp-content/mu-plugins \
    && mkdir -p /var/www/html/wp-content/plugins \
    && mkdir -p /var/www/html/wp-content/themes \
    && mkdir -p /var/www/html/wp-content/uploads



# Copy Azure wp-config and custom configuration
COPY ./config/wp-config.php /var/www/html/wp-config.php

# Set proper permissions for wp-content
RUN chown -R www-data:www-data /var/www/html/wp-content \
    && chmod -R 755 /var/www/html/wp-content \
    && chmod -R 775 /var/www/html/wp-content/uploads \
    && chown www-data:www-data /var/www/html/wp-config.php



EXPOSE 80

CMD ["apache2-foreground"]