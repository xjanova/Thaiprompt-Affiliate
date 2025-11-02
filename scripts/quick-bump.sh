#!/bin/bash

# Quick version bump without Laravel dependencies

BUMP_TYPE=${1:-minor}
CURRENT_VERSION=$(cat VERSION 2>/dev/null || echo "1.0.0")

echo "📦 Current version: $CURRENT_VERSION"

# Parse version
IFS='.' read -r -a VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR=${VERSION_PARTS[0]:-1}
MINOR=${VERSION_PARTS[1]:-0}
PATCH=${VERSION_PARTS[2]:-0}

# Bump version
case $BUMP_TYPE in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    patch)
        PATCH=$((PATCH + 1))
        ;;
    *)
        echo "❌ Invalid bump type: $BUMP_TYPE"
        echo "Usage: $0 [major|minor|patch]"
        exit 1
        ;;
esac

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"
echo "🎉 New version: $NEW_VERSION"

# Update VERSION file
echo "$NEW_VERSION" > VERSION
echo "✓ Updated VERSION file"

# Update package.json
if [ -f "package.json" ]; then
    # Use jq if available, otherwise use sed
    if command -v jq &> /dev/null; then
        jq ".version = \"$NEW_VERSION\"" package.json > package.json.tmp
        mv package.json.tmp package.json
    else
        sed -i "s/\"version\": \".*\"/\"version\": \"$NEW_VERSION\"/" package.json
    fi
    echo "✓ Updated package.json"
fi

# Update CHANGELOG.md
if [ -f "CHANGELOG.md" ]; then
    DATE=$(date +%Y-%m-%d)
    TEMP_FILE=$(mktemp)

    # Read first 3 lines (header)
    head -n 3 CHANGELOG.md > "$TEMP_FILE"

    # Add new version entry
    cat >> "$TEMP_FILE" << EOF

## [v${NEW_VERSION}] - ${DATE}

### ✨ Features
- เวอร์ชั่นใหม่ v${NEW_VERSION}

### 🐛 Bug Fixes
- ไม่มีการแก้ไขบัก

### 🔧 Other Changes
- อัพเดตเวอร์ชั่นจาก v${CURRENT_VERSION} เป็น v${NEW_VERSION}

EOF

    # Add rest of the file
    tail -n +4 CHANGELOG.md >> "$TEMP_FILE"

    mv "$TEMP_FILE" CHANGELOG.md
    echo "✓ Updated CHANGELOG.md"
fi

echo ""
echo "✅ Version bumped successfully!"
echo "📊 Summary: v$CURRENT_VERSION → v$NEW_VERSION"
echo ""
echo "🔍 Review changes:"
echo "   git diff VERSION package.json CHANGELOG.md"
echo ""
echo "💾 To commit and tag:"
echo "   git add VERSION package.json CHANGELOG.md"
echo "   git commit -m \"chore: bump version to $NEW_VERSION\""
echo "   git tag -a v$NEW_VERSION -m \"Release v$NEW_VERSION\""
echo "   git push && git push --tags"
