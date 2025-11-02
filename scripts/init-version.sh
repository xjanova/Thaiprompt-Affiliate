#!/bin/bash

# Script สำหรับสร้าง initial version tag และทดสอบระบบ versioning

set -e

echo "🚀 Thai Prompt Affiliate - Version Initialization"
echo "=================================================="
echo ""

# อ่านเวอร์ชั่นปัจจุบัน
CURRENT_VERSION=$(cat VERSION 2>/dev/null || echo "1.0.0")
echo "📦 Current version: $CURRENT_VERSION"

# สร้าง git tag
TAG_NAME="v${CURRENT_VERSION}"
echo "🏷️  Creating initial tag: $TAG_NAME"

# ตรวจสอบว่ามี tag อยู่แล้วหรือไม่
if git rev-parse "$TAG_NAME" >/dev/null 2>&1; then
    echo "⚠️  Tag $TAG_NAME already exists"
    echo ""
    read -p "Do you want to delete and recreate? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git tag -d "$TAG_NAME"
        echo "✓ Deleted old tag"
    else
        echo "Skipping tag creation"
        exit 0
    fi
fi

# สร้าง tag ใหม่
git tag -a "$TAG_NAME" -m "Initial release v${CURRENT_VERSION}"
echo "✓ Created tag $TAG_NAME"
echo ""

# ถามว่าต้องการ push หรือไม่
echo "Next steps:"
echo "1. Push tag to remote: git push origin $TAG_NAME"
echo "2. Push all tags: git push --tags"
echo ""
read -p "Do you want to push tags now? (y/N): " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then
    git push origin "$TAG_NAME" && echo "✓ Pushed tag to remote" || echo "✗ Failed to push tag"
fi

echo ""
echo "✅ Version initialization complete!"
echo ""
echo "📋 Summary:"
echo "  - Version: $CURRENT_VERSION"
echo "  - Tag: $TAG_NAME"
echo ""
echo "🎯 Next time you merge a PR with 'feat:' or 'fix:' prefix,"
echo "   the automatic versioning will work!"
