@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-box-seam-fill"></i> {{ __('Ventas por Producto') }}
                </div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="container-fluid">
                        <div class="row col-12">
                        <!-- Filtros de fecha -->
                        <form id="form-filtro-fechas" method="GET" action="{{ route('informes.ventas-productos-fichas') }}" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="fecha_inicial" class="form-label fw-bold">{{ __('Fecha inicial') }}</label>
                                    <input type="date" class="form-control" id="fecha_inicial" name="fecha_inicial" value="{{ $fechaInicial }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="fecha_final" class="form-label fw-bold">{{ __('Fecha final') }}</label>
                                    <input type="date" class="form-control" id="fecha_final" name="fecha_final" value="{{ $fechaFinal }}" required>
                                </div>
                            </div>
                        </form>
</div>
                        </div>
                    <div class="container-fluid">
                        <div class="row col-12 g-2 mb-3">
                            <div class="col-4">
                                <div class="alert alert-primary mb-0 text-center">
                                    <small class="d-block mb-1">{{ __('Total Facturado') }}</small>
                                    <strong class="fs-5">{{ number_format($totalGeneral, 2) }}€</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="alert alert-success mb-0 text-center">
                                    <small class="d-block mb-1">{{ __('Nº Artículos') }}</small>
                                    <strong class="fs-5">{{ $ventasProductos->count() }}</strong>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="alert alert-secondary mb-0 text-center">
                                    <small class="d-block mb-1">{{ __('Uds') }}</small>
                                    <strong class="fs-5">{{ number_format($cantidadTotal, 0) }}</strong>
                                </div>
                            </div>
              <!-- Tabla de productos -->
                        <div class="table-responsive mt-4">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>{{ __('Producto') }}</th>
                                        <th class="text-end">{{ __('Precio U.') }}</th>
                                        <th class="text-center">{{ __('Cantidad') }}</th>
                                        <th class="text-end">{{ __('Total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ventasProductos as $index => $producto)
                                    <tr>
                                        <td class="fw-bold">{{ $producto->producto }}</td>
                                        <td class="text-end">{{ number_format($producto->precio, 2) }}€</td>
                                        <td class="text-center">
                                            {{ number_format($producto->cantidad_vendida, 0) }}
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($producto->total_vendido, 2) }}€</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            {{ __('No hay datos para el período seleccionado') }}
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
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
    <div class="d-flex align-items-center justify-content-center">
        <button type="button" onclick="document.getElementById('form-filtro-fechas').submit();" class="btn btn-secondary borde-rojo fondo-rojo mx-1">
            <i class="bi bi-search"></i>
        </button>
    </div>
</div>
@endsection
