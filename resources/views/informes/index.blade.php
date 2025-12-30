@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-card-list"></i> {{ __('BALANCE POR SOCIO') }}
                </div>

                <div class="card-body py-2" style="flex: 0 0 auto;">
    <form id="realizar-busqueda" action="{{ route('informes.index') }}" method="post">
        @csrf
        @method('PUT')

        {{-- Mensajes --}}
        @if (session('success'))
            <div class="custom-success-container mb-2">
                <ul class="custom-success-list">
                    <li class="custom-success-item">{{ session('success') }}</li>
                </ul>
            </div>
        @endif

        @if ($errors->any())
            <div class="custom-error-container mb-2">
                <ul class="custom-error-list">
                    @foreach ($errors->all() as $error)
                        <li class="custom-error-item">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FILTROS EN FILA --}}
        <div class="row g-2 align-items-end">

            <div class="col-6 col-sm-6">
                <label for="fecha_inicial" class="fw-bold form-label mb-1">{{ __('Fecha inicial') }}</label>
                <input type="date" class="form-control" 
                       name="fecha_inicial" id="fecha_inicial"
                       value="{{ $request->fecha_inicial }}" 
                       onchange="actualizarFechaFinal()">
            </div>

            <div class="col-6 col-sm-6">
                <label for="fecha_final" class="fw-bold form-label mb-1">{{ __('Fecha final') }}</label>
                <input type="date" class="form-control"
                       name="fecha_final" id="fecha_final"
                       value="{{ $request->fecha_final }}">
            </div>

            @if (!$ajustes->facturar_ficha_automaticamente)
                <div class="col-12 col-sm-4">
                    <label for="incluir_facturados" class="fw-bold form-label mb-1">{{ __('Incluir facturados') }}</label>
                    <select name="incluir_facturados" id="incluir_facturados" 
                            class="form-select form-select-sm" required>
                        <option value="0" @selected($request->incluir_facturados == 0)>No</option>
                        <option value="1" @selected($request->incluir_facturados == 1)>Sí</option>
                    </select>
                </div>
            @endif

        </div>
    </form>
</div>


                <!-- Sección de resultados con scroll independiente -->
                <div class="card-body overflow-auto flex-fill" style="border-top: 1px solid #dee2e6;">
                    <div class="container-fluid">
                        <div class="row justify-content-center">
                            <div class="col-12">
                                @php
    $totalGastos = 0;
    $totalCompras = 0;
    $totalBalance = 0;
@endphp

<style>
    .tabla-minimalista {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 25px;
        background: #fff;
    }

    .tabla-minimalista th {
        font-weight: 600;
        padding: 12px 0;
        text-align: center;
        color: #222;
        border-bottom: 1px solid #e6e6e6;
        background: #fafafa;
        letter-spacing: 0.3px;
    }

    .tabla-minimalista td {
        padding: 10px 0;
        text-align: center;
        font-size: 18px;
        color: #333;
        border-bottom: 1px solid #f1f1f1;
    }

    .tabla-minimalista .cabecera-usuario th {
        background: transparent !important;
        border-bottom: none !important;
        font-size: 18px;
        padding: 20px 0 10px 0;
        text-align: left;
    }

    .tabla-minimalista tfoot td {
        font-weight: 600;
        color: #000;
    }
</style>

@foreach ($usuariosInforme as $usuario)

@php
    $totalGastos += $usuario->gastos;
    $totalCompras += $usuario->compras;
    $totalBalance += $usuario->balance;
@endphp

<table class="tabla-minimalista">
    <tbody>
        <tr class="cabecera-usuario">
            <th colspan="3">
                {{ $usuario->name }}
            </th>
        </tr>

        <tr>
            <th><i class="bi bi-cup-straw"></i></th>
            <th><i class="bi bi-cart2"></i></th>
            <th><i class="bi bi-graph-up"></i></th>
        </tr>

        <tr>
            <td>{{ number_format($usuario->gastos, 2) }}€</td>
            <td>{{ number_format($usuario->compras, 2) }}€</td>
            <td>{{ number_format($usuario->balance, 2) }}€</td>
        </tr>
    </tbody>
</table>

@endforeach


<table class="tabla-minimalista">
    <tbody>
        <tr class="cabecera-usuario">
            <th colspan="3">{{ __('TOTAL') }}</th>
        </tr>

        <tr>
            <th><i class="bi bi-cup-straw"></i></th>
            <th><i class="bi bi-cart2"></i></th>
            <th><i class="bi bi-graph-up"></i></th>
        </tr>

        <tr>
            <td>{{ number_format($totalGastos, 2) }}€</td>
            <td>{{ number_format($totalCompras, 2) }}€</td>
            <td>{{ number_format($totalBalance, 2) }}€</td>
        </tr>
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

