#!/bin/bash

echo "🧹 Limpiando cache y sesiones de MEZZIX..."

cd ~/Documentos/mezzix

# Limpiar cache de Laravel
echo "📦 Limpiando cache de configuración..."
php artisan config:clear

echo "📦 Limpiando cache de rutas..."
php artisan route:clear

echo "📦 Limpiando cache de vistas..."
php artisan view:clear

# Limpiar cache y sesiones con sudo
echo "🗑️  Limpiando archivos de cache (requiere sudo)..."
sudo rm -rf storage/framework/cache/data/*
sudo rm -rf storage/framework/sessions/*
sudo rm -rf storage/framework/views/*

# Ajustar permisos
echo "🔐 Ajustando permisos..."
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache

# Recompilar
echo "⚙️  Recompilando configuración..."
php artisan config:cache

echo ""
echo "✅ Limpieza completa!"
echo ""
echo "🚀 Ahora reinicia tu servidor web:"
echo "   sudo systemctl restart apache2"
echo "   # o"
echo "   sudo systemctl restart nginx"
echo ""
