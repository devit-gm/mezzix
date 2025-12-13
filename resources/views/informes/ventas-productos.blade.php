@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-box-seam-fill"></i> {{ __('Ventas por Producto') }}
                </div>
                <div class="card-body overflow-auto flex-fill">

                    <form id="form-filtro-fechas" method="GET" action="{{ route('informes.ventas-productos') }}" class="mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="fecha_inicial" class="form-label small mb-1">{{ __('Fecha inicial') }}</label>
                                <input type="date" class="form-control form-control-sm" id="fecha_inicial"
                                       name="fecha_inicial" value="{{ $fechaInicial }}" required>
                            </div>
                            <div class="col-6">
                                <label for="fecha_final" class="form-label small mb-1">{{ __('Fecha final') }}</label>
                                <input type="date" class="form-control form-control-sm" id="fecha_final"
                                       name="fecha_final" value="{{ $fechaFinal }}" required>
                            </div>
                        </div>
                    </form>

                    <div class="row g-2 mb-3">
      
                        <div class="col-4 col-md-3">
                            <div class="alert alert-primary mb-0 text-center">
                                <small class="d-block mb-1">{{ __('Total Facturado') }}</small>
                                <strong class="fs-5">{{ number_format($totalGeneral, 2) }}€</strong>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="alert alert-success mb-0 text-center">
                                <small class="d-block mb-1">{{ __('Nº Artículos') }}</small>
                                <strong class="fs-5">{{ $ventasProductos->count() }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-1">
                            <div class="alert alert-secondary mb-0 text-center">
                                <small class="d-block mb-1">{{ __('Uds') }}</small>
                                <strong class="fs-5">{{ number_format($cantidadTotal, 0) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover align-middle" style="min-width: 100%;">
                            <thead class="table-dark">
                                <tr>
                                    <th style="min-width: auto;">{{ __('Producto') }}</th>
                                    <th class="text-center" style="width: auto;">{{ __('Uds') }}</th>
                                    <th class="text-end d-lg-table-cell" style="width: auto;">{{ __('P.Unit') }}</th>
                                    <th class="text-center d-none d-xl-table-cell" style="width: auto;">{{ __('IVA %') }}</th>
                                    <th class="text-end d-none d-lg-table-cell" style="width: auto;">{{ __('Base Imp.') }}</th>
                                    <th class="text-end d-none d-xl-table-cell" style="width: auto;">{{ __('Imp. IVA') }}</th>
                                    <th class="text-end" style="width: auto;">{{ __('Total') }}</th>
                                    <th class="text-center d-md-table-cell" style="width: auto;">%</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($ventasProductos as $index => $venta)
                                <tr>
                                    <td style="white-space: normal; word-break: break-word;">
                                        <small><i class="bi bi-box-seam"></i> <strong>{{ $venta->producto }}</strong></small>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge bg-info" style="font-size: 0.75rem;">{{ number_format($venta->cantidad_vendida,0) }}</span>
                                    </td>

                                    <td class="text-end d-lg-table-cell">
                                        <small>{{ number_format($venta->precio,2) }}€</small>
                                    </td>

                                    <td class="text-center d-none d-xl-table-cell">
                                        <small>{{ number_format($venta->iva ?? 0, 0) }}%</small>
                                    </td>

                                    <td class="text-end d-none d-lg-table-cell">
                                        <small>{{ number_format($venta->base_imponible ?? 0, 2) }}€</small>
                                    </td>

                                    <td class="text-end d-none d-xl-table-cell">
                                        <small class="text-warning">{{ number_format($venta->importe_iva ?? 0, 2) }}€</small>
                                    </td>

                                    <td class="text-end fw-bold text-success" style="white-space: nowrap;">
                                        <small>{{ number_format($venta->total_vendido, 2) }}€</small>
                                    </td>

                                    <td class="text-center d-md-table-cell">
                                        @php
                                            $pct = $totalGeneral > 0 ? ($venta->total_vendido / $totalGeneral) * 100 : 0;
                                        @endphp
                                        <div class="progress" style="height: 16px; min-width: 70px;">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: {{ $pct }}%; "
                                                 aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                                <small style="font-size: 0.7rem;">{{ number_format($pct, 1) }}%</small>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        <small>{{ __('No hay datos para el período seleccionado') }}</small>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="table-secondary fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end"><small>{{ __('TOTAL:') }}</small></td>
                                    <td class="text-end d-lg-table-cell"></td>
                                    <td class="text-center d-none d-xl-table-cell"></td>
                                    <td class="text-end d-none d-lg-table-cell"><small>{{ number_format($subtotalGeneral ?? 0, 2) }}€</small></td>
                                    <td class="text-end d-none d-xl-table-cell"><small class="text-warning">{{ number_format($totalIvaGeneral ?? 0, 2) }}€</small></td>
                                    <td class="text-end text-primary"><small>{{ number_format($totalGeneral, 2) }}€</small></td>
                                    <td class="text-center d-md-table-cell"><small>100%</small></td>
                                </tr>
                            </tfoot>

                        </table>
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

