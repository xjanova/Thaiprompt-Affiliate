#!/bin/bash

# Schema Verification Helper Script
# Quick and easy schema checking for LINE Bot AI System

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

echo ""
echo -e "${MAGENTA}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${MAGENTA}║                                                           ║${NC}"
echo -e "${MAGENTA}║         🔍 Schema Verification System                     ║${NC}"
echo -e "${MAGENTA}║         LINE Bot AI - Database Schema Checker            ║${NC}"
echo -e "${MAGENTA}║                                                           ║${NC}"
echo -e "${MAGENTA}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found!${NC}"
    echo "Please run this script from your Laravel project root directory."
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}Error: .env file not found!${NC}"
    echo "Please ensure .env file exists with database configuration."
    exit 1
fi

# Menu
echo -e "${BLUE}Choose an option:${NC}"
echo ""
echo "  1) 🔍 Verify Schema (Check for issues)"
echo "  2) 🔧 Verify & Generate Fix Statements"
echo "  3) 📸 Create/Update Schema Snapshot"
echo "  4) 📋 Check Specific Table"
echo "  5) ℹ️  Show Help"
echo ""
read -p "Enter choice [1-5]: " choice

case $choice in
    1)
        echo ""
        echo -e "${BLUE}Running schema verification...${NC}"
        echo ""
        php artisan schema:verify
        ;;
    2)
        echo ""
        echo -e "${BLUE}Running schema verification with fix statements...${NC}"
        echo ""
        php artisan schema:verify --fix
        ;;
    3)
        echo ""
        echo -e "${BLUE}Creating schema snapshot...${NC}"
        echo ""
        php artisan schema:verify --snapshot
        echo ""
        echo -e "${GREEN}✓ Snapshot created successfully!${NC}"
        echo "Location: database/schema_snapshot.json"
        ;;
    4)
        echo ""
        read -p "Enter table name: " table_name
        echo ""
        echo -e "${BLUE}Checking table: $table_name${NC}"
        echo ""
        php artisan schema:verify --table="$table_name"
        ;;
    5)
        echo ""
        echo -e "${YELLOW}Schema Verification Help${NC}"
        echo ""
        echo "This tool helps you verify that your database schema matches"
        echo "the expected structure defined in migrations."
        echo ""
        echo "Use cases:"
        echo "  • After importing SQL files"
        echo "  • After manual database modifications"
        echo "  • Before running migrations"
        echo "  • Troubleshooting schema issues"
        echo ""
        echo "Commands:"
        echo "  php artisan schema:verify              - Basic verification"
        echo "  php artisan schema:verify --fix        - Generate ALTER TABLE statements"
        echo "  php artisan schema:verify --snapshot   - Create schema snapshot"
        echo "  php artisan schema:verify --table=NAME - Check specific table"
        echo ""
        ;;
    *)
        echo ""
        echo -e "${RED}Invalid choice!${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}Done!${NC}"
echo ""
