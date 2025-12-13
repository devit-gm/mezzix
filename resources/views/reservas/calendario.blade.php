@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-calendar3"></i> {{ __('Bookings') }} - {{ __('Calendar') }}
                </div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="container-fluid">
                        <!-- Navegación del mes -->
                        <div class="row mb-3">
                            <div class="col-12 p-0 d-flex justify-content-between align-items-center">
                                <a href="{{ route('reservas.calendario', ['mes' => $mesPrev, 'año' => $añoPrev]) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-chevron-left"></i> {{ __('Previous') }}
                                </a>
                                <h4 class="mb-0 text-center">{{ $mesNombre }} {{ $año }}</h4>
                                <a href="{{ route('reservas.calendario', ['mes' => $mesNext, 'año' => $añoNext]) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ __('Next') }} <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>
</div>
                    <div class="container-fluid">
                        <!-- Calendario -->
                        <div class="row">
                            <div class="col-12 p-0">
                                <div class="calendar-grid">
                                    <!-- Encabezados de días -->
                                    <div class="calendar-header">
                                        <div class="calendar-day-header">{{ __('Mon') }}</div>
                                        <div class="calendar-day-header">{{ __('Tue') }}</div>
                                        <div class="calendar-day-header">{{ __('Wed') }}</div>
                                        <div class="calendar-day-header">{{ __('Thu') }}</div>
                                        <div class="calendar-day-header">{{ __('Fri') }}</div>
                                        <div class="calendar-day-header">{{ __('Sat') }}</div>
                                        <div class="calendar-day-header">{{ __('Sun') }}</div>
                                    </div>

                                    <!-- Días del calendario -->
                                    <div class="calendar-body">
                                        @foreach($calendario as $semana)
                                            @foreach($semana as $dia)
                                                <div class="calendar-day {{ $dia['mes_actual'] ? '' : 'otro-mes' }} {{ $dia['es_hoy'] ? 'hoy' : '' }}">
                                                    <div class="calendar-day-number">{{ $dia['numero'] }}</div>
                                                    @if($dia['mes_actual'] && isset($reservasPorDia[$dia['fecha']]))
                                                        <div class="reservas-lista">
                                                            @foreach($reservasPorDia[$dia['fecha']] as $reserva)
                                                                <div class="reserva-mini" 
                                                                     data-bs-toggle="tooltip" 
                                                                     title="{{ $reserva->usuario->name ?? 'Sin usuario' }} - {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} a {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}">
                                                                    
                                                                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }}
                                                                   
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de reservas del día seleccionado -->
                        @if(request('dia'))
                            <div class="row mt-4">
                                <div class="col-12 ">
                                    <h5>{{ __('Bookings for') }} {{ request('dia') }}</h5>
                                    @php
                                        $reservasDia = $reservasPorDia[request('dia')] ?? collect();
                                    @endphp
                                    @if($reservasDia->count() > 0)
                                        <div class="list-group">
                                            @foreach($reservasDia as $reserva)
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>{{ $reserva->usuario->name ?? 'Sin usuario' }}</strong><br>
                                                            <small>
                                                                <i class="bi bi-clock"></i> {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                                            </small>
                                                            @if($reserva->comensales)
                                                                <small class="ms-2">
                                                                    <i class="bi bi-people"></i> {{ $reserva->comensales }} {{ __('guests') }}
                                                                </small>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <a href="{{ route('reservas.edit', $reserva->uuid) }}" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">{{ __('No bookings for this day') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @section('footer')
                <div class="card-footer text-center">
                    <a href="{{ route('reservas.create') }}" class="btn btn-sm borde-rojo fondo-rojo">
                        <i class="bi bi-plus-circle"></i>
                    </a>
                    <a href="{{ route('reservas.index') }}" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="bi bi-list"></i>
                    </a>
                </div>
                @endsection
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Calendario Grid */
.calendar-grid {
    width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.calendar-day-header {
    padding: 12px;
    text-align: center;
    font-weight: 600;
    color: #495057;
    border-right: 1px solid #dee2e6;
    font-size:1.4rem;
}

.calendar-day-header:last-child {
    border-right: none;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    grid-auto-rows: minmax(100px, auto);
}

.calendar-day {
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    padding: 8px;
    background-color: #fff;
    min-height: 100px;
    position: relative;
    transition: background-color 0.2s;
}

.calendar-day:nth-child(7n) {
    border-right: none;
}

.calendar-day:hover {
    background-color: #f8f9fa;
}

.calendar-day.otro-mes {
    background-color: #f8f9fa;
    opacity: 0.5;
}

.calendar-day.hoy {
    background-color: #fff3cd;
}

.calendar-day.hoy .calendar-day-number {
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.calendar-day-number {
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 14px;
}

.reservas-lista {
    margin-top: 8px;
}

.reserva-mini {
    background-color: #dc3545;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    margin-bottom: 4px;
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.reserva-mini:hover {
    background-color: #c82333;
}

.reserva-mini i {
    font-size: 8px;
    margin-right: 2px;
}

/* Responsive */
@media (max-width: 768px) {
    .calendar-body {
        grid-auto-rows: minmax(80px, auto);
    }
    
    .calendar-day {
        min-height: 80px;
        padding: 4px;
    }

    .calendar-day-header {
        font-size: 1rem;
        padding: 8px;
    }
    
    .calendar-day-number {
        font-size: 12px;
    }
    
    .reserva-mini {
        font-size: 12px;
        padding: 1px 4px;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Activar tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Click en día para ver detalles
    document.querySelectorAll('.calendar-day').forEach(function(dia) {
        dia.addEventListener('click', function() {
            const fecha = this.dataset.fecha;
            if (fecha) {
                window.location.href = "{{ route('reservas.calendario', ['mes' => $mes, 'año' => $año]) }}&dia=" + fecha;
            }
        });
    });
});
</script>
@endpush
@endsection