@push('scripts')
<script>
let validandoFechaFinal = false;

function actualizarFechaFinal() {
    const fechaInicial = document.getElementById('fecha_inicial');
    const fechaFinal = document.getElementById('fecha_final');
    
    if (fechaInicial.value) {
        // Si la fecha final está vacía o es anterior a la inicial, actualizarla
        if (!fechaFinal.value || fechaFinal.value < fechaInicial.value) {
            fechaFinal.value = fechaInicial.value;
        }
        // Establecer la fecha mínima (aunque Safari iOS no lo respete)
        fechaFinal.setAttribute('min', fechaInicial.value);
    } else {
        fechaFinal.removeAttribute('min');
    }
}

// Validación manual para Safari iOS cuando cambia la fecha final
function validarFechaFinal() {
    if (validandoFechaFinal) return;
    
    const fechaInicial = document.getElementById('fecha_inicial');
    const fechaFinal = document.getElementById('fecha_final');
    
    if (fechaInicial.value && fechaFinal.value) {
        if (fechaFinal.value < fechaInicial.value) {
            validandoFechaFinal = true;
            // Forzar blur para cerrar el datepicker antes del alert
            fechaFinal.blur();
            setTimeout(function() {
                alert('{{ __('La fecha final no puede ser anterior a la fecha inicial') }}');
                fechaFinal.value = fechaInicial.value;
                validandoFechaFinal = false;
            }, 100);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    actualizarFechaFinal();
    
    // Agregar validación al cambiar la fecha final
    document.getElementById('fecha_final').addEventListener('change', validarFechaFinal);
    document.getElementById('fecha_final').addEventListener('blur', validarFechaFinal);
});
</script>
@endpush

@section('footer')
<div class="card-footer">
    <form id="form-facturar" method="POST" action={{ route('informes.facturar'); }}>
        @csrf
        @method('PUT')
        <div class="d-flex align-items-center justify-content-center">
            <button type="button" onclick="document.getElementById('realizar-busqueda').submit();" class="btn btn-secondary mx-1"><i class="bi bi-search"></i></button>
            <button type="button" class="btn btn-info mx-1" data-bs-toggle="modal" data-bs-target="#modalResumen" title="{{ __('Ver resumen') }}"><i class="bi bi-table"></i></button>
            @if ($mostrarBotonFacturar == true)
            @if (Auth::user()->role_id < \App\Enums\Role::USUARIO_MESAS) </form>
                <a class="btn btn-success fondo-rojo borde-rojo fs-3" href="#" onclick="if(confirm('{{ __('Se marcará como facturado todo lo pendiente. ¿Desea continuar?') }}')){ document.getElementById('form-facturar').submit(); }"><i class="bi bi-cash-coin"></i></a>
                @endif
                @endif
        </div>
    </form>
</div>

<!-- Modal Resumen -->
<div class="modal fade" id="modalResumen" tabindex="-1" aria-labelledby="modalResumenLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header fondo-rojo">
                <h6 class="modal-title text-white" id="modalResumenLabel">{{ __('Resumen del Balance') }}</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm mb-0" style="font-size: 0.85rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="py-1">{{ __('Usuario') }}</th>
                                <th class="text-end py-1">{{ __('Gastos') }}</th>
                                <th class="text-end py-1">{{ __('Compras') }}</th>
                                <th class="text-end py-1">{{ __('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usuariosInforme as $usuario)
                            <tr>
                                <td class="py-1">{{ $usuario->name }}</td>
                                <td class="text-end py-1">{{ number_format($usuario->gastos, 2) }}€</td>
                                <td class="text-end py-1">{{ number_format($usuario->compras, 2) }}€</td>
                                <td class="text-end py-1 fw-bold">{{ number_format($usuario->balance, 2) }}€</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td class="py-1">{{ __('TOTAL') }}</td>
                                <td class="text-end py-1">{{ number_format($totalGastos, 2) }}€</td>
                                <td class="text-end py-1">{{ number_format($totalCompras, 2) }}€</td>
                                <td class="text-end py-1">{{ number_format($totalBalance, 2) }}€</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer p-2 justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> </button>
            </div>
        </div>
    </div>
</div>
@endsection