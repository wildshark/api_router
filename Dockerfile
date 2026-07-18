FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install required PHP extensions (SQLite)
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Set working directory
WORKDIR /var/www/html/

# Copy application source code
COPY . /var/www/html/

# Set proper ownership and permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
