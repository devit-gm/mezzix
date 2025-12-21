@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4"><i class="bi bi-calendar-check"></i> {{ __('Mis Inscripciones') }}</h1>
            <p class="lead text-muted">{{ __('Eventos a los que estás inscrito') }}</p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('eventos-publicos.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-calendar-event"></i> {{ __('Ver todos los eventos') }}
            </a>
        </div>
    </div>

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

    @if($misEventos->isEmpty())
        <div class="row">
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-calendar-x display-1 text-muted"></i>
                        <h3 class="mt-3">{{ __('No tienes inscripciones') }}</h3>
                        <p class="text-muted">{{ __('Explora el catálogo y apúntate a eventos') }}</p>
                        <a href="{{ route('eventos-publicos.index') }}" class="btn btn-primary mt-3">
                            <i class="bi bi-calendar-event"></i> {{ __('Ver eventos disponibles') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Evento') }}</th>
                                        <th>{{ __('Fecha') }}</th>
                                        <th>{{ __('Hora') }}</th>
                                        <th>{{ __('Estado') }}</th>
                                        <th>{{ __('Inscritos') }}</th>
                                        <th>{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($misEventos as $evento)
                                        @php
                                            $esFuturo = \Carbon\Carbon::parse($evento->fecha) >= now()->startOfDay();
                                            $estaAbierto = $evento->estado == 0;
                                        @endphp
                                        <tr class="{{ !$esFuturo ? 'table-secondary' : '' }}">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($evento->foto_evento)
                                                        <img src="{{ $evento->foto_evento }}" alt="{{ $evento->descripcion }}" 
                                                             class="rounded me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                    @else
                                                        <div class="bg-primary rounded me-2 d-flex align-items-center justify-content-center text-white" 
                                                             style="width: 50px; height: 50px;">
                                                            <i class="bi bi-calendar-event"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <strong>{{ $evento->descripcion }}</strong>
                                                        @if(!$esFuturo)
                                                            <br><small class="text-muted">{{ __('Evento pasado') }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="bi bi-calendar3"></i>
                                                {{ \Carbon\Carbon::parse($evento->fecha)->locale('es')->isoFormat('D MMM YYYY') }}
                                            </td>
                                            <td>
                                                @if($evento->hora)
                                                    <i class="bi bi-clock"></i> {{ \Carbon\Carbon::parse($evento->hora)->format('H:i') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if($estaAbierto)
                                                    <span class="badge bg-success">{{ __('Abierto') }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ __('Cerrado') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($evento->aforo_maximo)
                                                    <small class="text-muted">
                                                        {{ $evento->inscritos_actuales }} / {{ $evento->aforo_maximo }}
                                                    </small>
                                                @else
                                                    <small class="text-muted">{{ $evento->inscritos_actuales }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('eventos-publicos.show', $evento->uuid) }}" 
                                                       class="btn btn-outline-primary" title="{{ __('Ver detalles') }}">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    @if($esFuturo && $estaAbierto)
                                                        <form action="{{ route('eventos-publicos.cancelar', $evento->uuid) }}" 
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger" 
                                                                    title="{{ __('Cancelar inscripción') }}"
                                                                    onclick="return confirm('{{ __('¿Seguro que quieres cancelar tu inscripción?') }}')">
                                                                <i class="bi bi-x-circle"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
