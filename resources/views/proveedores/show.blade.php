@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-eye"></i> {{ __('Detalle del Proveedor') }}
                </div>

                <div class="card-body overflow-auto flex-fill">
                        
                        @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif
<div class="container-fluid">
                    
                        
                        <div class="row">
                            <!-- Información del Proveedor -->
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <strong>Información General</strong>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr>
                                                <th width="40%">Nombre:</th>
                                                <td><strong>{{ $proveedor->nombre }}</strong></td>
                                            </tr>
                                            <tr>
                                                <th>CIF/NIF:</th>
                                                <td>{{ $proveedor->cif ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Email:</th>
                                                <td>
                                                    @if($proveedor->email)
                                                        <a href="mailto:{{ $proveedor->email }}">{{ $proveedor->email }}</a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Teléfono:</th>
                                                <td>
                                                    @if($proveedor->telefono)
                                                        <a href="tel:{{ $proveedor->telefono }}">{{ $proveedor->telefono }}</a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Contacto:</th>
                                                <td>{{ $proveedor->contacto_principal ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Estado:</th>
                                                <td>
                                                    @if($proveedor->activo)
                                                        <span class="badge bg-success">Activo</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactivo</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Dirección y Condiciones -->
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <strong>Dirección y Condiciones</strong>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <tr>
                                                <th width="40%">Dirección:</th>
                                                <td>{{ $proveedor->direccion ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Ciudad:</th>
                                                <td>{{ $proveedor->ciudad ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Código Postal:</th>
                                                <td>{{ $proveedor->codigo_postal ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>País:</th>
                                                <td>{{ $proveedor->pais ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Días de Pago:</th>
                                                <td><strong>{{ $proveedor->dias_pago }} días</strong></td>
                                            </tr>
                                            <tr>
                                                <th>Descuento:</th>
                                                <td>{{ $proveedor->descuento_general }}%</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Condiciones de Pago y Notas -->
                        @if($proveedor->condiciones_pago || $proveedor->cuenta_bancaria || $proveedor->notas)
                        <div class="row mb-3">
                            @if($proveedor->condiciones_pago)
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong>Condiciones de Pago</strong>
                                    </div>
                                    <div class="card-body">
                                        {{ $proveedor->condiciones_pago }}
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($proveedor->cuenta_bancaria)
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong>Cuenta Bancaria</strong>
                                    </div>
                                    <div class="card-body">
                                        <code>{{ $proveedor->cuenta_bancaria }}</code>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($proveedor->notas)
                            <div class="col-12 mb-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong>Notas</strong>
                                    </div>
                                    <div class="card-body">
                                        {{ $proveedor->notas }}
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
</div>
<div class="container-fluid">
                        <!-- Historial de Albaranes -->
                        <div class="card mb-12 col-12 col-lg-12 col-sm-12">
                            <div class="card-header bg-light">
                                <strong>Historial de Albaranes</strong>
                            </div>
                            <div class="card-body">
                                @if($albaranes->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Nº Albarán</th>
                                                <th>Estado</th>
                                                <th class="text-end">Total</th>
                                                <th>Productos</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($albaranes as $albaran)
                                            <tr>
                                                <td>{{ $albaran->fecha ? $albaran->fecha->format('d/m/Y') : '-' }}</td>
                                                <td>{{ $albaran->numero_albaran ?? '-' }}</td>
                                                <td>
                                                    @if($albaran->estado === 'pendiente')
                                                        <span class="badge bg-warning">Pendiente</span>
                                                    @elseif($albaran->estado === 'recibido')
                                                        <span class="badge bg-success">Recibido</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $albaran->estado }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end"><strong>{{ number_format($albaran->total, 2) }} €</strong></td>
                                                <td>
                                                    <small class="text-muted">{{ $albaran->lineas->count() }} líneas</small>
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('albaranes.show', $albaran->id) }}" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if($albaranes->hasPages())
                                <div class="mt-3">
                                    {{ $albaranes->links() }}
                                </div>
                                @endif
                                @else
                                <p class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i><br>
                                    No hay albaranes registrados para este proveedor
                                </p>
                                @endif
                            </div>
                        </div>

                        <!-- Gráfico de Compras (Opcional) -->
                        @if($comprasPorMes->count() > 0)
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong>Evolución de Compras (Últimos 12 meses)</strong>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Mes</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($comprasPorMes as $compra)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::create($compra->año, $compra->mes)->isoFormat('MMMM YYYY') }}</td>
                                                <td class="text-end">{{ number_format($compra->total, 2) }} €</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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
        <a href="{{ route('proveedores.index') }}" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-chevron-left"></i>
        </a>
        <a href="{{ route('proveedores.edit', $proveedor->uuid) }}" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-pencil"></i>
        </a>
        <a href="{{ route('albaranes.create') }}?proveedor={{ $proveedor->uuid }}" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-plus-circle"></i>
        </a>
    </div>
</div>
@endsection
