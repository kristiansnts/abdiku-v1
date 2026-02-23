#!/bin/sh
set -e

echo "Starting Laravel application v1.0.1..."

: "${WAIT_FOR_DB:=true}"
: "${RUN_MIGRATIONS:=false}"
: "${RUN_CACHE:=true}"
: "${RUN_STORAGE_LINK:=true}"
: "${RUN_FILAMENT_ASSETS:=true}"

if [ "$WAIT_FOR_DB" = "true" ]; then
  echo "Waiting for database connection..."
  php artisan db:monitor --max=30 2>/dev/null || true
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
  echo "Running migrations..."
  php artisan migrate --force
fi

if [ "$RUN_CACHE" = "true" ]; then
  echo "Caching configuration..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

if [ "$RUN_STORAGE_LINK" = "true" ]; then
  echo "Creating storage link..."
  php artisan storage:link 2>/dev/null || true
fi

if [ "$RUN_FILAMENT_ASSETS" = "true" ]; then
  echo "Generating Filament assets..."
  php artisan filament:assets
fi

echo "Starting Octane server..."
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000
