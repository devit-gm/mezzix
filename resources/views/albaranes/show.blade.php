@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam"></i> Albarán {{ $albaran->numero_albaran }}</span>
                    @if($albaran->estado == 'pendiente')
                        <span class="badge bg-warning text-dark">Pendiente</span>
                    @elseif($albaran->estado == 'recibido')
                        <span class="badge bg-success">Recibido</span>
                    @endif
                </div>

                <div class="card-body overflow-auto flex-fill">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <div class="container-fluid">
                        <div class="row mb-3">
                            <!-- Información del Proveedor -->
                            <div class="col-12 col-md-6">
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <i class="bi bi-person-badge"></i> Proveedor
                                    </div>
                                    <div class="card-body">
                                        @if($albaran->proveedor)
                                            <p class="mb-2"><strong>Nombre:</strong> {{ $albaran->proveedor->nombre }}</p>
                                            <p class="mb-2"><strong>CIF:</strong> {{ $albaran->proveedor->cif }}</p>
                                            @if($albaran->proveedor->telefono)
                                            <p class="mb-2"><strong>Teléfono:</strong> {{ $albaran->proveedor->telefono }}</p>
                                            @endif
                                            @if($albaran->proveedor->email)
                                            <p class="mb-0"><strong>Email:</strong> {{ $albaran->proveedor->email }}</p>
                                            @endif
                                        @else
                                            <p class="mb-0 text-muted">Sin proveedor asignado</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Información del Albarán -->
                            <div class="col-12 col-md-6">
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <i class="bi bi-file-text"></i> Datos del Albarán
                                    </div>
                                    <div class="card-body">
                                        @if($albaran->fecha_albaran)
                                        <p class="mb-2"><strong>Fecha Albarán:</strong> {{ $albaran->fecha_albaran->format('d/m/Y') }}</p>
                                        @endif
                                        <p class="mb-2"><strong>Fecha Recepción:</strong> {{ $albaran->fecha->format('d/m/Y') }}</p>
                                        <p class="mb-2"><strong>Creado por:</strong> {{ $albaran->usuario->name ?? 'N/A' }}</p>
                                        @if($albaran->fecha_recepcion)
                                        <p class="mb-2"><strong>Confirmado:</strong> {{ $albaran->fecha_recepcion->format('d/m/Y H:i') }}</p>
                                        @endif
                                        @if($albaran->observaciones)
                                        <p class="mb-0"><strong>Observaciones:</strong> {{ $albaran->observaciones }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                                            <div class="container-fluid">


                        <div class="row">
                            <!-- Líneas de Productos -->
                            <div class="col-12">
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <i class="bi bi-cart"></i> Productos
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th class="text-center" style="width: 100px;">Cantidad</th>
                                                        <th class="text-end" style="width: 120px;">Precio Coste</th>
                                                        <th class="text-end" style="width: 120px;">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($albaran->lineas as $linea)
                                                    <tr>
                                                        <td>
                                                            {{ $linea->producto->nombre ?? 'Producto eliminado' }}
                                                            @if($linea->producto && $linea->producto->familiaObj)
                                                            <br><small class="text-muted">{{ $linea->producto->familiaObj->nombre }}</small>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">{{ number_format($linea->cantidad, 2) }}</td>
                                                        <td class="text-end">{{ number_format($linea->precio_coste, 2) }} €</td>
                                                        <td class="text-end"><strong>{{ number_format($linea->subtotal, 2) }} €</strong></td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                                        <td class="text-end"><strong class="fs-5">{{ number_format($albaran->total, 2) }} €</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
        <a href="{{ route('albaranes.index') }}" class="btn btn-secondary">
            <i class="bi bi-chevron-left"></i>
        </a>
        @if($albaran->estado == 'pendiente')
        <a href="{{ route('albaranes.edit', $albaran->id) }}" class="btn btn-secondary">
            <i class="bi bi-pencil"></i>
        </a>
        <form action="{{ route('albaranes.confirmar', $albaran->id) }}" method="POST" class="d-inline"
              onsubmit="return confirm('¿Confirmar la recepción del albarán? Esto actualizará el stock de los productos.')">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-circle"></i>
            </button>
        </form>
        <form action="{{ route('albaranes.destroy', $albaran->id) }}" method="POST" class="d-inline"
              onsubmit="return confirm('¿Está seguro de eliminar este albarán?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash"></i>
            </button>
        </form>
        @else
        <a href="{{ route('albaranes.pdf', $albaran->id) }}" class="btn btn-primary" target="_blank">
            <i class="bi bi-file-pdf"></i>
        </a>
        @endif
    </div>
</div>
@endsection
