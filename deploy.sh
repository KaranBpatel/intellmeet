#!/bin/bash
echo "Running migrations..."
php artisan migrate --force --no-interaction
echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
