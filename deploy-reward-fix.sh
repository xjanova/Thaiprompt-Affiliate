#!/bin/bash
#
# Deployment script to fix the undefined $reward variable issue
# Run this on the production server: member123.thaiprompt.online
#

echo "========================================="
echo "Deploying LINE Signup Reward Fix"
echo "========================================="
echo ""

# Step 1: Pull latest changes
echo "Step 1: Pulling latest changes from Git..."
git fetch origin
git pull origin claude/fix-undefined-reward-variable-012wn6rV3aV5geqRoeJ6cNRB
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to pull changes"
    exit 1
fi
echo "✓ Changes pulled successfully"
echo ""

# Step 2: Clear all Laravel caches
echo "Step 2: Clearing Laravel caches..."

# Clear view cache
php artisan view:clear 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✓ View cache cleared"
else
    echo "⚠ Warning: Could not clear view cache (may not exist)"
fi

# Clear route cache
php artisan route:clear 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✓ Route cache cleared"
else
    echo "⚠ Warning: Could not clear route cache"
fi

# Clear config cache
php artisan config:clear 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✓ Config cache cleared"
else
    echo "⚠ Warning: Could not clear config cache"
fi

# Clear application cache
php artisan cache:clear 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✓ Application cache cleared"
else
    echo "⚠ Warning: Could not clear application cache"
fi

# Step 3: Manually clear cache directories (fallback)
echo ""
echo "Step 3: Manually clearing cache directories..."
rm -rf storage/framework/views/* 2>/dev/null && echo "✓ View cache directory cleared"
rm -rf storage/framework/cache/* 2>/dev/null && echo "✓ Application cache directory cleared"
rm -rf bootstrap/cache/*.php 2>/dev/null && echo "✓ Bootstrap cache cleared"

# Step 4: Clear OPcache (if available)
echo ""
echo "Step 4: Attempting to clear OPcache..."
if command -v php &> /dev/null; then
    php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo '✓ OPcache cleared'; } else { echo '⚠ OPcache not available'; }" 2>/dev/null
fi

echo ""
echo "========================================="
echo "Deployment Complete!"
echo "========================================="
echo ""
echo "The following changes have been deployed:"
echo "- Fixed undefined \$reward variable in create form"
echo "- Added LineSignupReward instance to create() method"
echo "- All caches cleared"
echo ""
echo "Please test the LINE Signup Rewards create page now:"
echo "→ https://member123.thaiprompt.online/admin/line-membership-signup/rewards/create"
echo ""
