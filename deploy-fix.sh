#!/bin/bash
#
# Deployment script to fix the HRM performance route issue
# Run this on the production server: member123.thaiprompt.online
#

echo "========================================="
echo "Deploying HRM Performance Route Fix"
echo "========================================="
echo ""

# Step 1: Pull latest changes
echo "Step 1: Pulling latest changes from Git..."
git fetch origin
git pull origin claude/fix-missing-performance-route-011CUs634caCoPgePmuMFqVG
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to pull changes"
    exit 1
fi
echo "✓ Changes pulled successfully"
echo ""

# Step 2: Clear all Laravel caches
echo "Step 2: Clearing Laravel caches..."

# Clear view cache
php artisan view:clear
if [ $? -eq 0 ]; then
    echo "✓ View cache cleared"
else
    echo "⚠ Warning: Could not clear view cache"
fi

# Clear route cache
php artisan route:clear
if [ $? -eq 0 ]; then
    echo "✓ Route cache cleared"
else
    echo "⚠ Warning: Could not clear route cache"
fi

# Clear config cache
php artisan config:clear
if [ $? -eq 0 ]; then
    echo "✓ Config cache cleared"
else
    echo "⚠ Warning: Could not clear config cache"
fi

# Clear application cache
php artisan cache:clear
if [ $? -eq 0 ]; then
    echo "✓ Application cache cleared"
else
    echo "⚠ Warning: Could not clear application cache"
fi

echo ""
echo "========================================="
echo "Deployment Complete!"
echo "========================================="
echo ""
echo "The following changes have been deployed:"
echo "- Fixed admin.hrm.performance.index route"
echo "- Added 8 new HRM controllers"
echo "- Updated route references in admin menu"
echo ""
echo "Please test the HRM Performance menu now."
echo ""
