#!/bin/bash

# Quick script to bump version manually

BUMP_TYPE=${1:-patch}

echo "🔄 Bumping version ($BUMP_TYPE)..."
php artisan version:bump $BUMP_TYPE

echo ""
echo "Files updated. Review changes:"
git diff VERSION package.json CHANGELOG.md

echo ""
read -p "Do you want to commit these changes? (y/N): " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then
    NEW_VERSION=$(cat VERSION)
    git add VERSION package.json CHANGELOG.md
    git commit -m "chore: bump version to $NEW_VERSION"

    # Create tag
    git tag -a "v${NEW_VERSION}" -m "Release v${NEW_VERSION}"

    echo "✓ Committed and tagged"
    echo ""
    echo "Next: git push && git push --tags"
fi
