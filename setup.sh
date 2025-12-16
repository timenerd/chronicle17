#!/bin/bash

echo "========================================"
echo " TTRPG Session Recap - Setup Script"
echo "========================================"
echo ""

# Check if .env exists in parent directory
if [ ! -f ../.env ]; then
    echo "Creating .env file in parent directory (outside web root)..."
    cp .env.example ../.env
    echo ""
    echo "IMPORTANT: Edit ../.env (in parent directory) and add your API keys:"
    echo "- OPENAI_API_KEY"
    echo "- ANTHROPIC_API_KEY"
    echo "- Database credentials"
    echo ""
    read -p "Press enter to continue after editing ../.env..."
fi

# Install Composer dependencies
echo "Installing PHP dependencies..."
composer install
if [ $? -ne 0 ]; then
    echo "ERROR: Composer install failed"
    exit 1
fi
echo ""

# Database setup instructions
echo ""
echo "========================================"
echo " Database Setup"
echo "========================================"
echo ""
echo "Please run the following to create the database:"
echo "  mysql -u root -p < schema.sql"
echo ""
echo "Or manually in MySQL:"
echo "  mysql> source schema.sql;"
echo ""
read -p "Press enter once database is created..."

echo ""
echo "========================================"
echo " Setup Complete!"
echo "========================================"
echo ""
echo "Next steps:"
echo "1. Edit ../.env (parent directory) with your API keys (if not done)"
echo "2. Start the background worker:"
echo "     php worker.php"
echo "3. Access the app at http://localhost/ttrpg-recap"
echo ""
echo "Happy adventuring! ⚔️"
