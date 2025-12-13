@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-journal-text"></i> {{ $ajustes->modo_operacion === 'mesas' ? $ficha->descripcion :  __("FICHA - Servicios") }}</span>
                  
                        <span class="badge bg-light text-dark fs-5">{{ number_format($ficha->precio,2) }} <i class="bi bi-currency-euro"></i></span>
                   
                </div>

                <div class="card-body overflow-auto flex-fill">
                   
                    <div class="container-fluid @if($ajustes->modo_operacion !== 'mesas') @endif">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-12 col-md-12 col-lg-12">


                                <form id='editar-serviciosficha' action="{{ fichaRoute('updateservicios', $ficha->uuid) }}" method="post">
                                    @csrf
                                    @method('PUT')
                                    
                                        <div class="row">
                                            @if ($errors->any())
                                            <div class="custom-error-container" id="custom-error-container">
                                                <ul class="custom-error-list">
                                                    @foreach ($errors->all() as $error)
                                                    <li class="custom-error-item">{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @endif
                                            @if (session('success'))
                                            <div class="custom-success-container" id="custom-success-container">
                                                <ul class="custom-success-list">
                                                    <li class="custom-success-item">{{ session('success') }}</li>
                                                </ul>
                                            </div>
                                            @endif
                                            <style>
    /* ---- TABLA SERVICIOS (ESTILO MINIMALISTA) ---- */

    .tabla-servicios {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 6px;
        font-size: 0.95rem;
        padding:0px;
    }

    .tabla-servicios thead th {
        background: #f7f7f7;
        padding: 12px;
        font-weight: 600;
        border-bottom: 2px solid #e5e5e5;
        text-align: center;
    }

    .tabla-servicios tbody tr {
        background: #ffffff;
        border-radius: 8px;
        transition: background 0.2s ease;
        height: 80px;
    }

    .tabla-servicios tbody tr:hover {
        background: #f4f7ff;
    }

    .tabla-servicios td {
        padding: 16px;
        vertical-align: middle;
        border-top: 1px solid #efefef;
        font-size:18px;
    }

    .tabla-servicios td:first-child {
        border-left: 1px solid #efefef;
        border-radius: 8px 0 0 8px;
    }

    .tabla-servicios td:last-child {
        border-right: 1px solid #efefef;
        border-radius: 0 8px 8px 0;
    }

    /* ---- SWITCH ---- */
    .tabla-servicios .form-check-input {
        cursor: pointer;
        transform: scale(1.3);
    }
</style>


<table class="tabla-servicios table-responsive">
    <thead>
        <tr>
            <th class="text-start">{{ __('Servicio') }}</th>
            <th>{{ __('Precio') }}</th>

            @if($ficha->estado == 0 || (isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas'))
                <th>{{ __('Añadir') }}</th>
            @endif
        </tr>
    </thead>

    <tbody>
        @foreach ($serviciosFicha as $servicio)

        @php
            $esModoMesas = isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas';
            $ocultar = ($servicio->marcado == 0 && $ficha->estado == 1 && !$esModoMesas) ? "d-none" : "";
        @endphp

        <tr class="{{ $ocultar }}">

            <!-- Nombre -->
            <td class="align-middle">
                {{ $servicio->nombre }}
            </td>

            <!-- Precio -->
            <td class="align-middle text-center">
                {{ number_format($servicio->precio,2) }} <i class="bi bi-currency-euro"></i>
            </td>

            <!-- Switch -->
            @if($ficha->estado == 0 || (isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas'))
            <td class="align-middle text-center">
                <div class="form-check form-switch d-flex justify-content-center">
                    <input 
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        name="servicios[]"
                        value="{{ $servicio->uuid }}"
                        @if($servicio->marcado == 1) checked @endif
                        @if($ficha->estado == 1 && (!isset($ajustes->modo_operacion) || $ajustes->modo_operacion != 'mesas')) disabled @endif
                    >
                </div>
            </td>
            @endif

        </tr>
        @endforeach
    </tbody>
</table>

                                        
                                    </div>
                                </form>
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
    <form>
        <div class="d-flex align-items-center justify-content-center">
            @if(!isset($ajustes->modo_operacion) || $ajustes->modo_operacion == 'fichas' || (isset($ajustes->mostrar_usuarios) && $ajustes->mostrar_usuarios == 1))
            <a class="btn btn-dark mx-1" href={{ fichaRoute('usuarios', $ficha->uuid) }}><i class="bi bi-chevron-left"></i></a>
            @else
            <a class="btn btn-dark mx-1" href={{ fichaRoute('lista', $ficha->uuid) }}><i class="bi bi-chevron-left"></i></a>
            @endif
            @if($ficha->estado == 0 || (isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas'))
            <button type="button" onclick="document.getElementById('editar-serviciosficha').submit();" class="btn btn-success mx-1"><i class="bi bi-floppy"></i></button>
            @endif
            @php
                $siguienteRuta = route('fichas.resumen', $ficha->uuid);
                if (!isset($ajustes->modo_operacion) || $ajustes->modo_operacion == 'fichas') {
                    if (!isset($ajustes->mostrar_gastos) || $ajustes->mostrar_gastos == 1) {
                        $siguienteRuta = route('fichas.gastos', $ficha->uuid);
                    }
                }
            @endphp
            <a class="btn btn-dark mx-1" href="{{ $siguienteRuta }}"><i class="bi bi-chevron-right"></i></a>
        </div>
    </form>
</div>
@endsection