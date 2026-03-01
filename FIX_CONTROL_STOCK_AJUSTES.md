# ✅ Fix: Control de Stock Respeta Ajustes

## 🔴 Problema

El sistema verificaba stock **siempre**, incluso cuando el ajuste `permitir_comprar_sin_stock` estaba activado.

**Síntomas:**
- Con control de stock desactivado en ajustes
- Al añadir productos al carrito → "Stock insuficiente"
- Al actualizar cantidades → "Stock insuficiente"

---

## 🔍 Causa Raíz

### Métodos Afectados

**1. `addproduct()` - Añadir producto al carrito**
```php
// ❌ ANTES: Siempre verificaba stock
if ($ficha->estado == 0) {
    if (!$this->productoService->tieneStockDisponible($producto, $cantidad)) {
        return redirect()->back()->with('error', $mensaje);
    }
}
```

**2. `updatelista()` - Actualizar cantidades**
```php
// ❌ ANTES: Siempre verificaba stock
if ($cantidad > 0 && $ficha->estado == 0) {
    if (!$this->productoService->tieneStockDisponible($producto, $cantidad)) {
        return redirect()->route('fichas.lista', $uuid)->with('error', $mensaje);
    }
}
```

**Problema:** No consultaban el ajuste `permitir_comprar_sin_stock`

---

## ✅ Solución Aplicada

### Ajuste de Control

**Campo en BD:** `ajustes.permitir_comprar_sin_stock`

**Valores:**
- `0` → Control de stock **ACTIVO** (verifica y bloquea)
- `1` → Control de stock **DESACTIVADO** (permite comprar sin stock)

---

### 1. Método `addproduct()` Corregido

**Archivo:** `app/Http/Controllers/FichasController.php:1370`

```php
public function addproduct(Request $request)
{
    return DB::transaction(function () use ($request) {
        $ficha = Ficha::find($request->idFicha);
        $producto = Producto::with('composicion.componenteProducto')->find($request->idProducto);
        $cantidad = $request->cantidad;
        
        // 🚀 Obtener ajustes
        $ajustes = get_ajustes();

        // Verificar stock solo si:
        // 1. Ficha abierta (estado == 0)
        // 2. Control de stock ACTIVO (permitir_comprar_sin_stock == 0)
        if ($ficha->estado == 0 && $ajustes->permitir_comprar_sin_stock == 0) {
            if (!$this->productoService->tieneStockDisponible($producto, $cantidad)) {
                $mensaje = "Stock insuficiente de {$producto->nombre}...";
                return redirect()->back()->with('error', $mensaje);
            }
        }
        
        // ... resto del código
        
        // Reservar stock solo si control activo
        if ($ficha->estado == 0 && $ajustes->permitir_comprar_sin_stock == 0) {
            $this->productoService->reservarStock($producto, $cantidad);
        }
        
        // ... crear FichaProducto
    });
}
```

**Cambios:**
- ✅ Consulta `$ajustes->permitir_comprar_sin_stock`
- ✅ Solo verifica si `permitir_comprar_sin_stock == 0`
- ✅ Solo reserva si control activo

---

### 2. Método `updatelista()` Corregido

**Archivo:** `app/Http/Controllers/FichasController.php:1488`

```php
public function updatelista(string $uuid, string $uuid2, int $cantidad)
{
    return DB::transaction(function () use ($uuid, $uuid2, $cantidad) {
        $ficha = Ficha::find($uuid);
        $producto = Producto::with('composicion.componenteProducto')->find($uuid2);
        
        // 🚀 Obtener ajustes
        $ajustes = get_ajustes();
        
        // Verificar stock solo si:
        // 1. Añadiendo cantidad (> 0)
        // 2. Ficha abierta (estado == 0)
        // 3. Control de stock ACTIVO
        if ($cantidad > 0 && $ficha->estado == 0 && $ajustes->permitir_comprar_sin_stock == 0) {
            if (!$this->productoService->tieneStockDisponible($producto, $cantidad)) {
                $mensaje = "Stock insuficiente...";
                return redirect()->route('fichas.lista', $uuid)->with('error', $mensaje);
            }
        }
        
        if ($cantidad > 0) {
            // Reservar solo si control activo
            if ($ficha->estado == 0 && $ajustes->permitir_comprar_sin_stock == 0) {
                // ... reservar stock
            }
        } else {
            // Liberar solo si control activo
            if ($ficha->estado == 0 && $ajustes->permitir_comprar_sin_stock == 0) {
                // ... liberar stock
            }
        }
    });
}
```

