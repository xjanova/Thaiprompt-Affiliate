#!/bin/bash
# 🚀 Quick Fix: Convert Route Names to URLs
# This script ONLY converts existing route names to URLs in the database

echo "🔄 Quick Fix: Converting route names to URLs..."
echo ""

# Convert route names to URLs
echo "📝 Step 1: Converting data in database..."
php convert-routes-to-urls.php
echo "✅ Conversion complete"
echo ""

# Clear caches
echo "🧹 Step 2: Clearing caches..."
php artisan optimize:clear
php artisan cache:clear
echo "✅ Caches cleared"
echo ""

echo "✨ Quick fix complete!"
echo ""
echo "🧪 Next steps:"
echo "  1. Clear your browser cache (Ctrl+F5 or Cmd+Shift+R)"
echo "  2. Reload the page and test menu navigation"
echo ""
echo "📊 Conversion examples:"
echo "  • admin.kyc.index      → /admin/kyc"
echo "  • admin.dashboard      → /admin/dashboard"
echo "  • seller.products.index → /seller/products"
echo ""
