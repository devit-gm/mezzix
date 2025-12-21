@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4"><i class="bi bi-calendar-event"></i> {{ __('Catálogo de Eventos') }}</h1>
            <p class="lead text-muted">{{ __('Descubre y apúntate a los próximos eventos') }}</p>
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

    @if($eventos->isEmpty())
        <div class="row">
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-calendar-x display-1 text-muted"></i>
                        <h3 class="mt-3">{{ __('No hay eventos disponibles') }}</h3>
                        <p class="text-muted">{{ __('Vuelve pronto para ver los próximos eventos') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($eventos as $evento)
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="{{ route('eventos-publicos.show', $evento->uuid) }}" class="text-decoration-none">
                        <div class="card h-100 shadow-sm hover-shadow transition">
                            @if($evento->foto_evento)
                                <img src="{{ $evento->foto_evento }}" class="card-img-top" alt="{{ $evento->descripcion }}" style="height: 250px; object-fit: cover;">
                            @else
                                <div class="card-img-top bg-gradient d-flex align-items-center justify-content-center" style="height: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="bi bi-calendar-event text-white" style="font-size: 4rem;"></i>
                                </div>
                            @endif
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-dark">{{ $evento->descripcion }}</h5>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3"></i> 
                                    {{ \Carbon\Carbon::parse($evento->fecha)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
                                </small>
                                @if($evento->hora)
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> {{ \Carbon\Carbon::parse($evento->hora)->format('H:i') }}
                                    </small>
                                @endif
                            </div>

                            @if($evento->descripcion_evento)
                                <p class="card-text text-muted small">
                                    {{ Str::limit($evento->descripcion_evento, 120) }}
                                </p>
                            @endif

                            <div class="mt-auto">
                                @if($evento->aforo_maximo)
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-people"></i> 
                                            {{ $evento->inscritos_actuales }} / {{ $evento->aforo_maximo }} {{ __('inscritos') }}
                                        </small>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: {{ $evento->aforo_maximo > 0 ? ($evento->inscritos_actuales / $evento->aforo_maximo * 100) : 0 }}%"
                                                 aria-valuenow="{{ $evento->inscritos_actuales }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="{{ $evento->aforo_maximo }}">
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="d-flex justify-content-between align-items-center">
                                    @if(isset($evento->precio) && floatval($evento->precio) > 0)
                                        <span class="h5 mb-0 text-success">{{ number_format($evento->precio, 2) }} €</span>
                                    @else
                                        <span class="badge bg-success">{{ __('Gratis') }}</span>
                                    @endif
                                    
                                    <span class="btn btn-primary btn-sm">
                                        {{ __('Ver detalles') }} <i class="bi bi-arrow-right"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    cursor: pointer;
}
</style>
@endsection
