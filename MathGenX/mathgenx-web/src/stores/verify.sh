#!/bin/bash
echo "🔍 Checking Docker Services..."

echo "📦 Services Status:"
docker compose ps

echo -e "\n🏥 Health Checks:"
echo "Laravel API:"
curl -s http://localhost:8080/api/up 2>/dev/null && echo " ✅" || echo " ❌ Failed"

echo -e "\nRAG API:"
docker compose exec -T rag-api curl -s http://localhost:8000/health 2>/dev/null && echo " ✅" || echo " ❌ Failed"

echo -e "\n💾 Database:"
docker compose exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo '✅ Connected';" 2>/dev/null || echo "❌ Failed"

echo -e "\n🔴 Redis:"
docker compose exec -T app php artisan tinker --execute="Redis::ping(); echo '✅ Connected';" 2>/dev/null || echo "❌ Failed"

echo -e "\n✅ Verification complete!"
