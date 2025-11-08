#!/bin/bash

echo "============================================"
echo "  ติดตั้งระบบหมอดูไพ่ทาโร่ต์"
echo "  Tarot Reading System Installation"
echo "============================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo -e "${RED}Error: Do not run this script as root${NC}"
   exit 1
fi

# Step 1: Run migrations
echo -e "${YELLOW}[1/4] Running migrations...${NC}"
php artisan migrate --path=database/migrations/2025_11_07_000001_create_tarot_cards_table.php
php artisan migrate --path=database/migrations/2025_11_07_000002_create_tarot_card_back_images_table.php
php artisan migrate --path=database/migrations/2025_11_07_000003_create_tarot_spread_types_table.php
php artisan migrate --path=database/migrations/2025_11_07_000004_create_tarot_reading_categories_table.php
php artisan migrate --path=database/migrations/2025_11_07_000005_create_tarot_readings_table.php
php artisan migrate --path=database/migrations/2025_11_07_000006_create_tarot_reading_cards_table.php
php artisan migrate --path=database/migrations/2025_11_07_000007_create_tarot_user_limits_table.php
php artisan migrate --path=database/migrations/2025_11_07_000008_create_tarot_settings_table.php

if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Failed to run migrations${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Migrations completed successfully${NC}"
echo ""

# Step 2: Run seeders
echo -e "${YELLOW}[2/4] Seeding database with tarot cards and initial data...${NC}"
php artisan db:seed --class=TarotSystemSeeder

if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Failed to seed database${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Database seeded successfully (78 tarot cards loaded)${NC}"
echo ""

# Step 3: Create directories for images
echo -e "${YELLOW}[3/4] Creating image directories...${NC}"
mkdir -p public/images/tarot
mkdir -p public/images/tarot/cards
mkdir -p public/images/tarot/card-backs
mkdir -p storage/app/public/tarot/cards
mkdir -p storage/app/public/tarot/card-backs

# Set permissions
chmod -R 755 public/images/tarot
chmod -R 755 storage/app/public/tarot

echo -e "${GREEN}✓ Image directories created${NC}"
echo ""

# Step 4: Create storage link if not exists
echo -e "${YELLOW}[4/4] Creating storage link...${NC}"
if [ ! -L public/storage ]; then
    php artisan storage:link
    echo -e "${GREEN}✓ Storage link created${NC}"
else
    echo -e "${GREEN}✓ Storage link already exists${NC}"
fi

echo ""
echo -e "${GREEN}============================================"
echo "  ✓ Installation Complete!"
echo "============================================${NC}"
echo ""
echo "Next steps:"
echo "1. Upload tarot card images to: public/images/tarot/cards/"
echo "2. Upload card back images via Admin panel: /admin/tarot/card-backs"
echo "3. Configure categories and pricing: /admin/tarot/categories"
echo "4. Access the system:"
echo "   - Frontend: /tarot"
echo "   - Admin: /admin/tarot"
echo ""
echo "For more information, read: TAROT_SYSTEM_README.md"
echo ""
