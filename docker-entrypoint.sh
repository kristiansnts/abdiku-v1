#!/bin/sh
set -e

echo "Starting Laravel application..."

# Wait for database to be ready (optional, adjust timeout as needed)
echo "Waiting for database connection..."
php artisan db:monitor --max=30 2>/dev/null || true

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Cache configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link (ignore if already exists)
echo "Creating storage link..."
php artisan storage:link 2>/dev/null || true

# Generate Filament assets
echo "Generating Filament assets..."
php artisan filament:assets

echo "Starting Octane server..."
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8080
