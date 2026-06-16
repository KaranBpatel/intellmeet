FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

EXPOSE 10000

# Startup script – removes SQLite, runs migrations, clears cache, starts server
RUN echo '#!/bin/bash\n\
rm -f database/database.sqlite\n\
echo "Running migrations..."\n\
php artisan migrate --force --no-interaction || echo "Migration failed, continuing..."\n\
echo "Clearing cache..."\n\
php artisan config:clear\n\
php artisan cache:clear\n\
php artisan view:clear\n\
echo "Starting server..."\n\
php artisan serve --host=0.0.0.0 --port=${PORT:-10000}' > /start.sh \
    && chmod +x /start.sh

CMD ["/start.sh"]
