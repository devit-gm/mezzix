# 🚀 Guía Rápida - Nuevos Módulos Mezzix

## 📦 Gestión de Proveedores

### Crear Proveedor
```
1. Ir a /proveedores
2. Click en "Nuevo Proveedor"
3. Llenar datos obligatorios:
   - Nombre del proveedor (*)
4. Datos opcionales recomendados:
   - CIF/NIF
   - Email y teléfono
   - Dirección completa
   - Días de pago (default: 30)
   - Descuento general
5. Guardar
```

### Asociar con Albaranes
Los albaranes ahora pueden asociarse con proveedores:
- Campo `proveedor_id` en tabla albaranes
- Al crear/editar un albarán, seleccionar proveedor
- Ver historial de albaranes en detalle del proveedor

## 📱 Modo Offline PWA

### Activar Modo Offline
```
1. Asegurarse de que la PWA está instalada
2. Desconectar internet (modo avión)
3. La app sigue funcionando
4. Indicador rojo aparece: "📡 Sin conexión"
```

### Realizar Operaciones Offline
```
1. Navegar normalmente por la app
2. Añadir productos a fichas/mesas
3. Crear/editar registros
4. Las operaciones se guardan automáticamente
5. Mensaje: "Operación guardada. Se sincronizará cuando haya conexión"
```

### Sincronizar Datos
```
1. Reconectar internet
2. Indicador cambia: "⏳ Sincronizando..."
3. Esperar notificación: "Sincronización completa"
4. Indicador verde: "✅ Conectado"
5. Datos actualizados en servidor
```

### Forzar Sincronización Manual
```javascript
// Abrir consola del navegador (F12)
window.offlineManager.forceSyncNow();
```

### Verificar Estado
```javascript
// Ver estado actual
window.offlineManager.getStatus();
// Retorna: { isOnline: true/false, pendingCount: 0, syncInProgress: false }
```

## 🔧 Comandos Útiles

### Migrations
```bash
# Crear tabla proveedores
php artisan migrate --path=database/migrations/2024_12_11_000001_create_proveedores_table.php

# Actualizar albaranes
php artisan migrate --path=database/migrations/2024_12_11_000002_add_proveedor_to_albaranes.php
```

### Cache
```bash
# Limpiar caché de Service Worker
# DevTools > Application > Service Workers > Unregister
# Ctrl+Shift+R para recargar

# Limpiar caché de Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### IndexedDB
```javascript
// Abrir DevTools > Application > IndexedDB > mezzix-sync
// Ver operaciones pendientes en tabla: pending-requests
```

## 🎯 Casos de Uso

### Caso 1: Restaurante con WiFi intermitente
```
1. Instalar PWA en tablets de camareros
2. Camareros toman pedidos normalmente
3. Si WiFi falla, pedidos se guardan en cola
4. Cuando WiFi vuelve, se sincronizan automáticamente
5. Cocina recibe todos los pedidos
```

### Caso 2: Evento sin conexión
```
1. Precarga la app con datos necesarios (productos, familias)
2. Durante evento, tomar pedidos sin conexión
3. Al finalizar evento, conectar a internet
4. Sincronizar todas las operaciones del día
5. Generar facturación completa
```

### Caso 3: Gestión de compras con proveedores
```
1. Crear proveedores con sus datos fiscales
2. Al recibir mercancía, crear albarán
3. Asociar albarán con proveedor
4. Ver en detalle del proveedor todo su historial
5. Generar informes de compras por proveedor
```

## ⚠️ Limitaciones Conocidas

### Proveedores
- No se puede eliminar un proveedor con albaranes asociados
- La tabla albaranes está en conexión 'site' (multi-tenant)

### Modo Offline
- Operaciones complejas pueden fallar sin conexión
- Límite de almacenamiento: depende del navegador (~50% espacio disponible)
- Background Sync solo en navegadores compatibles (Chrome, Edge, Opera)
- Notificaciones push requieren HTTPS

## 🆘 Solución Rápida de Problemas

### ❌ Service Worker no funciona
```
1. Verificar HTTPS (localhost está OK)
2. DevTools > Application > Service Workers
3. Unregister y recargar (Ctrl+Shift+R)
```

### ❌ Indicador offline no aparece
```
1. F12 > Console
2. Verificar errores de JavaScript
3. Confirmar que se carga: /js/offline-manager.js
4. Ejecutar: console.log(window.offlineManager)
```

### ❌ Sincronización no funciona
```
1. F12 > Application > IndexedDB
2. Buscar database: mezzix-sync
3. Ver tabla: pending-requests
4. Si hay operaciones, forzar sync:
   window.offlineManager.forceSyncNow()
```

### ❌ No puedo crear proveedor
```
1. Verificar que la tabla existe:
   php artisan migrate --path=database/migrations/2024_12_11_000001_create_proveedores_table.php
2. Verificar permisos de usuario
3. Revisar logs: storage/logs/laravel.log
```

## 📞 Contacto

Para soporte o reportar bugs, contactar al equipo de desarrollo.

---

**Última actualización**: 11 de diciembre de 2025
