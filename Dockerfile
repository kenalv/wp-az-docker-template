# Usa la imagen oficial de WordPress con PHP 8.2
FROM wordpress:6.6-php8.2-apache

# Instalar extensiones PHP necesarias para Azure y rendimiento
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    redis-tools \
    wget \
    unzip \
    ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    zip \
    intl \
    mbstring \
    xml \
    gd \
    pdo_mysql \
    mysqli \
    opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Descargar certificado SSL de Azure MySQL
RUN mkdir -p /usr/local/share/ca-certificates \
    && curl -o /usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt.pem \
       https://www.digicert.com/CACerts/DigiCertGlobalRootCA.crt \
    && chmod 644 /usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt.pem \
    && update-ca-certificates

# Configurar PHP para producción
COPY config/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configurar Apache para mejor rendimiento
COPY config/apache.conf /etc/apache2/conf-available/wordpress.conf
RUN a2enconf wordpress && a2enmod rewrite headers deflate expires \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Descargar y extraer WordPress si no está presente
RUN echo "Verificando instalación de WordPress..." && \
    if [ ! -f "/var/www/html/wp-load.php" ] || [ ! -f "/var/www/html/wp-admin/index.php" ]; then \
        echo "Descargando WordPress completo..."; \
        cd /tmp && \
        wget -q https://wordpress.org/latest.tar.gz && \
        tar -xzf latest.tar.gz && \
        cp -rf wordpress/* /var/www/html/ && \
        rm -rf wordpress latest.tar.gz && \
        echo "WordPress descargado exitosamente"; \
    else \
        echo "WordPress ya está instalado"; \
    fi

# Crear directorio para uploads y configurar permisos
RUN mkdir -p /var/www/html/wp-content/uploads \
    && mkdir -p /var/www/html/wp-content/plugins/ \
    && mkdir -p /var/www/html/wp-content/themes/ \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copiar configuración personalizada de WordPress (como backup)
COPY config/wp-config.php /tmp/wp-config-custom.php

# Copiar archivos temporales de debug
COPY config/debug-index.php /tmp/debug-index.php

# Copiar db.php personalizado para SSL y health check
COPY config/db.php /var/www/html/wp-content/
COPY config/health.php /var/www/html/
COPY config/status.php /var/www/html/
COPY config/install-wp.php /var/www/html/

# Copiar plugins y temas personalizados (incluyendo .gitkeep)
COPY src/ /var/www/html/wp-content/

# Remover archivos .gitkeep si existen
RUN find /var/www/html/wp-content/ -name ".gitkeep" -delete || true

# Script de inicialización
COPY scripts/init.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/init.sh

# Exponer el puerto 80
EXPOSE 80

# Usar el script de inicialización
ENTRYPOINT ["/usr/local/bin/init.sh"]
CMD ["apache2-foreground"]