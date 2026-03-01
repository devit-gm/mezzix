#!/bin/bash

echo "🧹 Limpiando cache de productos_menu..."

cd ~/Documentos/mezzix

# Limpiar cache de Laravel con sudo
echo "📦 Borrando archivos de cache..."
sudo rm -rf storage/framework/cache/data/*

# Limpiar otros caches
php artisan config:clear
php artisan view:clear

echo ""
echo "✅ Cache limpiado!"
echo ""
echo "🔄 Ahora refresca la página con Ctrl+F5"
echo ""
