@extends('layouts.app')

@section('content')
@php
    $modoOperacion = $ajustes->modo_operacion ?? 'fichas';
    $esAgenciaEventos = ($modoOperacion === 'agencia_eventos');
    
    // Definir prefijo de rutas según el modo
    $rutaPrefix = $esAgenciaEventos ? 'eventos.gestion' : 'fichas';
@endphp
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo"><i class="bi bi-receipt"></i> 
                    @if($request->incluir_cerradas == 0)
                    @if($esAgenciaEventos)
                    {{ __('EVENTOS ACTIVOS') }}
                    @else
                    {{ __('FICHAS ABIERTAS') }}
                    @endif
                    @endif
                    @if($request != null)
                    @if($request->incluir_cerradas == 1)
                    @if($esAgenciaEventos)
                    {{ __('EVENTOS FINALIZADOS') }}
                    @else
                    {{ __('FICHAS CERRADAS') }}
                    @endif
                    @endif
                    @endif</div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="container-fluid">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-12 col-md-12 col-lg-12 p-0">
                                <form id="realizar-busqueda" action="{{ route($rutaPrefix . '.index') }}" method="post">
                                    @csrf
                                    @method('PUT')

                                    <!-- <div class="form-group mb-3">
                                        <label for="incluir_cerradas" class="fw-bold form-label">Mostrar fichas cerradas:</label>
                                        <select name="incluir_cerradas" id="incluir_cerradas" class="form-select form-select-sm" aria-label=".form-select-sm example">
                                            <option value="0" @if ($request->incluir_cerradas == 0) selected @endif >No</option>
                                            <option value="1" @if ($request->incluir_cerradas == 1) selected @endif>Sí</option>
                                        </select>
                                    </div>
                                    <br /> -->
                                    @if($request != null)
                                    @if($request->incluir_cerradas == 1)
                                    <input type="hidden" name="incluir_cerradas" id="incluir_cerradas" value="0" />
                                    @else
                                    <input type="hidden" name="incluir_cerradas" id="incluir_cerradas" value="1" />
                                    @endif
                                    @endif

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

                                    @foreach ($fichas as $ficha)
                                    @php
                                    $esUsuarioBasico = Auth::user()->role_id >= \App\Enums\Role::USUARIO_MESAS;
                                    
                                    // Usar la información ya calculada en el controlador
                                    $estaInscrito = ($esAgenciaEventos && $ficha->tipo == 4 && isset($ficha->apuntado));
                                    
                                    if ($esAgenciaEventos) {
                                        // En modo agencia, usuarios básicos van al detalle público
                                        if ($esUsuarioBasico) {
                                            $ruta = route('eventos-publicos.show', ['uuid' => $ficha->uuid]);
                                        } else {
                                            // Administradores van a editar
                                            $ruta = route('eventos-publicos.show', ['uuid' => $ficha->uuid]);
                                        }
                                    } else {
                                        // Lógica original para modo fichas
                                        if ($ficha->tipo != 3) {
                                            $ruta = route('fichas.familias', ['uuid' => $ficha->uuid]);
                                        } else {
                                            $ruta = route('fichas.gastos', ['uuid' => $ficha->uuid]);
                                        }
                                        if($ficha->estado == 1 && $ficha->tipo != 3){
                                            $ruta = route('fichas.lista', ['uuid' => $ficha->uuid]);
                                        }
                                        if($ficha->tipo == 4){
                                            $ruta = route('fichas.usuarios', ['uuid' => $ficha->uuid]);
                                        }
                                    }
                                    @endphp
                                    <table class="table table-bordered table-responsive">
                                        <tbody>
                                            @if ($ficha->descripcion != null && $ficha->descripcion != '')
                                            <tr>
                                                <td colspan="3" class="align-middle">
                                                    @if($ficha->tipo == 4 && $ficha->apuntado)
                                                        <div style="float:right">
                                                            <i class="bi bi-calendar-check-fill color-rojo" style="font-size:1.1em;"></i>
                                                            <i class="bi bi-person-standing" style="margin-right: 0.1em;"></i>{{$ficha->apuntado->invitados}} 
                                                            <i class="bi bi-person-fill" style="margin-right: 0.1em;"></i>{{$ficha->apuntado->ninos}}          
                                                        </div>
                                                    @endif
                                                    <b>{{ $ficha->descripcion }}</b>
                                                </td>
                                            </tr>
                                            @endif
                                            @if($ficha->tipo == 4)
                                            @if ($ficha->menu != null && $ficha->menu != '')
                                            <!-- Añadir icono de menú -->
                                            <tr>
                                                <td colspan="3" class="align-middle">
                                                    <i class="bi bi-book"></i> {{ $ficha->menu }}
                                                </td>
                                            </tr>
                                            @endif
                                            @if ($ficha->responsables != null && $ficha->responsables != '')
                                            <!-- Añadir icono de responsables -->
                                            <tr>
                                                <td colspan="3" class="align-middle">
                                                    <i class="bi bi-person-circle"></i> {{ $ficha->responsables }}
                                                </td>
                                            </tr>
                                            @endif
                                            @endif


                                            <tr class="clickable-row" data-href="{{$ruta}}" data-hrefborrar="{{ route($rutaPrefix . '.destroy', $ficha->uuid) }}" data-textoborrar="{{ $esAgenciaEventos ? __('¿Está seguro de eliminar el evento?') : __('¿Está seguro de eliminar la ficha?') }}" data-hrefrestarcantidadmethod="GET" data-hrefrestarcantidad="{{ route($rutaPrefix . '.edit', ['uuid' => $ficha->uuid]) }}" data-borrable="{{$ficha->borrable}}">
                                                <td class="align-middle text-center" style="width:85px">
                                                    @if($ficha->tipo == 4 && $ficha->hora != null)
                                                    <span class="badge bg-danger mt-2 fondo-rojo">{{\Carbon\Carbon::parse($ficha->hora)->format('H:i')}}</span>
                                                    @endif
                                                    <div class="fondo-calendario">

                                                        <p style="padding-top:14px">
                                                            <span style="font-size:0.8em; text-transform:uppercase"><b>{{ $ficha->mes }}</b></span>
                                                            <span style="clear: both;display: block; margin-top: -8px;">{{ \Carbon\Carbon::parse($ficha->fecha)->format('j') }}</span>
                                                        </p>

                                                    </div>

                                                </td>


                                                <td class="align-middle">

                                                    @if ($ficha->tipo != 4 && $ficha->tipo != 2)
                                                    @if ($ficha->usuario)
                                                    {{ $ficha->usuario->name }}
                                                    <br />
                                                    @endif
                                                    @endif
                                                    @if ($ficha->tipo == 1)
                                                            <span class="badge-tipo individual">
                                                                <i class="bi bi-person-fill"></i>
                                                                {{ __('Individual') }}
                                                            </span>

                                                        @elseif($ficha->tipo == 2)
                                                            <div>
                                                                <i class="bi bi-people-fill"></i> {{ $ficha->total_comensales }}
                                                                <i class="bi bi-person-standing"></i> {{ $ficha->total_comensales - $ficha->total_ninos }}
                                                                <i class="bi bi-person-fill"></i> {{ $ficha->total_ninos }}
                                                            </div>
                                                            <span class="badge-tipo conjunta">
                                                                <i class="bi bi-people-fill"></i>
                                                                {{ __('Conjunta') }}
                                                            </span>

                                                        @elseif($ficha->tipo == 3)
                                                            <span class="badge-tipo compra">
                                                                <i class="bi bi-cart"></i>
                                                                {{ __('Compra') }}
                                                            </span>

                                                        @elseif($ficha->tipo == 4)
                                                            @if($esAgenciaEventos)
                                                                {{-- Vista para eventos en modo agencia: mostrar precio e inscritos --}}
                                                                <div class="text-muted" style="font-size: 1em;">
                                                                    @if($ficha->precio && $ficha->precio > 0)
                                                                        <strong>{{ number_format($ficha->precio, 2) }} €</strong>
                                                                    @else
                                                                        <strong>{{ __('Gratuito') }}</strong>
                                                                    @endif
                                                                </div>

                                                                <div class="mb-1">
                                                                    <i class="bi bi-people-fill"></i> 
                                                                    <strong>{{ $ficha->inscritos_actuales ?? 0 }}</strong> / {{ $ficha->aforo_maximo ?? 0 }} {{ __('inscritos') }}
                                                                </div>

                                                                @if($ficha->ubicacion_evento)
                                                                <div class="text-muted small">
                                                                    <i class="bi bi-geo-alt-fill"></i> {{ $ficha->ubicacion_evento }}
                                                                </div>
                                                                @endif
                                                            @else
                                                                {{-- Vista para modo fichas normal --}}
                                                                <div>
                                                                    <i class="bi bi-people-fill"></i> {{ $ficha->total_comensales }}
                                                                    <i class="bi bi-person-standing"></i> {{ $ficha->total_comensales - $ficha->total_ninos }}
                                                                    <i class="bi bi-person-fill"></i> {{ $ficha->total_ninos }}
                                                                </div>
                                                                <span class="badge-tipo evento">
                                                                    <i class="bi bi-calendar-event"></i>
                                                                    {{ __('Evento') }}
                                                                </span>
                                                            @endif
                                                        @endif

                                                    @if(!$esAgenciaEventos)
                                                    <br />
                                                    @endif
                                                    @if ($ficha->estado == 0)
                                                    <span class="badge d-none bg-success">{{ __('Abierta') }}</span>
                                                    @elseif ($ficha->estado == 1)
                                                    <span class="badge d-none bg-dark border border-dark">{{ __('Cerrada') }}</span>
                                                    @endif

                                                </td>
                                                @php
                                                    $esUsuarioNormal = Auth::user()->role_id >= \App\Enums\Role::USUARIO_MESAS;
                                                    $esCreadorFicha = Auth::id() == $ficha->user_id;
                                                    $puedeEditarFicha = !$esUsuarioNormal || $esCreadorFicha;
                                                    
                                                    // Determinar si hay botones para mostrar
                                                    $mostrarCelda = false;
                                                    if ($ficha->estado == 0) {
                                                        if ($esAgenciaEventos) {
                                                            $mostrarCelda = true; // Siempre mostrar en agencia
                                                        } elseif ($puedeEditarFicha && $ficha->borrable) {
                                                            $mostrarCelda = true;
                                                        }
                                                    } elseif (!$esAgenciaEventos) {
                                                        $mostrarCelda = true; // Ticket
                                                    }
                                                @endphp
                                                @if($mostrarCelda)
                                                <td class="align-middle text-center" style="width: 50px">
                                                    <div class="d-flex justify-content-center" style="flex-direction: column;">
                                                        @if ($ficha->estado == 0)
                                                            @if($esAgenciaEventos)
                                                                @if($esUsuarioBasico)
                                                                    {{-- Botón de ver detalle con indicador de inscripción --}}
                                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                                        <a class="btn btn-sm btn-primary" href="{{ $ruta }}"><i class="bi bi-eye fs-5"></i></a>
                                                                        @if($estaInscrito)
                                                                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                                                        @endif
                                                                    </div>
                                                                @else
                                                                    {{-- Botones de editar/borrar para administradores --}}
                                                                    <a class="btn btn-sm btn-dark mb-2" href="{{ route($rutaPrefix . '.edit', ['uuid' => $ficha->uuid]) }}"><i class="bi bi-pencil fs-5"></i></a>
                                                                    @if($ficha->borrable)
                                                                        <a class="btn btn-sm btn-danger" href="#" onclick="triggerParentClick(event,this);"><i class="bi bi-trash fs-5"></i></a>
                                                                    @endif
                                                                @endif
                                                            @elseif($puedeEditarFicha && $ficha->borrable)
                                                                {{-- Modo fichas normal: editar y borrar solo si es admin o creador --}}
                                                                <a class="btn btn-sm btn-dark mb-2" href="{{ route($rutaPrefix . '.edit', ['uuid' => $ficha->uuid]) }}"><i class="bi bi-pencil fs-5"></i></a>
                                                                <a class="btn btn-sm btn-danger" href="#" onclick="triggerParentClick(event,this);"><i class="bi bi-trash fs-5"></i></a>
                                                            @endif
                                                        @else
                                                            @if(!$esAgenciaEventos)
                                                            <a class="btn btn-sm btn-success mb-2" href="{{ route('fichas.ticket', ['uuid' => $ficha->uuid]) }}" title="{{ __('Descargar Ticket') }}"><i class="bi bi-receipt"></i></a>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                                @endif
                                            </tr>
                                        </tbody>
                                    </table>
                                    @endforeach
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
            @if ($request != null && $request->incluir_cerradas == 1)
            <button type="button" onclick="document.getElementById('realizar-busqueda').submit();" class="btn btn-primary mx-1">
                <i class="bi bi-calendar-check"></i>
            </button>
            @else
            <button type="button" onclick="document.getElementById('realizar-busqueda').submit();" class="btn btn-primary mx-1">
                <i class="bi bi-clock-history"></i>
            </button>
            @endif
            @php
                $esUsuarioBasicoFooter = Auth::user()->role_id >= \App\Enums\Role::USUARIO_MESAS;
                $puedeCrearEvento = !($esAgenciaEventos && $esUsuarioBasicoFooter);
            @endphp
            @if($puedeCrearEvento)
            <a href="{{ route($rutaPrefix . '.create') }}" class="btn btn-primary fondo-rojo borde-rojo mx-1"><i class="bi bi-plus-circle"></i></a>
            @endif
        </div>
    </form>
</div>
@endsection