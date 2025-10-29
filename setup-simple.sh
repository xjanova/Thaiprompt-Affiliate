#!/bin/bash

echo "========================================="
echo "Simple Setup Script for Login Testing"
echo "========================================="
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "Error: composer.json not found. Please run this script from the project root."
    exit 1
fi

echo "[1/6] Checking .env file..."
if [ ! -f ".env" ]; then
    echo "Copying .env.example to .env..."
    cp .env.example .env
fi

echo "[2/6] Creating SQLite database directory..."
mkdir -p database

echo "[3/6] Creating empty SQLite database..."
touch database/database.sqlite

echo "[4/6] Updating .env for SQLite..."
# Update DB connection to SQLite
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
sed -i 's/^DB_HOST=/#DB_HOST=/' .env
sed -i 's/^DB_PORT=/#DB_PORT=/' .env
sed -i 's/^DB_DATABASE=/#DB_DATABASE=/' .env
sed -i 's/^DB_USERNAME=/#DB_USERNAME=/' .env
sed -i 's/^DB_PASSWORD=/#DB_PASSWORD=/' .env

echo "[5/6] Simple database setup complete!"
echo ""
echo "Database file created at: database/database.sqlite"
echo ""

echo "[6/6] Next steps:"
echo "1. You need PHP installed to run Laravel"
echo "2. Run: composer install"
echo "3. Run: php artisan key:generate"
echo "4. Run: php artisan migrate"
echo "5. Run: php artisan db:seed (if you have seeders)"
echo "6. Run: php artisan serve"
echo ""
echo "Then visit: http://localhost:8000/login"
echo ""
echo "========================================="
echo "Setup script completed!"
echo "========================================="
