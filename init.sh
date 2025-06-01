#!/bin/bash

echo "🔧 Running Laravel setup..."

docker exec -it laravel-app composer install
docker exec -it laravel-app php artisan migrate --force
docker exec -it laravel-app php artisan config:clear
docker exec -it laravel-app php artisan cache:clear
docker exec -it laravel-app php artisan route:clear
docker exec -it laravel-app php artisan view:clear
docker exec -it laravel-app npm install
docker exec -it laravel-app npm run build

echo "✅ Laravel is ready!"