**Cambios:**
- ✅ Consulta ajustes antes de verificar
- ✅ Verifica solo si control activo
- ✅ Reserva/libera solo si control activo

---

## 🎯 Comportamiento Final

### Con Control de Stock ACTIVO (`permitir_comprar_sin_stock = 0`)

**Añadir producto:**
1. ✅ Verifica stock disponible
2. ✅ Si no hay → muestra error "Stock insuficiente"
3. ✅ Si hay → añade y reserva stock

**Actualizar cantidad:**
1. ✅ Verifica stock para nueva cantidad
2. ✅ Si no hay → muestra error
3. ✅ Si hay → actualiza y ajusta reserva

---

### Con Control de Stock DESACTIVADO (`permitir_comprar_sin_stock = 1`)

**Añadir producto:**
1. ✅ **NO** verifica stock
2. ✅ Añade producto sin importar stock
3. ✅ **NO** reserva stock

**Actualizar cantidad:**
1. ✅ **NO** verifica stock
2. ✅ Actualiza cantidad libremente
3. ✅ **NO** ajusta reservas

---

## 🧪 Testing

### Test 1: Control de Stock Desactivado

```bash
# 1. En ajustes, activar "Permitir comprar sin stock"
#    (permitir_comprar_sin_stock = 1)

# 2. Ir a una ficha
# 3. Añadir un producto con stock = 0
# 4. ✅ Debería añadirse SIN error

# 5. Aumentar cantidad más allá del stock disponible
# 6. ✅ Debería permitirlo SIN error
```

### Test 2: Control de Stock Activado

```bash
# 1. En ajustes, desactivar "Permitir comprar sin stock"
#    (permitir_comprar_sin_stock = 0)

# 2. Ir a una ficha
# 3. Añadir un producto con stock = 0
# 4. ❌ Debería mostrar "Stock insuficiente"

# 5. Añadir producto con stock = 5, cantidad = 10
# 6. ❌ Debería mostrar "Stock insuficiente"

# 7. Añadir producto con stock = 5, cantidad = 3
# 8. ✅ Debería añadirse y reservar 3 unidades
```

### Test 3: Productos Combinados

```bash
# Con control activo
# Producto combinado con:
#   - Componente A: stock = 5
#   - Componente B: stock = 10

# 1. Añadir 3 unidades
# 2. ✅ Debería permitir (suficiente stock de ambos)

# 3. Añadir 8 unidades
# 4. ❌ Debería bloquear (componente A insuficiente)
```

---

## 📊 Archivos Modificados

**1 archivo:**
- ✅ `app/Http/Controllers/FichasController.php`

**2 métodos actualizados:**
- ✅ `addproduct()` (línea 1370)
- ✅ `updatelista()` (línea 1488)

**Líneas añadidas:** ~10 líneas
**Líneas modificadas:** ~6 líneas

---

## 🔧 Configuración en Ajustes

### Dónde Configurarlo

**Panel de Ajustes:**
```
Ajustes → Stock → Permitir comprar sin stock
```

**Base de datos:**
```sql
UPDATE ajustes 
SET permitir_comprar_sin_stock = 1  -- Desactivar control
WHERE id = ...;

UPDATE ajustes 
SET permitir_comprar_sin_stock = 0  -- Activar control
WHERE id = ...;
```

---

## 💡 Casos de Uso

### ¿Cuándo Desactivar Control de Stock?

1. **Servicios:** No tienen stock físico
2. **Pedidos bajo demanda:** Se piden al proveedor después
3. **Stock ilimitado:** Productos digitales, licencias
4. **Testing:** Probar sistema sin restricciones

### ¿Cuándo Activar Control de Stock?

1. **Productos físicos limitados**
2. **Control de inventario estricto**
3. **Evitar sobreventa**
4. **Productos perecederos**

---

## 🎯 Resultado

**Antes:**
- 🔴 Siempre verificaba stock, sin importar ajustes
- 🔴 No se podía desactivar control de stock
- 🔴 Bloqueaba ventas aunque ajuste estuviera activo

**Después:**
- ✅ Respeta ajuste `permitir_comprar_sin_stock`
- ✅ Control de stock totalmente configurable
- ✅ Flexibilidad para diferentes casos de uso
- ✅ Lógica centralizada y consistente

---

**Fecha:** 2026-02-03  
**Por:** Rio 😄  
**Proyecto:** MEZZIX - Control de Stock Configurable
