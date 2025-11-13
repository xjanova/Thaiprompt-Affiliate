#!/bin/bash

# Clean build artifacts
# Usage: ./clean.sh

set -e

echo "🧹 Cleaning Thaiprompt Affiliate Control App..."
echo ""

# Clean dotnet artifacts
echo "📦 Cleaning .NET artifacts..."
dotnet clean

# Remove bin and obj directories
echo "🗑️  Removing bin and obj directories..."
find . -type d -name "bin" -exec rm -rf {} + 2>/dev/null || true
find . -type d -name "obj" -exec rm -rf {} + 2>/dev/null || true

# Remove user-specific files
echo "🗑️  Removing user-specific files..."
find . -type f -name "*.user" -delete 2>/dev/null || true
find . -type f -name "*.suo" -delete 2>/dev/null || true

# Remove VS cache
echo "🗑️  Removing Visual Studio cache..."
rm -rf .vs 2>/dev/null || true

echo ""
echo "✅ Clean completed!"
echo "💡 Run ./build.sh to rebuild the project"
