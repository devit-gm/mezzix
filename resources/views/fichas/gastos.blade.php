@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-journal-text"></i> {{ $ajustes->modo_operacion === 'mesas' ? $ficha->descripcion : __("Ficha") . ' - '  . __("Gastos") }}</span>
                    
                        <span class="badge bg-light text-dark fs-5">{{ number_format($ficha->precio,2) }} <i class="bi bi-currency-euro"></i></span>
                    
                </div>

                <div class="card-body overflow-auto flex-fill">
                    @if($ficha->tipo != 3)
                    
                    @else
                    @php
                    $totalGastos = 0;
                    @endphp
                    @foreach ($gastosFicha as $componente)
                    @php
                    $totalGastos += $componente->precio;
                    @endphp
                    @endforeach

                    <div class="d-grid gap-2 d-md-flex justify-content-end col-sm-12 col-md-12 col-lg-12">
                        <button class="btn btn-lg btn-light border border-dark">{{number_format($totalGastos,2)}} <i class="bi bi-currency-euro"></i></button>
                    </div>
                    @endif
                    <div class="container-fluid">
                        <div class="row justify-content-center align-items-center">
                            
                              
                                    <div class="row col-12 p-0">
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
    /* ===== TABLA GASTOS (ESTILO MINIMALISTA) ===== */

    .tabla-gastos {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
        margin-bottom: 20px;
        font-size: 0.95rem;
        padding:0px;
    }

    .tabla-gastos tbody tr.header-row th {
        background: #f7f7f7;
        padding: 14px;
        font-weight: 600;
        border: 1px solid #e6e6e6;
        border-radius: 8px;
    }

    .tabla-gastos tbody tr.data-row {
        background: #ffffff;
        border-radius: 8px;
        overflow: hidden;
        transition: background 0.2s ease;
        cursor: pointer;
    }

    .tabla-gastos tbody tr.data-row:hover {
        background: #f4f7ff;
    }

    .tabla-gastos td {
        padding: 16px;
        vertical-align: middle;
        border-top: 1px solid #f1f1f1;
        font-size:18px;
    }

    .tabla-gastos td:first-child {
        border-left: 1px solid #efefef;
        border-radius: 8px 0 0 8px;
        font-size:20px;
    }

    .tabla-gastos td:last-child {
        border-right: 1px solid #efefef;
        border-radius: 0 8px 8px 0;
    }

    /* Botón descarga */
    .btn-download {
        padding: 6px 10px;
        border-radius: 6px;
        border: none;
        transition: background 0.2s ease;
        font-size:20px;
    }

    .btn-download:hover {
        background: #dcdcdc;
    }

    /* Botón eliminar */
    .btn-delete {
        padding: 6px 9px;
        border-radius: 6px;
    }
</style>



@foreach ($gastosFicha as $componente)

<table class="tabla-gastos table-responsive">

    <tbody>

        {{-- Cabecera con nombre + botón de ticket --}}
        <tr class="header-row">
            <th colspan="3" class="align-middle justify-content-between">

                <span>{{ $componente->usuario->name }}</span>

                @if($componente->ticket != "")
                    @php
                        $ruta = URL::to('/') . '/images/' . $componente->ticket;
                    @endphp
                    <a href="{{ $ruta }}" target="_blank" class="btn btn-download">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                    </a>
                @endif

            </th>
        </tr>


        {{-- Fila de datos --}}
        @php
            $clickable = ($ficha->estado == 0) ? 'clickable-row' : '';
        @endphp

        <tr class="data-row {{ $clickable }}"
            data-hrefborrar="{{ fichaRoute('destroygastos', ['uuid' => $ficha->uuid, 'uuid2' => $componente->uuid]) }}"
            data-textoborrar="{{ __('¿Está seguro de eliminar el gasto de la lista?') }}"
            data-borrable="{{ $componente->borrable }}">

            {{-- Descripción --}}
            <td class="align-middle">
                {{ $componente->descripcion }}
            </td>

            {{-- Precio --}}
            <td class="align-middle text-center" style="width: 120px;">
                {{ number_format($componente->precio,2) }} <i class="bi bi-currency-euro"></i>
            </td>

            {{-- Botón eliminar --}}
            @if($ficha->estado == 0)
                <td class="align-middle text-center" style="width: 60px;">
                    <button class="btn btn-md btn-borrar-min btn-danger"
                        onclick="triggerParentClick(event,this);">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            @endif

        </tr>

    </tbody>

</table>

@endforeach


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
<div class=" card-footer">
    <form id="ficha-resumen" action="{{ fichaRoute('enviar', $ficha->uuid) }}" method="post">
        @csrf
        @method('PUT')

        <div class="d-flex align-items-center justify-content-center">
            @if($ficha->tipo != 3)
            <a class="btn btn-dark mx-1" href={{ route('fichas.servicios', $ficha->uuid) }}><i class="bi bi-chevron-left"></i></a>
            @if($ficha->estado == 0)
            <a class="btn btn-info mx-1" href={{ route('fichas.addgastos', $ficha->uuid) }}><i class="bi bi-plus-circle"></i></a>
            <a class="btn btn-success mx-1" href={{ route('fichas.resumen', $ficha->uuid) }}><i class="bi bi-check-circle"></i></a>
            @else
            <a class="btn btn-dark mx-1" href="{{ route('fichas.resumen', ['uuid'=>$ficha->uuid]) }}"><i class="bi bi-chevron-right"></i></a>
            @endif
            @endif

            @if($ficha->tipo == 3)
            <a class="btn btn-dark mx-1" href={{ route('fichas.index', $ficha->uuid) }}><i class="bi bi-chevron-left"></i></a>
            @if($ficha->estado == 0)
            <a class="btn btn-info mx-1" href={{ route('fichas.addgastos', $ficha->uuid) }}><i class="bi bi-plus-circle"></i></a>
            @endif
            @if(count($gastosFicha)>0 && $ficha->estado == 0)
            <button type="button" onclick="document.getElementById('ficha-resumen').submit();" class="btn btn-success mx-1"><i class="bi bi-send"></i></button>
            @endif
            @endif
        </div>
    </form>
</div>
@endsection