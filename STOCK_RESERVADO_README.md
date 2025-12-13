# Sistema de Stock con Reservas - AGSuitePro

## 🎯 Descripción

El sistema de **stock con reservas** permite gestionar el inventario de manera inteligente, reservando automáticamente el stock cuando se añaden productos a fichas abiertas y liberándolo cuando se eliminan o se cierra la ficha.

## 📊 Conceptos Clave

### Stock Real vs Stock Disponible

- **Stock Real** (`stock`): La cantidad física de producto en el almacén
- **Stock Reservado** (`stock_reservado`): Cantidad comprometida en fichas/mesas abiertas
- **Stock Disponible**: `stock - stock_reservado`

```
Ejemplo:
Stock Real: 100 unidades
Stock Reservado: 25 unidades (en 3 fichas abiertas)
Stock Disponible: 75 unidades (disponible para nuevas ventas)
```

## ✨ Características Implementadas

### 1. Campo `stock_reservado` en Productos
- Nuevo campo en tabla `productos`
- Se actualiza automáticamente al gestionar fichas
- Siempre refleja la cantidad comprometida en fichas abiertas

### 2. Métodos en Modelo `Producto`

#### `stockDisponible()`
Calcula el stock disponible para nuevas ventas
```php
$producto->stockDisponible(); // Retorna: stock - stock_reservado
```

#### `tieneStockDisponible($cantidad)`
Verifica si hay suficiente stock para una cantidad solicitada
```php
if ($producto->tieneStockDisponible(5)) {
    // Proceder con la venta
}
```

#### `reservarStock($cantidad)`
Incrementa el stock reservado (al añadir a ficha abierta)
```php
$producto->reservarStock(3); // stock_reservado += 3
```

#### `liberarStock($cantidad)`
Reduce el stock reservado (al eliminar de ficha o cancelar)
```php
$producto->liberarStock(2); // stock_reservado -= 2
```

#### `confirmarVenta($cantidad)`
Descuenta del stock real y libera la reserva (al cerrar ficha)
```php
$producto->confirmarVenta(5); 
// stock -= 5
// stock_reservado -= 5
```

### 3. Gestión Automática en Fichas

#### Al añadir productos (`addproduct`)
1. ✅ Verifica stock disponible suficiente
2. ✅ Muestra error si no hay stock
3. ✅ Reserva el stock automáticamente
4. ✅ Para productos combinados, verifica cada componente

#### Al eliminar productos (`destroylista`)
1. ✅ Libera el stock reservado
2. ✅ Para productos combinados, libera componentes

#### Al cambiar cantidad (`updatelista`)
1. ✅ Verifica stock al aumentar
2. ✅ Reserva stock al añadir
3. ✅ Libera stock al reducir

#### Al cerrar ficha (`enviar` / `cerrarMesa`)
1. ✅ Confirma la venta (descuenta stock real)
2. ✅ Libera el stock reservado
3. ✅ Registra en log la operación
4. ✅ Notifica si stock bajo

### 4. Visualización de Stock

Las vistas de productos muestran:
- 🔴 **Agotado**: Stock disponible ≤ 0 (no se puede añadir)
- 🟡 **Stock Bajo**: Stock disponible > 0 pero ≤ stock_mínimo (aviso)
- 🟢 **Stock OK**: Stock disponible > stock_mínimo

## 🔄 Flujo de Trabajo

### Escenario: Venta de un producto

```
Estado Inicial:
├── Stock Real: 100
├── Stock Reservado: 0
└── Stock Disponible: 100

1. Usuario abre ficha y añade 10 unidades
├── Verificar: ¿stockDisponible() >= 10? ✓ Sí
├── Reservar: reservarStock(10)
├── Stock Real: 100 (sin cambios)
├── Stock Reservado: 10 ↑
└── Stock Disponible: 90 ↓

2. Otro usuario intenta añadir 95 unidades
├── Verificar: ¿stockDisponible() >= 95? ✗ No (solo hay 90)
└── Mostrar error: "Stock insuficiente"

3. Usuario cierra la ficha original
├── Confirmar venta: confirmarVenta(10)
├── Stock Real: 90 ↓ (descuenta del inventario)
├── Stock Reservado: 0 ↓ (libera la reserva)
└── Stock Disponible: 90 (ahora realmente disponible)
```

### Escenario: Cancelación/Eliminación

```
Estado:
├── Stock Real: 100
├── Stock Reservado: 15 (en ficha abierta)
└── Stock Disponible: 85

1. Usuario elimina 5 unidades de la ficha
├── Liberar: liberarStock(5)
├── Stock Real: 100 (sin cambios)
├── Stock Reservado: 10 ↓
└── Stock Disponible: 90 ↑
```

## 🛠️ Comando Artisan

### Recalcular Stock Reservado

