#!/bin/bash

# Build script for AI Blog Summary WordPress Plugin
# This script prepares the plugin for distribution by:
# 1. Installing production dependencies only (no dev dependencies)
# 2. Optimizing the Composer autoloader
#
# Note: Run 'npm run build' manually to build production assets

set -e

echo "🔨 Building AI Blog Summary Plugin (Composer dependencies)..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Get plugin directory
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PLUGIN_DIR"

# Check if composer is available
if ! command -v composer &> /dev/null; then
    echo -e "${RED}❌ Error: composer not found${NC}"
    echo "Please install Composer: https://getcomposer.org/download/"
    exit 1
fi

echo -e "${YELLOW}Installing production dependencies (no dev)...${NC}"
composer install --no-dev --optimize-autoloader --quiet

echo -e "${GREEN}✅ Composer build complete!${NC}"
echo ""
echo "Plugin PHP dependencies are ready."
VENDOR_SIZE=$(du -sh vendor 2>/dev/null | cut -f1 || echo "N/A")
echo "Vendor folder size: ${VENDOR_SIZE}"
echo ""
echo "📦 Next steps:"
echo "   1. Run 'npm run build' to build production assets"
echo "   2. Create distribution zip: zip -r ai-blog-summary.zip . -x@.distignore"
echo ""
echo "ℹ️  Note: Vendor folder is included (contains optimized autoloader)"
echo "   Dev dependencies are excluded (PHPUnit, WPCS, etc.)"

