FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    libfreetype6-dev \
    libjpeg62-turbo-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Setup Nginx
COPY .render/nginx.conf /etc/nginx/sites-available/default

# Create SQLite database file if it doesn't exist
RUN mkdir -p database && touch database/database.sqlite && chmod 666 database/database.sqlite

# Permissions for storage and cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/database

# Expose port 80
EXPOSE 80

# Start script
RUN echo '#!/bin/sh\n\
    php artisan migrate --force\n\
    # Seed roles and demo data if no users exist\n\
    php artisan db:seed --class=DatabaseSeeder --force\n\
    php-fpm -D\n\
    nginx -g "daemon off;"\n\
    ' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
