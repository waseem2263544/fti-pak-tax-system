#!/bin/bash

# FTI Pak Tax Management System - cPanel Installation Script
# This script automates the setup process

echo "=================================================="
echo "FTI Pak Tax Management System - Setup Wizard"
echo "=================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}Creating .env file...${NC}"
    cp .env.example .env
    
    # Generate application key
    echo -e "${YELLOW}Generating application key...${NC}"
    php artisan key:generate
else
    echo -e "${GREEN}.env file already exists${NC}"
fi

# Check dependencies
echo ""
echo -e "${YELLOW}Checking system requirements...${NC}"

# Check PHP version
PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}')
echo "PHP Version: $PHP_VERSION"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo -e "${RED}Composer not found. Please install Composer.${NC}"
    exit 1
fi
echo "Composer: OK"

# Install Laravel dependencies
echo ""
echo -e "${YELLOW}Installing Laravel dependencies...${NC}"
composer install --no-dev --optimize-autoloader

# Run migrations
echo ""
echo -e "${YELLOW}Running database migrations...${NC}"
php artisan migrate --force

# Seed database
echo ""
echo -e "${YELLOW}Seeding database with default data...${NC}"
php artisan db:seed --force

# Create storage links
echo ""
echo -e "${YELLOW}Creating storage links...${NC}"
php artisan storage:link

# Set permissions
echo ""
echo -e "${YELLOW}Setting correct permissions...${NC}"
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage

echo ""
echo -e "${GREEN}=================================================="
echo "Setup completed successfully!"
echo "=================================================="
echo ""
echo -e "${GREEN}Next steps:${NC}"
echo "1. Edit .env file with your database credentials"
echo "2. Run: php artisan app:setup (to create admin user)"
echo "3. Configure cron jobs in cPanel:"
echo "   - cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1"
echo "4. Access the application at your domain"
echo ""
