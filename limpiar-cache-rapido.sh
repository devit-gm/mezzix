#!/bin/bash

echo "🧹 Limpiando cache completo de MEZZIX..."

cd ~/Documentos/mezzix

# Limpiar cache de Laravel
echo "📦 Limpiando cache de aplicación..."
php artisan cache:clear

echo "📦 Limpiando cache de configuración..."
php artisan config:clear

echo "📦 Limpiando cache de rutas..."
php artisan route:clear

echo "📦 Limpiando cache de vistas..."
php artisan view:clear

echo ""
echo "✅ Cache limpiado!"
echo ""
echo "🔄 Ahora haz Ctrl+F5 en el navegador para refrescar la página"
echo ""
