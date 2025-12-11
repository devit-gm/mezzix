@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-box-seam"></i> {{ __('Albaranes de Compra') }}
                </div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="container-fluid d-flex flex-column">
                        <!-- Filtros -->
                        <div class="mb-3">
                            <form method="GET" action="{{ route('albaranes.index') }}" id="formFiltros">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <select name="proveedor_id" class="form-select form-select-sm">
                                            <option value="">Todos los proveedores</option>
                                            @foreach($proveedores as $proveedor)
                                                <option value="{{ $proveedor->id }}" {{ request('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                                                    {{ $proveedor->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-12">
                                        <select name="estado" class="form-select form-select-sm">
                                            <option value="">Todos los estados</option>
                                            <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                            <option value="recibido" {{ request('estado') == 'recibido' ? 'selected' : '' }}>Recibido</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <input type="date" name="fecha_desde" class="form-control form-control-sm" 
                                               placeholder="Desde" value="{{ request('fecha_desde') }}">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" name="fecha_hasta" class="form-control form-control-sm" 
                                               placeholder="Hasta" value="{{ request('fecha_hasta') }}">
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Listado de Albaranes -->
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

                        @if($albaranes->isEmpty())
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle me-2"></i>
                            No hay albaranes registrados.
                        </div>
                        @else
                        <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 120px;">N° Albarán</th>
                                                <th>Proveedor</th>
                                                <th class="text-center" style="width: 100px;">Fecha</th>
                                                <th class="text-center" style="width: 100px;">Estado</th>
                                                <th class="text-end" style="width: 100px;">Total</th>
                                                <th class="text-center" style="width: 150px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($albaranes as $albaran)
                                            <tr>
                                                <td class="text-center">
                                                    <strong>{{ $albaran->numero_albaran }}</strong>
                                                </td>
                                                <td>
                                                    @if($albaran->proveedor)
                                                        {{ $albaran->proveedor->nombre }}
                                                        <br><small class="text-muted">{{ $albaran->proveedor->cif }}</small>
                                                    @else
                                                        <small class="text-muted">Sin proveedor</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    {{ $albaran->fecha->format('d/m/Y') }}
                                                </td>
                                                <td class="text-center">
                                                    @if($albaran->estado == 'pendiente')
                                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                                    @elseif($albaran->estado == 'recibido')
                                                        <span class="badge bg-success">Recibido</span>
                                                    @else
                                                        <span class="badge bg-info">Facturado</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <strong>{{ number_format($albaran->total, 2) }} €</strong>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ route('albaranes.show', $albaran->id) }}" 
                                                           class="btn btn-sm btn-info" title="Ver detalles">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        @if($albaran->estado == 'pendiente')
                                                        <a href="{{ route('albaranes.edit', $albaran->id) }}" 
                                                           class="btn btn-sm btn-secondary" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form action="{{ route('albaranes.destroy', $albaran->id) }}" 
                                                              method="POST" class="d-inline"
                                                              onsubmit="return confirm('¿Está seguro de eliminar este albarán?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-center mt-3">
                            {{ $albaranes->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
<div class="card-footer">
    <div class="d-flex align-items-center justify-content-center gap-2">
        <button type="submit" form="formFiltros" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-search"></i>
        </button>
        </a>
        <a href="{{ route('albaranes.create') }}" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-plus-circle"></i>
        </a>
    </div>
</div>
@endsection
