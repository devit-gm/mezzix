@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-people-fill"></i> {{ __('Gestión de Proveedores') }}
                </div>

                <div class="card-body overflow-auto flex-fill">
                    <!-- Mensajes -->
                        @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif
                    <div class="container-fluid d-flex flex-column">
                        
                        
                        <!-- Filtros -->
                        <form method="GET" action="{{ route('proveedores.index') }}" class="mb-3" id="formFiltros">
                            <div class="row g-2">
                                <div class="col-12">
                                    <input type="text" name="buscar" class="form-control form-control-sm" 
                                           placeholder="Buscar por nombre, CIF, email o teléfono..." 
                                           value="{{ request('buscar') }}">
                                </div>
                                <div class="col-12">
                                    <select name="estado" class="form-select form-select-sm">
                                        <option value="">Todos los estados</option>
                                        <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activos</option>
                                        <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>Inactivos</option>
                                    </select>
                                </div>
                            </div>
                        </form>

                          @if($proveedores->isEmpty())
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle me-2"></i>
                            No hay proveedores registrados.
                        </div>
                        @else

                        <!-- Tabla de Proveedores -->
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>CIF</th>
                                        <th>Contacto</th>
                                        <th>Ciudad</th>
                                        <th>Días Pago</th>
                                        <th class="text-center">Albaranes</th>
                                        <th class="text-end">Total Compras</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($proveedores as $proveedor)
                                    <tr>
                                        <td>
                                            <strong>{{ $proveedor->nombre }}</strong>
                                            @if($proveedor->contacto_principal)
                                                <br><small class="text-muted">{{ $proveedor->contacto_principal }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $proveedor->cif ?? '-' }}</td>
                                        <td>
                                            @if($proveedor->telefono)
                                                <i class="bi bi-telephone"></i> {{ $proveedor->telefono }}<br>
                                            @endif
                                            @if($proveedor->email)
                                                <i class="bi bi-envelope"></i> <small>{{ $proveedor->email }}</small>
                                            @endif
                                            @if(!$proveedor->telefono && !$proveedor->email)
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $proveedor->ciudad ?? '-' }}</td>
                                        <td>{{ $proveedor->dias_pago }} días</td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $proveedor->albaranes->count() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($proveedor->total_compras ?? 0, 2) }} €</strong>
                                        </td>
                                        <td class="text-center">
                                            @if($proveedor->activo)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('proveedores.show', $proveedor->uuid) }}" 
                                                   class="btn btn-info" title="Ver Detalle">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('proveedores.edit', $proveedor->uuid) }}" 
                                                   class="btn btn-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('proveedores.destroy', $proveedor->uuid) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('¿Estás seguro de eliminar este proveedor?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                </tbody>
                            </table>
                        </div>
                        @endforeach
@endif
                    </div>
                </div>

                <!-- Paginación -->
                @if($proveedores->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $proveedores->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
<div class="card-footer">
    <div class="d-flex align-items-center justify-content-center gap-2">
    <a href="{{ route('proveedores.index') }}" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-chevron-left"></i>
        </a>    
    <button type="submit" form="formFiltros" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-search"></i>
        </button>
        
        <a href="{{ route('proveedores.create') }}" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-plus-circle"></i>
        </a>
    </div>
</div>
@endsection
