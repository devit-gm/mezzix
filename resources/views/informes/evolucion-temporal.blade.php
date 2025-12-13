@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-graph-up"></i> {{ __('Evolución Temporal') }}
                </div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="container-fluid">
                        <div class="row col-12 mb-2">
                        <!-- Filtros de fecha -->
                        <form id="form-filtro-fechas" method="GET" action="{{ route('informes.evolucion-temporal') }}" class="mb-4">
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
                        <!-- Ventas por día -->
                        <div class="row mb-2">
                            <h5 class="mb-3"><i class="bi bi-calendar-day"></i> {{ __('Ventas Diarias') }}</h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>{{ __('Fecha') }}</th>
                                            <th class="text-center">{{ __('Fichas') }}</th>
                                            <th class="text-end">{{ __('Total') }}</th>
                                            <th class="text-end">{{ __('Ticket Medio') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $maxVentas = $ventasPorDia->max('total_vendido') ?? 1;
                                        @endphp
                                        @forelse($ventasPorDia as $venta)
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="bi bi-calendar-check"></i>
                                                {{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }}
                                            </td>
                                            <td class="text-center">
                                                {{ $venta->num_transacciones }}
                                            </td>
                                            <td class="text-end fw-bold ">{{ number_format($venta->total_vendido, 2) }}€</td>
                                            <td class="text-end">{{ number_format($venta->ticket_medio, 2) }}€</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
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
                    <div class="container-fluid">
                        <!-- Ventas por mes (últimos 12 meses) -->
                        <div class="row">
                            <h5 class="mb-3"><i class="bi bi-calendar3"></i> {{ __('Ventas Mensuales') }} ({{ __('Últimos 12 meses') }})</h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>{{ __('Mes') }}</th>
                                            <th class="text-center">{{ __('Fichas') }}</th>
                                            <th class="text-end">{{ __('Total') }}</th>
                                            <th class="text-end">{{ __('Ticket Medio') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $maxVentasMes = $ventasPorMes->max('total_vendido') ?? 1;
                                        @endphp
                                        @forelse($ventasPorMes as $venta)
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="bi bi-calendar-month"></i>
                                                {{ $venta->mes_nombre }}
                                            </td>
                                            <td class="text-center">
                                                {{ $venta->num_transacciones }}
                                            </td>
                                            <td class="text-end fw-bold">{{ number_format($venta->total_vendido, 2) }}€</td>
                                            <td class="text-end">{{ number_format($venta->ticket_medio, 2) }}€</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                {{ __('No hay datos disponibles') }}
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
