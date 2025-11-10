#!/bin/bash

# Laravel Cache Clearing Script
# Run this on your production server to clear all Laravel caches

echo "🧹 Clearing Laravel caches..."

# Clear route cache
php artisan route:clear
echo "✓ Route cache cleared"

# Clear config cache
php artisan config:clear
echo "✓ Config cache cleared"

# Clear application cache
php artisan cache:clear
echo "✓ Application cache cleared"

# Clear view cache
php artisan view:clear
echo "✓ View cache cleared"

# Optimize for production (optional - rebuild caches)
# Uncomment the lines below if you want to rebuild caches after clearing
# php artisan route:cache
# php artisan config:cache
# php artisan view:cache

echo ""
echo "✅ All caches cleared successfully!"
echo "The seller.analytics route should now work properly."
