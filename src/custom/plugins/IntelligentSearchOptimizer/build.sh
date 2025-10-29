#!/bin/bash

# Build admin assets
echo "Building admin assets..."
cd ../../.. # Go to project root
./bin/build-administration.sh

# Install and activate plugin
echo "Installing plugin..."
php bin/console plugin:install --activate IntelligentSearchOptimizer

# Run migrations
echo "Running migrations..."
php bin/console database:migrate --all IntelligentSearchOptimizer

# Clear cache
echo "Clearing cache..."
php bin/console cache:clear

echo "Plugin installation complete!"