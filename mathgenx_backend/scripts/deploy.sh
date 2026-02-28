#!/bin/bash

# Deployment script for production
set -e

echo "🚀 Starting deployment..."

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    exit 1
fi

# Load environment variables
export $(cat .env | grep -v '^#' | xargs)

# Pull latest code (if using git)
if [ -d .git ]; then
    echo "📥 Pulling latest code..."
    git pull origin main || git pull origin master
fi

# Build frontend
echo "📦 Building frontend..."
cd mathgenx-web
npm ci
npm run build
cd ..

# Build and start containers
echo "🐳 Building and starting containers..."
docker compose build
docker compose up -d

# Wait for database to be ready
echo "⏳ Waiting for database..."
sleep 10

# Run Laravel setup
echo "⚙️  Setting up Laravel..."
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
docker compose exec -T app php artisan route:clear
docker compose exec -T app php artisan view:clear

# Run migrations
echo "🗄️  Running database migrations..."
docker compose exec -T app php artisan migrate --force

# Set permissions
echo "🔐 Setting permissions..."
docker compose exec -T app chown -R www-data:www-data /var/www/storage
docker compose exec -T app chown -R www-data:www-data /var/www/bootstrap/cache
docker compose exec -T app chmod -R 775 /var/www/storage
docker compose exec -T app chmod -R 775 /var/www/bootstrap/cache

# Create storage link
echo "🔗 Creating storage symlink..."
docker compose exec -T app php artisan storage:link || true

# Optimize Laravel
echo "⚡ Optimizing Laravel..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

# Health check
echo "🏥 Checking service health..."
docker compose ps

echo ""
echo "✅ Deployment complete!"
echo ""
echo "Services are running. Check status with:"
echo "  docker compose ps"
echo ""
echo "View logs with:"
echo "  docker compose logs -f"

