#!/bin/bash

# TPIX Whitepaper - Clear All Cache Script
# This script clears all Laravel caches to ensure updated views are displayed

echo "🧹 Clearing all Laravel caches..."

# Clear application cache
php artisan cache:clear
echo "✅ Application cache cleared"

# Clear config cache
php artisan config:clear
echo "✅ Config cache cleared"

# Clear route cache
php artisan route:clear
echo "✅ Route cache cleared"

# Clear view cache (MOST IMPORTANT for Blade templates!)
php artisan view:clear
echo "✅ View cache cleared"

# Clear compiled views
rm -rf storage/framework/views/*.php
echo "✅ Compiled views removed"

# Clear opcache (if available)
if command -v php &> /dev/null; then
    php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo '✅ OPcache cleared\n'; } else { echo '⚠️  OPcache not available\n'; }"
fi

# Touch the Blade file to force recompilation
touch resources/views/tpix/whitepaper/index.blade.php
echo "✅ Blade template touched (force recompile)"

echo ""
echo "🎉 All caches cleared successfully!"
echo "📝 View cache was specifically cleared to update TPIX Whitepaper"
echo ""
echo "Now please refresh your browser: http://member123.thaiprompt.online/tpix/whitepaper"
