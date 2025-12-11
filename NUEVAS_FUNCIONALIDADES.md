# Nuevas Funcionalidades - Mezzix/AGSuitePro

## 1. Gestión de Proveedores

### Descripción
Sistema completo para gestionar proveedores, permitiendo almacenar información fiscal, comercial y de contacto. Se integra con el módulo de albaranes para facilitar la gestión de compras.

### Características
- ✅ CRUD completo de proveedores
- ✅ Información fiscal (CIF, datos de facturación)
- ✅ Condiciones de pago y plazos
- ✅ Datos bancarios
- ✅ Seguimiento de compras por proveedor
- ✅ Historial de albaranes por proveedor
- ✅ Estadísticas de compras
- ✅ Búsqueda y filtros
- ✅ Activar/Desactivar proveedores

### Archivos Creados

#### Migraciones
- `database/migrations/2024_12_11_000001_create_proveedores_table.php` - Tabla de proveedores
- `database/migrations/2024_12_11_000003_update_albaranes_proveedores.php` - Actualización de albaranes

#### Modelos
- `app/Models/Proveedor.php` - Modelo con relaciones y scopes

#### Controladores
- `app/Http/Controllers/ProveedoresController.php` - Lógica de negocio

#### Vistas
- `resources/views/proveedores/index.blade.php` - Listado
- `resources/views/proveedores/create.blade.php` - Crear proveedor
- `resources/views/proveedores/edit.blade.php` - Editar proveedor
- `resources/views/proveedores/show.blade.php` - Detalle y estadísticas

#### Scripts
- `database/scripts/migrar_proveedores_existentes.sql` - Migración de datos antiguos

### Integración con Albaranes
Los albaranes ahora están vinculados a proveedores mediante una relación de base de datos:
- Campo `proveedor_id` en tabla `albaranes`
- Eliminados campos redundantes: `proveedor`, `nif`, `contacto`
- Selector de proveedor en formularios de albaranes
- Historial de albaranes en la ficha del proveedor

### Campos de la Tabla Proveedores

```php
- id (bigint, PK)
- uuid (string, unique)
- nombre (string) *
- cif (string, unique) *
- direccion (text)
- telefono (string)
- email (string)
- persona_contacto (string)
- condiciones_pago (enum: contado, 30, 60, 90, 120 días)
- dias_pago (integer)
- cuenta_bancaria (string)
- observaciones (text)
- activo (boolean)
- created_at, updated_at
```

### Uso

#### Crear un Proveedor
1. Ir a `Gestión > Proveedores`
2. Clic en "Nuevo Proveedor"
3. Rellenar datos obligatorios (nombre, CIF)
4. Guardar

#### Crear Albarán con Proveedor
1. Ir a `Gestión > Albaranes > Nuevo`
2. Seleccionar proveedor del desplegable
3. Si no existe el proveedor, usar enlace "Crear nuevo proveedor"
4. Continuar con los productos

#### Ver Estadísticas de Proveedor
1. Ir a `Gestión > Proveedores`
2. Clic en el icono de ojo de cualquier proveedor
3. Ver total de compras, número de albaranes e historial

---

## 2. Modo Offline PWA (Progressive Web App)

### Descripción
Sistema avanzado de trabajo offline que permite usar la aplicación sin conexión a Internet. Los datos se sincronizan automáticamente cuando se recupera la conexión.

### Características
- ✅ Service Worker v3 con múltiples estrategias de caché
- ✅ Cola de sincronización con IndexedDB
- ✅ Detección automática del estado de conexión
- ✅ Indicador visual del estado (online/offline/sincronizando)
- ✅ Notificaciones toast para feedback
- ✅ Sincronización automática al recuperar conexión
- ✅ Caché de assets estáticos (CSS, JS, imágenes)
- ✅ Caché de llamadas API
- ✅ Estrategia Network-First para datos dinámicos

### Archivos Creados/Modificados

#### Service Worker
- `public/sw.js` - Service Worker principal (v3)
  - Gestión de 3 cachés: static, dynamic, api
  - Listeners de eventos: install, activate, fetch, sync, message
  - Integración con IndexedDB

#### Gestor Offline
- `public/js/offline-manager.js` - Clase OfflineManager
  - Detección de estado de conexión
  - Indicador visual de estado
  - Sistema de notificaciones toast
  - Comunicación con Service Worker
  - API pública para sincronización manual

#### Layout
- `resources/views/layouts/app.blade.php` - Modificado
  - Script de offline-manager incluido
  - Inicialización automática

### Estrategias de Caché

#### 1. Static Cache (Caché estática)
**Recursos:** CSS, JS, fuentes, imágenes de UI
**Estrategia:** Cache-First
**TTL:** Hasta nueva versión del SW

```javascript
// Archivos incluidos:
- /css/app.css
- /js/app.js
- /build/assets/*
- Fuentes e iconos
```

#### 2. Dynamic Cache (Caché dinámica)
**Recursos:** Páginas HTML, vistas
**Estrategia:** Network-First con fallback
**TTL:** 24 horas
**Límite:** 50 entradas

