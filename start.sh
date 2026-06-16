#!/bin/bash

echo "=========================================="
echo "🚀 Starting IntelliMeet Application..."
echo "=========================================="
echo ""

echo "📊 Running database migrations..."
php artisan migrate --force --no-interaction

if [ $? -eq 0 ]; then
    echo "✅ Migrations completed successfully!"
else
    echo "❌ Migration failed, but continuing..."
fi

echo ""
echo "🔑 Generating app key..."
php artisan key:generate --force

echo ""
echo "🔗 Creating storage link..."
php artisan storage:link || true

echo ""
echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "🚀 Starting server on port ${PORT:-10000}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
