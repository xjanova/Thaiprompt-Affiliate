#!/bin/bash

# Script to update composer.lock for google/cloud-vision dependency
# This should be run on a machine with Composer installed

set -e  # Exit on error

echo "================================================"
echo "  Updating Composer Lock File"
echo "  for Google Cloud Vision OCR Feature"
echo "================================================"
echo ""

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Error: Composer is not installed!"
    echo "Please install Composer first: https://getcomposer.org/download/"
    exit 1
fi

echo "✓ Composer found: $(composer --version)"
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found!"
    echo "Please run this script from the project root directory."
    exit 1
fi

echo "✓ Found composer.json"
echo ""

# Backup current composer.lock if exists
if [ -f "composer.lock" ]; then
    echo "📦 Backing up current composer.lock..."
    cp composer.lock composer.lock.backup
    echo "✓ Backup created: composer.lock.backup"
    echo ""
fi

# Update composer dependencies
echo "📥 Updating Composer dependencies..."
echo "This may take a few minutes..."
echo ""

# Try with normal memory first
if composer update google/cloud-vision --no-dev --optimize-autoloader; then
    echo "✓ Composer update successful!"
else
    echo "⚠️  Standard update failed, trying with unlimited memory..."
    COMPOSER_MEMORY_LIMIT=-1 composer update google/cloud-vision --no-dev --optimize-autoloader
fi

echo ""
echo "================================================"
echo "  Composer Lock File Updated Successfully!"
echo "================================================"
echo ""

# Verify the package is installed
if composer show google/cloud-vision &> /dev/null; then
    echo "✓ Package verification:"
    composer show google/cloud-vision | head -5
    echo ""
else
    echo "⚠️  Warning: Could not verify google/cloud-vision installation"
    echo ""
fi

# Show what changed
if [ -f "composer.lock.backup" ]; then
    echo "📊 Summary of changes:"
    echo "- composer.lock has been updated"
    echo "- Old version backed up to composer.lock.backup"
    echo ""
fi

echo "================================================"
echo "  Next Steps:"
echo "================================================"
echo ""
echo "1. Review the changes:"
echo "   git diff composer.lock"
echo ""
echo "2. Commit the updated composer.lock:"
echo "   git add composer.lock"
echo "   git commit -m 'chore: update composer.lock for google/cloud-vision'"
echo ""
echo "3. Push to your branch:"
echo "   git push origin claude/setup-google-ocr-api-011CUnVwmYYKGparJkfMV2Fb"
echo ""
echo "4. Deploy to production:"
echo "   git pull && composer install --no-dev --optimize-autoloader"
echo ""
echo "================================================"
