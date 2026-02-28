#!/bin/bash

# Build Docker images script
set -e

echo "🔨 Building Docker images..."

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "Please copy .env.docker.example to .env and configure it"
    exit 1
fi

# Load environment variables
export $(cat .env | grep -v '^#' | xargs)

# Build frontend first
echo "📦 Building Vue.js frontend..."
cd mathgenx-web
npm ci
npm run build
cd ..

# Build Docker images
echo "🐳 Building Docker containers..."
docker compose build --no-cache

echo "✅ Build complete!"
echo ""
echo "To start the containers, run:"
echo "  docker compose up -d"

