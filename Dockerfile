# SmartBengkel - Railway Deployment (PHP 8.2 + Apache + mysqli)
FROM php:8.2-apache

# Install ekstensi PHP yang dibutuhkan aplikasi (mysqli, pdo_mysql, dll)
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite headers

# Salin seluruh aplikasi ke document root Apache
WORKDIR /var/www/html
COPY . /var/www/html/

# Bersihkan artefak yang tidak perlu dikirim
RUN rm -rf /var/www/html/.git \
    /var/www/html/.claude \
    /var/www/html/.kiro \
    /var/www/html/temp_repo \
    /var/www/html/tests \
    /var/www/html/setup.ps1 \
    /var/www/html/.env

# Pastikan direktori upload & log dapat ditulis
RUN mkdir -p /var/www/html/foto \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Salin script inisialisasi & entrypoint
COPY deploy/entrypoint.sh /entrypoint.sh
COPY deploy/init_db.php /var/www/html/deploy/init_db.php
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
