#!/bin/bash

# Build script for Thaiprompt Affiliate Control App
# Usage: ./build.sh [platform] [configuration]
# Example: ./build.sh android Debug

set -e

PLATFORM=${1:-android}
CONFIGURATION=${2:-Debug}

echo "🔨 Building Thaiprompt Affiliate Control App"
echo "Platform: $PLATFORM"
echo "Configuration: $CONFIGURATION"
echo ""

# Clean previous build
echo "🧹 Cleaning previous build..."
dotnet clean

# Restore packages
echo "📦 Restoring NuGet packages..."
dotnet restore

# Build based on platform
case $PLATFORM in
    android|Android)
        echo "📱 Building for Android..."
        dotnet build -f net8.0-android -c $CONFIGURATION
        ;;
    ios|iOS)
        echo "🍎 Building for iOS..."
        dotnet build -f net8.0-ios -c $CONFIGURATION
        ;;
    windows|Windows)
        echo "🪟 Building for Windows..."
        dotnet build -f net8.0-windows10.0.19041.0 -c $CONFIGURATION
        ;;
    maccatalyst|MacCatalyst)
        echo "🖥️  Building for macOS..."
        dotnet build -f net8.0-maccatalyst -c $CONFIGURATION
        ;;
    all|All)
        echo "🌍 Building for all platforms..."
        dotnet build -c $CONFIGURATION
        ;;
    *)
        echo "❌ Unknown platform: $PLATFORM"
        echo "Available platforms: android, ios, windows, maccatalyst, all"
        exit 1
        ;;
esac

echo ""
echo "✅ Build completed successfully!"
