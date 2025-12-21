@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo"><i class="bi bi-calendar-event"></i> {{ $evento->descripcion }}</div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="container-fluid">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-12 col-md-12 col-lg-12">
                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('success') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif

                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        {{ session('error') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif

                                @if(session('info'))
                                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                                        {{ session('info') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="card shadow-sm mb-4">
                                            @if($evento->foto_evento)
                                            <img src="{{ asset('storage/' . $evento->foto_evento) }}" class="card-img-top" alt="{{ $evento->descripcion }}" style="max-height: 400px; object-fit: cover;">
                                            @endif
                                            
                                            <div class="card-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6 col-lg-4">
                                                        <h5><i class="bi bi-calendar3"></i> {{ __('Fecha') }}</h5>
                                                        <p class="text-muted">{{ \Carbon\Carbon::parse($evento->fecha)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
                                                    </div>
                                                    @if($evento->hora)
                                                    <div class="col-md-6 col-lg-4">
                                                        <h5><i class="bi bi-clock"></i> {{ __('Hora') }}</h5>
                                                        <p class="text-muted">{{ \Carbon\Carbon::parse($evento->hora)->format('H:i') }}</p>
                                                    </div>
                                                    @endif
                                                    @if($evento->ubicacion_evento)
                                                    <div class="col-md-12 col-lg-4">
                                                        <h5><i class="bi bi-geo-alt-fill"></i> {{ __('Ubicación') }}</h5>
                                                        <p class="text-muted">{{ $evento->ubicacion_evento }}</p>
                                                    </div>
                                                    @endif
                                                </div>

                                                @if($evento->descripcion_evento)
                                                <div class="mb-3">
                                                    <h5><i class="bi bi-info-circle"></i> {{ __('Descripción') }}</h5>
                                                    <p class="text-muted" style="white-space: pre-line;">{{ $evento->descripcion_evento }}</p>
                                                </div>
                                                @endif

                                                @if($evento->menu)
                                                <div class="mb-3">
                                                    <h5><i class="bi bi-cup-straw"></i> {{ __('Menú') }}</h5>
                                                    <p class="text-muted">{{ $evento->menu }}</p>
                                                </div>
                                                @endif

                                                @if($evento->responsables)
                                                <div class="mb-2">
                                                    <h5><i class="bi bi-person-badge"></i> {{ __('Responsables') }}</h5>
                                                    <p class="text-muted">{{ $evento->responsables }}</p>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="card shadow-sm">
                                            <div class="card-body">
                                                <h4 class="card-title mb-4">{{ __('Información de inscripción') }}</h4>

                                                @if(isset($evento->precio) && floatval($evento->precio) > 0)
                                                    <div class="mb-3">
                                                        <h5>{{ __('Precio') }}</h5>
                                                        <p class="h3 text-success mb-0">{{ number_format($evento->precio, 2) }} €</p>
                                                    </div>
                                                @else
                                                    <div class="mb-3">
                                                        <span class="badge bg-success fs-5">{{ __('Evento Gratuito') }}</span>
                                                    </div>
                                                @endif

                                                @if($evento->aforo_maximo)
                                                    <div class="mb-3">
                                                        <h6><i class="bi bi-people"></i> {{ __('Aforo') }}</h6>
                                                        <p class="mb-2">{{ $evento->inscritos_actuales }} / {{ $evento->aforo_maximo }} {{ __('inscritos') }}</p>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar {{ $plazasDisponibles <= 0 ? 'bg-danger' : ($plazasDisponibles <= 5 ? 'bg-warning' : 'bg-success') }}" 
                                                                 role="progressbar" 
                                                                 style="width: {{ $evento->aforo_maximo > 0 ? ($evento->inscritos_actuales / $evento->aforo_maximo * 100) : 0 }}%">
                                                                @if($plazasDisponibles > 0)
                                                                    {{ $plazasDisponibles }} {{ __('plazas') }}
                                                                @else
                                                                    {{ __('Completo') }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @auth
                                                    @if($estaInscrito)
                                                        <div class="alert alert-info">
                                                            <i class="bi bi-check-circle-fill"></i> {{ __('Ya estás inscrito en este evento') }}
                                                        </div>
                                                    @elseif($evento->aforo_maximo && $plazasDisponibles <= 0)
                                                        <div class="alert alert-danger">
                                                            <i class="bi bi-x-circle"></i> {{ __('Evento completo') }}
                                                        </div>
                                                    @elseif($evento->estado != 0)
                                                        <div class="alert alert-secondary">
                                                            <i class="bi bi-info-circle"></i> {{ __('Inscripciones cerradas') }}
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="alert alert-warning">
                                                        <i class="bi bi-info-circle"></i> {{ __('Debes iniciar sesión para inscribirte') }}
                                                    </div>
                                                @endauth
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
</div>
@endsection

@section('footer')
<div class="card-footer">
    <form>
        <div class="d-flex align-items-center justify-content-center">
            <a class="btn btn-dark mx-1" href="{{ route('eventos.gestion.index') }}"><i class="bi bi-chevron-left"></i></a>
            
            @auth
                @if(Auth::user()->role_id == 1 || $evento->user_id == Auth::id())
                    {{-- Botones de gestión para administradores --}}
                    <a class="btn btn-primary mx-1" href="{{ route('fichas.edit', $evento->uuid) }}" title="{{ __('Editar evento') }}">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a class="btn btn-info mx-1" href="{{ route('fichas.usuarios', $evento->uuid) }}" title="{{ __('Ver inscritos') }}">
                        <i class="bi bi-people"></i>
                    </a>
                @else
                    {{-- Botones de inscripción para usuarios normales --}}
                    @if($estaInscrito)
                        {{-- Botón para cancelar inscripción --}}
                        <button type="button" class="btn btn-danger mx-1" 
                                onclick="if(confirm('{{ __('¿Seguro que quieres cancelar tu inscripción?') }}')) { document.getElementById('form-cancelar-inscripcion').submit(); }">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    @else
                        @if($evento->estado == 0 && (!$evento->aforo_maximo || $plazasDisponibles > 0))
                            {{-- Botón para inscribirse --}}
                            <button type="button" class="btn btn-success mx-1" onclick="document.getElementById('form-inscribirse').submit();">
                                <i class="bi bi-calendar-check"></i>
                            </button>
                        @endif
                    @endif
                @endif
            @else
                {{-- Botón para ir a login --}}
                <a class="btn btn-primary mx-1" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i></a>
            @endauth
        </div>
    </form>
    
    {{-- Formularios ocultos para las acciones --}}
    @auth
        @if($estaInscrito)
            <form id="form-cancelar-inscripcion" action="{{ route('eventos-publicos.cancelar', $evento->uuid) }}" method="POST" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        @else
            @if($evento->estado == 0 && (!$evento->aforo_maximo || $plazasDisponibles > 0))
                <form id="form-inscribirse" action="{{ route('eventos-publicos.inscribirse', $evento->uuid) }}" method="POST" style="display: none;">
                    @csrf
                </form>
            @endif
        @endif
    @endauth
</div>
@endsection
