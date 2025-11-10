#!/bin/bash
# 🚀 Deploy Menu System Changes (url field refactoring)
# This script applies all the menu system changes to your database

echo "🔄 Deploying menu system changes..."
echo ""

# Step 1: Clear all caches
echo "📦 Step 1: Clearing all Laravel caches..."
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "✅ Caches cleared"
echo ""

# Step 2: Run migrations (if needed)
echo "🗄️  Step 2: Running migrations..."
php artisan migrate --force
echo "✅ Migrations complete"
echo ""

# Step 3: Run the updated seeder
echo "🌱 Step 3: Running WindowsUiSeeder with new 'url' field structure..."
php artisan db:seed --class=WindowsUiSeeder --force
echo "✅ Seeder complete"
echo ""

# Step 4: Clear caches again (to ensure fresh data)
echo "🧹 Step 4: Clearing caches again to ensure fresh data..."
php artisan config:clear
php artisan cache:clear
echo "✅ Final cache clear complete"
echo ""

echo "✨ Deployment complete!"
echo ""
echo "📋 Summary of changes:"
echo "  - All menu data now uses 'url' field instead of 'route'"
echo "  - URLs are stored directly (e.g., /admin/dashboard)"
echo "  - Parent menus with submenus use '#' as their URL"
echo "  - WindowsUiSeeder updated with correct data structure"
echo "  - WindowsUiController updated to validate/save 'url' field"
echo "  - Admin UI updated to show 'URL' inputs"
echo "  - Menu component simplified (no route conversion needed)"
echo ""
echo "🧪 Next steps:"
echo "  1. Clear your browser cache (Ctrl+F5 or Cmd+Shift+R)"
echo "  2. Log in and test the menu functionality"
echo "  3. Check that menu links show correct URLs when hovering"
echo "  4. Verify menu clicks navigate to correct pages"
echo ""
