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

# STARTUP SCRIPT - THIS WILL RUN MIGRATIONS
CMD ["sh", "-c", "\
    echo '==========================================' && \
    echo '🚀 Starting IntelliMeet Application...' && \
    echo '==========================================' && \
    echo '' && \
    echo '📊 Running database migrations...' && \
    php artisan migrate --force --no-interaction && \
    echo '✅ Migrations completed!' && \
    echo '' && \
    echo '🔑 Generating app key...' && \
    php artisan key:generate --force && \
    echo '' && \
    echo '🔗 Creating storage link...' && \
    php artisan storage:link || true && \
    echo '' && \
    echo '⚡ Optimizing...' && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    echo '' && \
    echo '🚀 Starting server on port ${PORT:-10000}...' && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-10000} \
"]
