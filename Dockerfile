FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip nginx libfreetype6-dev libjpeg62-turbo-dev

# Install Node.js for asset building
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install PHP and JS dependencies, then build assets
RUN composer install --no-dev --optimize-autoloader && \
    npm install && \
    npm run build && \
    cp public/build/.vite/manifest.json public/build/manifest.json || true

# Setup Nginx config
COPY .render/nginx.conf /etc/nginx/sites-available/default

# Ensure necessary directories exist and have correct permissions
RUN mkdir -p database storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache && \
    touch database/database.sqlite && \
    chown -R www-data:www-data /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/database

# Expose port 80
EXPOSE 80

# Production Start Script (NO SEEDING, only migrations)
RUN echo '#!/bin/sh\n\
    php artisan migrate --force\n\
    php artisan storage:link\n\
    php artisan config:cache\n\
    php artisan route:cache\n\
    php artisan view:cache\n\
    php-fpm -D\n\
    nginx -g "daemon off;"\n\
    ' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
