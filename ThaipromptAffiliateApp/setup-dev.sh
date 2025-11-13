#!/bin/bash

# Development environment setup script
# Usage: ./setup-dev.sh

set -e

echo "🚀 Setting up Thaiprompt Affiliate Control App Development Environment"
echo ""

# Check .NET SDK
echo "✅ Checking .NET SDK..."
if ! command -v dotnet &> /dev/null; then
    echo "❌ .NET SDK not found!"
    echo "Please install .NET 8.0 SDK from: https://dotnet.microsoft.com/download"
    exit 1
fi

DOTNET_VERSION=$(dotnet --version)
echo "   Found .NET SDK version: $DOTNET_VERSION"

# Check MAUI workload
echo ""
echo "✅ Checking MAUI workload..."
if ! dotnet workload list | grep -q "maui"; then
    echo "❌ MAUI workload not installed!"
    echo "Installing MAUI workload..."
    sudo dotnet workload install maui
else
    echo "   MAUI workload is installed"
fi

# Restore packages
echo ""
echo "📦 Restoring NuGet packages..."
dotnet restore

# Check Android SDK (for Linux/macOS)
echo ""
echo "✅ Checking Android development tools..."
if command -v adb &> /dev/null; then
    ADB_VERSION=$(adb --version | head -n 1)
    echo "   Found ADB: $ADB_VERSION"
else
    echo "⚠️  ADB not found - Android development tools may not be installed"
fi

# Make scripts executable
echo ""
echo "✅ Making scripts executable..."
chmod +x build.sh run-android.sh clean.sh setup-dev.sh

# Create local config file if not exists
echo ""
echo "✅ Checking configuration..."
if [ ! -f "appsettings.local.json" ]; then
    cat > appsettings.local.json << 'EOF'
{
  "ApiBaseUrl": "http://10.0.2.2:8000/api/v1",
  "Environment": "Development"
}
EOF
    echo "   Created appsettings.local.json"
else
    echo "   Configuration file exists"
fi

echo ""
echo "🎉 Development environment setup completed!"
echo ""
echo "📚 Next steps:"
echo "   1. Configure API URL in Helpers/Constants.cs"
echo "   2. Run ./build.sh to build the project"
echo "   3. Open ThaipromptAffiliateApp.sln in Visual Studio"
echo "   4. Select target platform and run!"
echo ""
echo "💡 Quick commands:"
echo "   ./build.sh android       - Build for Android"
echo "   ./run-android.sh         - Run on Android"
echo "   ./clean.sh               - Clean build artifacts"
