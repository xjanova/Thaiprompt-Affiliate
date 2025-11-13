#!/bin/bash

# Run Android app script
# Usage: ./run-android.sh [device]

set -e

DEVICE=${1:-""}

echo "📱 Running Android App..."
echo ""

# Check if device is specified
if [ -z "$DEVICE" ]; then
    echo "📋 Available devices:"
    adb devices -l
    echo ""
    echo "💡 Tip: Specify device with ./run-android.sh [device_id]"
    echo ""
fi

# Build and run
echo "🔨 Building..."
dotnet build -f net8.0-android -c Debug

echo ""
echo "🚀 Installing and launching app..."

if [ -z "$DEVICE" ]; then
    dotnet run -f net8.0-android -c Debug
else
    dotnet run -f net8.0-android -c Debug -- --device=$DEVICE
fi

echo ""
echo "✅ App launched successfully!"
echo "📊 View logs with: adb logcat | grep ThaipromptAffiliate"
