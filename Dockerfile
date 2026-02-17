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

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Fix permissions for the build process
RUN chown -R www-data:www-data /var/www

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies and build assets
RUN npm install && npm run build

# Setup Nginx
COPY .render/nginx.conf /etc/nginx/sites-available/default

# Ensure the database directory exists and has correct permissions
RUN mkdir -p database && touch database/database.sqlite && \
    chown -R www-data:www-data /var/www/database /var/www/storage /var/www/bootstrap/cache /var/www/public

# Expose port 80
EXPOSE 80

# Start script
RUN echo '#!/bin/sh\n\
    php artisan migrate --force\n\
    php artisan db:seed --class=DatabaseSeeder --force\n\
    php artisan storage:link\n\
    php-fpm -D\n\
    nginx -g "daemon off;"\n\
    ' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
