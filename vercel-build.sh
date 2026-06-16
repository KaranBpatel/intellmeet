#!/bin/bash
set -e

echo "=========================================="
echo "🚀 Installing Composer..."
curl -sS https://getcomposer.org/installer | php

echo "📦 Installing PHP dependencies..."
php composer.phar install --no-dev --optimize-autoloader --ignore-platform-req=php

echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Build complete!"
