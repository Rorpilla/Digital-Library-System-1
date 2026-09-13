FROM php:8.2-apache

WORKDIR /var/www/html

# Install SQLite development libraries
RUN apt-get update \
    && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Copy the application
COPY . /var/www/html

# Make the application writable
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html

# Apache listens on Render's port
EXPOSE 80