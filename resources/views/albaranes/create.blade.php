@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-box-seam"></i> {{ __('Nuevo Albarán de Compra') }}
                </div>

                <div class="card-body overflow-auto flex-fill">
                    @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <div class="container-fluid">
                        <form method="POST" action="{{ route('albaranes.store') }}" id="formAlbaran" style="flex:1">
                            @csrf

                            <div class="row">
                                <!-- Datos del Proveedor -->
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-secondary text-white">
                                            <i class="bi bi-person-badge"></i> Datos del Proveedor
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="proveedor_id" class="form-label">Proveedor *</label>
                                                <select class="form-select" id="proveedor_id" name="proveedor_id" required>
                                                    <option value="">Seleccione un proveedor</option>
                                                    @foreach($proveedores as $proveedor)
                                                        <option value="{{ $proveedor->id }}" {{ old('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                                                            {{ $proveedor->nombre }} ({{ $proveedor->cif }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="form-text text-muted">
                                                    ¿No encuentras el proveedor? 
                                                    <a href="{{ route('proveedores.create') }}" target="_blank">Crear nuevo proveedor</a>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Datos del Albarán -->
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header bg-secondary text-white">
                                            <i class="bi bi-file-text"></i> Datos del Albarán
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="numero_albaran" class="form-label">Número de Albarán *</label>
                                                <input type="text" class="form-control" id="numero_albaran" name="numero_albaran" 
                                                       value="{{ old('numero_albaran') }}" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="fecha_albaran" class="form-label">Fecha del Albarán del Proveedor</label>
                                                <input type="date" class="form-control" id="fecha_albaran" name="fecha_albaran" 
                                                       value="{{ old('fecha_albaran') }}">
                                            </div>

                                            <div class="mb-3">
                                                <label for="fecha" class="form-label">Fecha de Recepción *</label>
                                                <input type="date" class="form-control" id="fecha" name="fecha" 
                                                       value="{{ old('fecha', date('Y-m-d')) }}" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="observaciones" class="form-label">Observaciones</label>
                                                <textarea class="form-control" id="observaciones" name="observaciones" 
                                                          rows="3">{{ old('observaciones') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Líneas de Productos -->
                            <div class="card mb-3" style="height: auto;">
                                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-cart"></i> Productos</span>
                                    <button type="button" class="btn btn-sm btn-light" onclick="agregarLinea()">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="tablaLineas">
                                            <thead>
                                                <tr>
                                                    <th style="width: 40%;">Producto</th>
                                                    <th style="width: 15%;" class="text-center">Cantidad</th>
                                                    <th style="width: 20%;" class="text-end">Precio Coste</th>
                                                    <th style="width: 20%;" class="text-end">Subtotal</th>
                                                    <th style="width: 5%;" class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="lineasContainer">
                                                <!-- Las líneas se añadirán aquí dinámicamente -->
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                                    <td class="text-end"><strong id="totalGeneral">0.00 €</strong></td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let lineaIndex = 0;
const productosData = @json($productos ?? []);

console.log('=== DEBUG PRODUCTOS ===');
console.log('Raw productosData:', productosData);
console.log('Type:', typeof productosData);
console.log('Is Array:', Array.isArray(productosData));
console.log('Length:', productosData ? productosData.length : 'null/undefined');
if (productosData && productosData.length > 0) {
    console.log('First item:', productosData[0]);
    console.log('First item keys:', Object.keys(productosData[0]));
}
console.log('======================');

// Agregar línea de producto
function agregarLinea() {
    const container = document.getElementById('lineasContainer');
    
    // Crear el HTML de la fila
    let optionsHtml = '<option value="">Seleccione un producto</option>';
    productosData.forEach(producto => {
        if (producto && producto.uuid) {
            // Probar diferentes formas de acceder a la familia
            const familiaObj = producto.familia_obj || producto.familiaObj || null;
            const familiaNombre = (familiaObj && familiaObj.nombre) ? familiaObj.nombre : '';
            const productoNombre = producto.nombre || 'Sin nombre';
            optionsHtml += `<option value="${producto.uuid}">${productoNombre}${familiaNombre ? ' - ' + familiaNombre : ''}</option>`;
        }
    });
    
    console.log('Options HTML:', optionsHtml);
    
    const rowHtml = `
        <tr class="linea-producto">
            <td>
                <select class="form-select form-select-sm select-producto" name="lineas[${lineaIndex}][producto_id]" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-center input-cantidad" 
                       name="lineas[${lineaIndex}][cantidad]" step="0.01" min="0.01" value="1" required>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end input-precio" 
                       name="lineas[${lineaIndex}][precio_coste]" step="0.01" min="0" value="0.00" required>
            </td>
            <td class="text-end align-middle">
                <strong class="subtotal-linea">0.00 €</strong>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="eliminarLinea(this)" style="display:inline">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    container.insertAdjacentHTML('beforeend', rowHtml);
    
    // Añadir event listeners a los nuevos inputs
    const nuevaFila = container.lastElementChild;
    nuevaFila.querySelector('.input-cantidad').addEventListener('input', calcularSubtotal);
    nuevaFila.querySelector('.input-precio').addEventListener('input', calcularSubtotal);
    
    lineaIndex++;
}

// Eliminar línea
function eliminarLinea(btn) {
    btn.closest('tr').remove();
    calcularTotal();
}

// Calcular subtotal de una línea
function calcularSubtotal(event) {
    const fila = event.target.closest('tr');
    const cantidad = parseFloat(fila.querySelector('.input-cantidad').value) || 0;
    const precio = parseFloat(fila.querySelector('.input-precio').value) || 0;
    const subtotal = cantidad * precio;
    
    fila.querySelector('.subtotal-linea').textContent = subtotal.toFixed(2) + ' €';
    calcularTotal();
}

// Calcular total general
function calcularTotal() {
    let total = 0;
    document.querySelectorAll('.subtotal-linea').forEach(el => {
        const valor = parseFloat(el.textContent.replace(' €', '')) || 0;
        total += valor;
    });
    
    document.getElementById('totalGeneral').textContent = total.toFixed(2) + ' €';
}

// Validar formulario antes de enviar
document.getElementById('formAlbaran').addEventListener('submit', function(e) {
    const lineas = document.querySelectorAll('.linea-producto');
    if (lineas.length === 0) {
        e.preventDefault();
        alert('Debe añadir al menos un producto al albarán.');
        return false;
    }
});

// Agregar una línea por defecto al cargar
document.addEventListener('DOMContentLoaded', function() {
    agregarLinea();
});
</script>
@endsection

@section('footer')
<div class="card-footer">
    <div class="d-flex align-items-center justify-content-center gap-2">
        <a href="{{ route('albaranes.index') }}" class="btn btn-secondary">
            <i class="bi bi-chevron-left"></i>
        </a>
        <button type="submit" form="formAlbaran" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-floppy"></i>
        </button>
    </div>
</div>
@endsection
