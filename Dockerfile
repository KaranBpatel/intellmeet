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

# Copy application files (including .env)
COPY . .

# Remove .env.example if it exists and rename .env to .env.example
RUN if [ -f .env ]; then mv .env .env.example; fi

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

# Startup script - will create .env from environment variables
RUN echo '#!/bin/bash\n\
echo "Starting application..."\n\
echo "Creating .env from environment variables..."\n\
cat > .env << EOF\n\
APP_NAME=${APP_NAME:-IntelliMeet}\n\
APP_ENV=${APP_ENV:-production}\n\
APP_KEY=${APP_KEY}\n\
APP_DEBUG=${APP_DEBUG:-false}\n\
APP_URL=${APP_URL}\n\
\n\
DB_CONNECTION=pgsql\n\
DB_HOST=${DB_HOST}\n\
DB_PORT=${DB_PORT:-5432}\n\
DB_DATABASE=${DB_DATABASE}\n\
DB_USERNAME=${DB_USERNAME}\n\
DB_PASSWORD=${DB_PASSWORD}\n\
\n\
CACHE_DRIVER=file\n\
SESSION_DRIVER=file\n\
SESSION_LIFETIME=120\n\
\n\
MAIL_MAILER=smtp\n\
MAIL_HOST=smtp.gmail.com\n\
MAIL_PORT=587\n\
MAIL_USERNAME=${MAIL_USERNAME}\n\
MAIL_PASSWORD=${MAIL_PASSWORD}\n\
MAIL_ENCRYPTION=tls\n\
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}\n\
MAIL_FROM_NAME="${APP_NAME:-IntelliMeet}"\n\
\n\
OPENAI_API_KEY=${OPENAI_API_KEY}\n\
OPENAI_ORGANIZATION=${OPENAI_ORGANIZATION}\n\
OPENAI_MAX_TOKENS=${OPENAI_MAX_TOKENS:-2000}\n\
\n\
PUSHER_APP_ID=dummy_id\n\
PUSHER_APP_KEY=dummy_key\n\
PUSHER_APP_SECRET=dummy_secret\n\
PUSHER_APP_CLUSTER=ap1\n\
COMPOSER_NO_SCRIPTS=1\n\
EOF\n\
\n\
echo "Generated .env file:"\n\
cat .env\n\
\n\
echo "Running migrations..."\n\
php artisan migrate --force --no-interaction || echo "Migration skipped"\n\
echo "Optimizing..."\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
echo "Starting server on port ${PORT:-10000}..."\n\
php artisan serve --host=0.0.0.0 --port=${PORT:-10000}' > /start.sh \
    && chmod +x /start.sh

CMD ["/start.sh"]
