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

# Descargar certificado SSL de Azure MySQL y configurar CA certificates
RUN mkdir -p /var/www/html/ssl \
    && curl -o /var/www/html/ssl/DigiCertGlobalRootCA.crt.pem \
       https://www.digicert.com/CACerts/DigiCertGlobalRootCA.crt \
    && chmod 644 /var/www/html/ssl/DigiCertGlobalRootCA.crt.pem \
    && cp /var/www/html/ssl/DigiCertGlobalRootCA.crt.pem /usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt \
    && update-ca-certificates

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

# Copy themes and plugins from src directory
COPY ./src/ /var/www/html/wp-content/


# Copy Azure wp-config and health checks
COPY ./config/wp-config.php /var/www/html/wp-config.php
COPY ./config/health.php /var/www/html/health.php
COPY ./config/status.php /var/www/html/status.php

# Set proper permissions for wp-content and clean .gitkeep files
RUN find /var/www/html/wp-content/ -name ".gitkeep" -delete || true \
    && chown -R www-data:www-data /var/www/html/wp-content \
    && chmod -R 755 /var/www/html/wp-content \
    && chmod -R 775 /var/www/html/wp-content/uploads \
    && chown www-data:www-data /var/www/html/wp-config.php



EXPOSE 80

CMD ["apache2-foreground"]