#!/bin/bash

echo "========================================="
echo "🔧 Menu Issue Diagnostic & Fix Script"
echo "========================================="
echo ""

# Step 1: Check .env file
echo "📋 Step 1: Checking .env file..."
if [ ! -f ".env" ]; then
    echo "❌ .env file not found!"
    if [ -f ".env.example" ]; then
        echo "📝 Creating .env from .env.example..."
        cp .env.example .env
        echo "✅ .env file created"
        echo "⚠️  Please edit .env file to configure your database connection"
        echo ""
    else
        echo "❌ .env.example also not found!"
        exit 1
    fi
else
    echo "✅ .env file exists"
    echo ""
fi

# Step 2: Check vendor directory
echo "📦 Step 2: Checking composer dependencies..."
if [ ! -d "vendor" ]; then
    echo "❌ vendor directory not found!"
    echo "📥 Installing composer dependencies..."
    composer install
    if [ $? -ne 0 ]; then
        echo "❌ composer install failed!"
        exit 1
    fi
    echo "✅ Composer dependencies installed"
    echo ""
else
    echo "✅ vendor directory exists"
    echo ""
fi

# Step 3: Generate app key if needed
echo "🔑 Step 3: Checking APP_KEY..."
if ! grep -q "APP_KEY=base64:" .env; then
    echo "❌ APP_KEY not set"
    echo "🔧 Generating APP_KEY..."
    php artisan key:generate
    echo "✅ APP_KEY generated"
    echo ""
else
    echo "✅ APP_KEY is set"
    echo ""
fi

# Step 4: Clear all caches
echo "🧹 Step 4: Clearing all caches..."
php artisan optimize:clear
if [ $? -eq 0 ]; then
    echo "✅ All caches cleared"
    echo ""
else
    echo "⚠️  Could not clear caches (might be normal if app not fully set up)"
    echo ""
fi

# Step 5: Run migrations
echo "🗄️  Step 5: Running migrations..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo "✅ Migrations completed"
    echo ""
else
    echo "❌ Migrations failed!"
    echo "Please check your database configuration in .env file"
    exit 1
fi

# Step 6: Run WindowsUiSeeder
echo "🌱 Step 6: Running WindowsUiSeeder..."
php artisan db:seed --class=WindowsUiSeeder --force
if [ $? -eq 0 ]; then
    echo "✅ WindowsUiSeeder completed"
    echo ""
else
    echo "❌ WindowsUiSeeder failed!"
    exit 1
fi

# Step 7: Run ComponentSettingSeeder
echo "🌱 Step 7: Running ComponentSettingSeeder..."
php artisan db:seed --class=ComponentSettingSeeder --force
if [ $? -eq 0 ]; then
    echo "✅ ComponentSettingSeeder completed"
    echo ""
else
    echo "❌ ComponentSettingSeeder failed!"
    exit 1
fi

# Step 8: Verify data in database
echo "✅ Step 8: Verifying menu data..."
php artisan tinker --execute="
\$admin = \App\Models\WindowsUiSetting::get('windows_start_menu_items_admin');
if (\$admin) {
    echo '✅ Admin menu data found: ' . count(\$admin) . ' items\n';
} else {
    echo '❌ Admin menu data NOT found!\n';
}

\$seller = \App\Models\WindowsUiSetting::get('windows_start_menu_items_seller');
if (\$seller) {
    echo '✅ Seller menu data found: ' . count(\$seller) . ' items\n';
} else {
    echo '❌ Seller menu data NOT found!\n';
}

\$user = \App\Models\WindowsUiSetting::get('windows_start_menu_items_user');
if (\$user) {
    echo '✅ User menu data found: ' . count(\$user) . ' items\n';
} else {
    echo '❌ User menu data NOT found!\n';
}
"

echo ""
echo "========================================="
echo "🎉 Setup Complete!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Clear your browser cache (Ctrl+Shift+R)"
echo "2. Reload the website"
echo "3. Check if menu links work correctly"
echo ""
echo "If you still see issues, check the logs:"
echo "  tail -f storage/logs/laravel.log"
echo ""
