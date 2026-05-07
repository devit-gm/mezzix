@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex">
            <div class="card flex-fill">
                <div class="card-header fondo-rojo d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-journal-text"></i> {{ $ajustes->modo_operacion === 'mesas' ? $ficha->descripcion :  __('Ficha - Resumen') }}</span>
                    <!-- @if($ajustes->modo_operacion === 'mesas')
                        <span class="badge bg-light text-dark fs-5">{{ number_format($ficha->precio,2) }} <i class="bi bi-currency-euro"></i></span>
                    @endif -->
                </div>

                <div class="card-body">
                    <!-- @if($ajustes->modo_operacion !== 'mesas')
                    <div class="d-grid gap-2 d-md-flex justify-content-end col-sm-12 col-md-8 col-lg-12">
                        <button class="btn btn-lg btn-light border border-dark">{{number_format($ficha->precio,2)}} <i class="bi bi-currency-euro"></i></button>
                    </div>
                    @endif -->
                    <div class="container-fluid @if($ajustes->modo_operacion !== 'mesas') @endif" style="padding:0px;">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-12 col-md-12 col-lg-12">
                                <form id="ficha-resumen" action="{{ fichaRoute('enviar', $ficha->uuid) }}" method="post">
                                    @csrf
                                    @method('PUT')

                                    <table class="table table-borderless shadow-sm rounded overflow-hidden">
                                        <tbody>
                                            @if($ficha->tipo != 3)
                                            <tr class="bg-light">
                                                <th scope="row" class="fw-semibold text-secondary">{{ __('Total consumos') }}</th>
                                                <td class="text-end">
                                                    {{ number_format($ficha->total_consumos,2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th scope="row" class="fw-semibold text-secondary">{{ __('Total servicios') }}</th>
                                                <td class="text-end">
                                                    {{ number_format($ficha->total_servicios,2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>

                                            @if(isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas')
                                            {{-- Desglose de IVA para modo mesas (calculado en controlador) --}}

                                            @if(isset($ivaDesglose) && count($ivaDesglose) > 0)
                                            <tr class="bg-light">
                                                <td colspan="2" class="pt-3">
                                                    <strong class="text-secondary">{{ __('Desglose de IVA') }}</strong>
                                                </td>
                                            </tr>
                                            @foreach($ivaDesglose as $datos)
                                            <tr>
                                                <th scope="row" class="fw-normal text-secondary ps-4">
                                                    {{ __('IVA') }} {{ number_format($datos['porcentaje'], 0) }}%
                                                    <span class="text-muted small">({{ number_format($datos['base'], 2) }} € {{ __('base') }})</span>
                                                </th>
                                                <td class="text-end">
                                                    {{ number_format($datos['cuota'], 2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>
                                            @endforeach
                                            <tr class="bg-light">
                                                <th scope="row" class="fw-semibold text-secondary">{{ __('Base Imponible') }}</th>
                                                <td class="text-end">
                                                    {{ number_format($totalBaseImponible, 2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="fw-semibold text-secondary">{{ __('Total IVA') }}</th>
                                                <td class="text-end">
                                                    {{ number_format($totalIva, 2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>
                                            @endif
                                            @endif

                                            @endif

                                            @if(!isset($ajustes->modo_operacion) || $ajustes->modo_operacion == 'fichas' || (isset($ajustes->mostrar_gastos) && $ajustes->mostrar_gastos == 1))
                                            <tr class="bg-light">
                                                <th scope="row" class="fw-semibold text-secondary">{{ __('Total gastos') }}</th>
                                                <td class="text-end">
                                                    {{ number_format($ficha->total_gastos,2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>
                                            @endif

                                            @if($ficha->tipo != 3)
                                            <tr>
                                                <th scope="row" class="fw-semibold text-secondary" style="vertical-align:middle;">
                                                    @if(!empty($ficha->es_infantil))
                                                    {{ __('Niños') }}
                                                    @else
                                                    {{ __('Comensales') }}
                                                    @if(!isset($ajustes->modo_operacion) || $ajustes->modo_operacion == 'fichas')
                                                    <br>
                                                    <span class="text-muted small">({{ __('No incluye niños') }})</span>
                                                    @endif
                                                    @endif
                                                </th>
                                                <td class="text-end">{{ $ficha->total_comensales }}</td>
                                            </tr>

                                            <tr class="bg-light" style="border-top: 1px solid #e5e5e5;">
                                                <th scope="row" class="fw-semibold text-secondary">
                                                    @if(!empty($ficha->es_infantil))
                                                    {{ __('Total por niño') }}
                                                    @else
                                                    {{ __('Total por comensal') }}
                                                    @endif
                                                </th>
                                                <td class="text-end">
                                                    {{ number_format($ficha->precio_comensal,2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>

                                            <tr style="border-top: 1px solid #e5e5e5;">
                                                <th scope="row" class="fw-semibold text-dark">
                                                    {{ $ajustes->modo_operacion === 'mesas' ? __('TOTAL MESA') : __('TOTAL FICHA') }}
                                                </th>
                                                <td class="fw-semibold text-end text-dark">
                                                    {{ number_format($ficha->precio,2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>

                                            @if(isset($cargoInvitados) && $cargoInvitados > 0)
                                            <tr style="background-color: rgba(167, 56, 13, 0.1); border-top: 2px solid #A7380D;">
                                                <th scope="row" class="fw-semibold" style="color: #A7380D;">
                                                    <i class="bi bi-person-plus-fill me-2"></i>{{ __('Tu cargo por invitados') }}
                                                </th>
                                                <td class="fw-semibold text-end" style="color: #A7380D;">
                                                    + {{ number_format($cargoInvitados, 2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(isset($cargoNinos) && $cargoNinos > 0)
                                            <tr style="background-color: rgba(167, 56, 13, 0.1); border-top: 2px solid #A7380D;">
                                                <th scope="row" class="fw-semibold" style="color: #A7380D;">
                                                    <i class="bi bi-people-fill me-2"></i>{{ __('Importe de la ficha correspondiente a tus niños') }}
                                                </th>
                                                <td class="fw-semibold text-end" style="color: #A7380D;">
                                                    {{ number_format($cargoNinos, 2) }}
                                                    <i class="bi bi-currency-euro"></i>
                                                </td>
                                            </tr>
                                            @endif
                                            @endif
                                        </tbody>
                                    </table>

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
            @if(!isset($ajustes->modo_operacion) || $ajustes->modo_operacion == 'fichas')
            {{-- Modo fichas: siempre ir a gastos --}}
            <a class="btn btn-dark mx-1" href="{{ fichaRoute('gastos', $ficha->uuid) }}"><i class="bi bi-chevron-left"></i></a>
            @elseif($ajustes->modo_operacion == 'mesas' && isset($ajustes->mostrar_gastos) && $ajustes->mostrar_gastos)
            {{-- Modo mesas con gastos habilitados: ir a gastos --}}
            <a class="btn btn-dark mx-1" href="{{ fichaRoute('gastos', $ficha->uuid) }}"><i class="bi bi-chevron-left"></i></a>
            @else
            {{-- Modo mesas sin gastos: ir a servicios --}}
            <a class="btn btn-dark mx-1" href="{{ fichaRoute('servicios', $ficha->uuid) }}"><i class="bi bi-chevron-left"></i></a>
            @endif

            @if(isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas')
            {{-- En modo mesas, siempre mostrar el botón para volver al grid --}}
            <a href="{{ route('mesas.index') }}" class="btn btn-success mx-1"><i class="bi bi-grid-3x3-gap"></i></a>
            @elseif(($ficha->precio>0 || ($ficha->tipo == 3 && $ficha->gastos > 0)) && $ficha->estado == 0)
            {{-- En modo fichas, solo mostrar el botón de enviar si hay importe --}}
            <button type="button" onclick="document.getElementById('ficha-resumen').submit();" class="btn btn-success mx-1"><i class="bi bi-send"></i></button>
            @endif
        </div>
    </form>
</div>
@endsection