Si necesitas recalcular el stock reservado basándose en las fichas abiertas actuales:

```bash
php artisan stock:recalcular-reservado
```

Este comando:
1. Resetea todo `stock_reservado` a 0
2. Calcula cantidades en todas las fichas abiertas (estado=0)
3. Actualiza `stock_reservado` de cada producto
4. Para combinados, actualiza componentes
5. Muestra resumen de productos actualizados

**Cuándo usarlo:**
- Después de importar datos
- Si se detectan inconsistencias
- Tras recuperación de errores
- Al migrar desde versión anterior

## 📝 Logs

El sistema registra todas las operaciones de stock:

```php
Log::info('Venta confirmada', [
    'producto' => 'Cerveza Estrella Galicia',
    'stock_anterior' => 100,
    'stock_nuevo' => 95,
    'reserva_anterior' => 10,
    'reserva_nueva' => 5,
    'cantidad_vendida' => 5
]);
```

## 🔍 Consultas SQL Útiles

### Ver productos con stock reservado
```sql
SELECT nombre, stock, stock_reservado, (stock - stock_reservado) as disponible
FROM productos 
WHERE stock_reservado > 0;
```

### Ver stock reservado por ficha
```sql
SELECT 
    f.uuid as ficha_id,
    f.descripcion,
    p.nombre as producto,
    SUM(fp.cantidad) as cantidad_reservada
FROM fichas f
JOIN fichas_productos fp ON f.uuid = fp.id_ficha
JOIN productos p ON fp.id_producto = p.uuid
WHERE f.estado = 0
GROUP BY f.uuid, p.uuid;
```

### Detectar inconsistencias
```sql
SELECT 
    p.nombre,
    p.stock_reservado as reservado_en_tabla,
    COALESCE(SUM(fp.cantidad), 0) as reservado_real
FROM productos p
LEFT JOIN fichas_productos fp ON p.uuid = fp.id_producto
LEFT JOIN fichas f ON fp.id_ficha = f.uuid AND f.estado = 0
GROUP BY p.uuid
HAVING p.stock_reservado != COALESCE(SUM(fp.cantidad), 0);
```

## ⚠️ Consideraciones Importantes

### 1. Solo se reserva en fichas abiertas (estado = 0)
- Fichas cerradas (estado = 1) ya consumieron el stock real
- No se duplica el descuento

### 2. Productos Combinados
- Se verifica/reserva/libera stock de CADA componente
- Si un componente no tiene stock, el combinado no se puede vender

### 3. Transacciones
- `cerrarMesa` usa locking pesimista (`lockForUpdate`)
- Evita race conditions en ventas concurrentes
- Garantiza consistencia de datos

### 4. Productos sin gestión de stock
- Si `stock = NULL`, se considera disponibilidad ilimitada
- No se reserva ni se valida

### 5. Stock Negativo
- `liberarStock` usa `max(0, ...)` para evitar negativos en reservas
- Si ocurre, ejecutar `stock:recalcular-reservado`

## 🐛 Troubleshooting

### Problema: "Stock insuficiente" pero hay stock físico

**Causa**: Stock está reservado en otras fichas abiertas

**Solución**: 
1. Verificar fichas abiertas con ese producto
2. Cerrar fichas no necesarias
3. O aumentar stock real

### Problema: Stock reservado mayor que stock real

**Causa**: 
- Productos añadidos antes de implementar reservas
- Modificación manual de stock
- Error en alguna operación

**Solución**:
```bash
php artisan stock:recalcular-reservado
```

### Problema: Stock no se libera al eliminar productos

**Verificar**:
1. Que la ficha esté abierta (estado = 0)
2. Logs de la aplicación
3. Que el método `liberarStock` se esté llamando

## 📈 Beneficios

✅ **Venta segura**: No se vende más de lo disponible
✅ **Stock en tiempo real**: Refleja compromisos actuales
✅ **Mejor experiencia**: Usuario sabe de inmediato si hay disponibilidad
✅ **Control financiero**: Evita sobreventa y reclamos
✅ **Trazabilidad**: Logs completos de operaciones
✅ **Productos combinados**: Gestión automática de componentes
✅ **Concurrencia**: Sin problemas en múltiples ventas simultáneas

## 🔄 Migración de Versión Anterior

Si actualizas desde una versión sin stock reservado:

1. Ejecutar migración:
```bash
php artisan migrate
```

2. Recalcular reservas:
```bash
php artisan stock:recalcular-reservado
```

3. Verificar funcionamiento en ficha de prueba

4. Opcional: Revisar inconsistencias con query SQL

## 📞 Soporte

Para dudas o problemas con el sistema de stock reservado:
- Revisar logs en `storage/logs/laravel.log`
- Ejecutar `stock:recalcular-reservado`
- Verificar consultas SQL de detección de inconsistencias
