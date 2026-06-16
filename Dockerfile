FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php

# Create necessary directories
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

# Set environment
ENV PORT=10000

# Expose port
EXPOSE 10000

# Startup script
RUN echo '#!/bin/bash\n\
echo "Starting application..."\n\
echo "Database Host: ${DB_HOST:-not set}"\n\
echo "Database Name: ${DB_DATABASE:-not set}"\n\
echo "Running migrations..."\n\
php artisan migrate --force --no-interaction\n\
echo "Creating storage link..."\n\
php artisan storage:link || echo "Storage link already exists"\n\
echo "Optimizing..."\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
echo "Starting server on port ${PORT:-10000}..."\n\
php artisan serve --host=0.0.0.0 --port=${PORT:-10000}' > /start.sh \
    && chmod +x /start.sh

CMD ["/start.sh"]
CMD ["bash", "deploy.sh"]
