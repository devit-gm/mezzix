@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-cart-fill"></i> {{ __('Compras por Usuario') }}
                </div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="container-fluid">
                       <div class="row col-12"> 
                        <!-- Filtros de fecha -->
                        <form id="form-filtro-fechas" method="GET" action="{{ route('informes.compras-usuarios') }}" class="mb-4">
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
                        <div class="row g-2 mb-3">
                        <!-- Tabla de usuarios -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>{{ __('Usuario') }}</th>
                                        <th class="text-center">{{ __('Nº Fichas') }}</th>
                                        <th class="text-end">{{ __('Total') }}</th>
                                        <th class="text-end">{{ __('Ticket Medio') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($comprasUsuarios as $index => $usuario)
                                    <tr>
                                        <td class="fw-bold">{{ $usuario->usuario }}</td>
                                        <td class="text-center">
                                            {{ $usuario->total_compras }}
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($usuario->total_gastado, 2) }}€</td>
                                        <td class="text-end">{{ number_format($usuario->ticket_medio, 2) }}€</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
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