#### 3. API Cache (Caché de API)
**Recursos:** Llamadas a /api/*
**Estrategia:** Network-First con cache fallback
**TTL:** Según headers (default: 5 minutos)

### Funciones del Service Worker

```javascript
// Gestión de IndexedDB
openDB()                    // Abre/crea base de datos
savePendingRequest()        // Guarda petición pendiente
getPendingRequests()        // Obtiene todas las pendientes
deletePendingRequest()      // Elimina después de sincronizar
syncPendingRequests()       // Sincroniza todas las pendientes

// Estrategias de caché
cacheFirst()               // Prioriza caché (assets)
networkFirst()             // Prioriza red (datos)
```

### API del OfflineManager

```javascript
// Inicialización (automática en app.blade.php)
const offline = new OfflineManager();

// Sincronización manual
offline.forceSyncNow();

// Obtener estado actual
const status = offline.getStatus();
// Returns: 'online' | 'offline' | 'syncing'

// Eventos personalizados
window.addEventListener('online-status-changed', (e) => {
  console.log('Estado:', e.detail.status);
});
```

### Estados Visuales

#### Online (Verde)
- Círculo verde en esquina superior derecha
- Tooltip: "Conectado"
- Sincronización automática activa

#### Offline (Rojo)
- Círculo rojo en esquina superior derecha
- Tooltip: "Sin conexión"
- Operaciones se guardan en cola

#### Syncing (Amarillo)
- Círculo amarillo pulsante
- Tooltip: "Sincronizando..."
- Se ejecuta automáticamente al recuperar conexión

### Notificaciones Toast

```javascript
// Tipos de notificación:
- success: Operación exitosa (verde)
- error: Error en operación (rojo)
- info: Información general (azul)
- warning: Advertencia (amarillo)

// Ejemplos:
"✓ Conectado a Internet"
"⚠ Sin conexión. Los cambios se guardarán localmente"
"↻ Sincronizando datos..."
"✓ Datos sincronizados correctamente"
```

### Casos de Uso

#### 1. WiFi Inestable en Restaurante
- El usuario trabaja en un restaurante con WiFi intermitente
- Toma comandas y gestiona mesas
- Las operaciones se guardan localmente
- Al recuperar WiFi, se sincronizan automáticamente

#### 2. Eventos al Aire Libre
- Fichas de eventos en lugares sin cobertura
- Los datos se guardan en IndexedDB
- Al volver a zona con cobertura, todo se sincroniza

#### 3. Gestión de Albaranes
- Recepción de mercancía sin conexión
- Los albaranes se crean localmente
- Se sincronizan cuando hay red disponible

### Configuración del Service Worker

```javascript
// Versión del Service Worker
const VERSION = 'v3';

// Nombres de caché
const STATIC_CACHE = 'mezzix-static-v3';
const DYNAMIC_CACHE = 'mezzix-dynamic-v3';
const API_CACHE = 'mezzix-api-v3';

// Límites de caché
const MAX_DYNAMIC_CACHE = 50;
const MAX_API_CACHE = 100;

// TTL de caché API (5 minutos)
const API_CACHE_TTL = 5 * 60 * 1000;
```

### Base de Datos IndexedDB

```javascript
// Nombre: mezzix-offline-db
// Version: 1
// Store: pendingRequests

// Estructura de registro:
{
  id: timestamp,
  url: string,
  method: string,
  headers: object,
  body: string,
  timestamp: number,
  retries: number
}
```

### Limitaciones Actuales

1. **Archivos grandes**: No se cachean archivos > 10MB
2. **Multimedia**: Videos/audios no se guardan offline
3. **WebSockets**: No soportados offline (solo HTTP)
4. **Background Sync**: Requiere soporte del navegador

### Compatibilidad de Navegadores

- ✅ Chrome 40+
- ✅ Firefox 44+
- ✅ Safari 11.1+
- ✅ Edge 17+
- ⚠️ Internet Explorer: NO soportado

### Testing del Modo Offline

#### Chrome DevTools
1. F12 > Application > Service Workers
2. Verificar que SW esté activo
3. Network > Throttling > Offline
4. Probar funcionalidades

#### Firefox DevTools
1. F12 > Application > Service Workers
2. "Offline" checkbox
3. Probar funcionalidades

### Resolución de Problemas

#### El SW no se registra
```javascript
// Verificar en consola:
navigator.serviceWorker.getRegistration()
  .then(reg => console.log(reg));

// Si es null, verificar:
// 1. HTTPS (o localhost)
// 2. Ruta correcta del sw.js
// 3. Headers de respuesta correctos
```

#### La sincronización no funciona
```javascript
// Forzar sincronización:
offline.forceSyncNow();

// Ver cola pendiente:
// F12 > Application > IndexedDB > mezzix-offline-db
```

#### Limpiar caché
```javascript
// En DevTools > Console:
caches.keys().then(names => {
  names.forEach(name => caches.delete(name));
});

// O desde el gestor:
// Application > Storage > Clear storage
```

---

## Próximas Funcionalidades Recomendadas

### Corto Plazo
- [ ] Gestión de Gastos mejorada
- [ ] Reportes de compras por proveedor
- [ ] Dashboard de estadísticas

### Medio Plazo
- [ ] CRM básico para clientes
- [ ] Sistema de notificaciones push
- [ ] Gestión de inventario avanzada

### Largo Plazo
- [ ] Integración con facturación electrónica
- [ ] App móvil nativa
- [ ] Analíticas avanzadas con gráficos

---

## Notas Técnicas

### Base de Datos Multi-tenant
- Los proveedores se almacenan en la base de datos por defecto
- Los albaranes se almacenan en la conexión 'site' (multi-tenant)
- Las relaciones entre proveedores y albaranes funcionan correctamente entre conexiones

### Seguridad
- Validación de datos en backend
- Campos únicos (CIF, UUID)
- Control de permisos por roles (futuro)

### Performance
- Índices en campos clave (uuid, cif, activo)
- Eager loading en consultas (with())
- Paginación en listados

---

**Fecha de Implementación:** Diciembre 2024
**Versión de Laravel:** 10.x
**Autor:** GitHub Copilot
