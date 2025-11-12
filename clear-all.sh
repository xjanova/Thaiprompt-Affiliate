#!/bin/bash

echo "🧹 Clearing all caches..."

# Clear Laravel caches
rm -rf storage/framework/views/*
rm -rf storage/framework/cache/data/*
rm -rf bootstrap/cache/*.php

echo "✅ Blade views cleared"
echo "✅ Application cache cleared"
echo "✅ Bootstrap cache cleared"

# Clear route cache file
if [ -f bootstrap/cache/routes-v7.php ]; then
    rm bootstrap/cache/routes-v7.php
    echo "✅ Routes cache cleared"
fi

# Clear config cache file
if [ -f bootstrap/cache/config.php ]; then
    rm bootstrap/cache/config.php
    echo "✅ Config cache cleared"
fi

echo ""
echo "🎉 All caches cleared successfully!"
echo "📝 Please hard refresh your browser: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)"
