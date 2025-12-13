@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo"><i class="bi bi-calendar3"></i> {{ __('Bookings') }} - Gestión</div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="container-fluid">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-12 col-md-12 col-lg-12">
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
                                @if($reservas->count() > 0)

                                @push('styles')
<style>
/* ===================== */
/* Estilo minimalista reservas */
/* ===================== */

.reserva-item {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    cursor: pointer;
    background-color: #fff;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.reserva-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.fecha-badge {
    width: 60px;
    flex-shrink: 0;
    text-align: center;
}

.fecha-badge .mes {
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #6c757d;
}

.fecha-badge .dia {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111;
}

.reserva-info {
    flex-grow: 1;
    padding-left: 12px;
}

.reserva-info .usuario {
    font-weight: 600;
    font-size: 0.95rem;
    color: #111;
}

.reserva-info .nombre-reserva {
    font-size: 0.85rem;
    color: #6c757d;
}

.reserva-actions {
    flex-shrink: 0;
}

.btn-outline-danger {
    border-radius: 6px;
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}
</style>
@endpush

@foreach ($reservas as $reserva)
<div class="reserva-item clickable-row"
     data-href="{{ route('reservas.edit', $reserva->uuid) }}" 
     data-hrefborrar="{{ route('reservas.destroy', $reserva->uuid) }}" 
     data-textoborrar="{{ __('¿Está seguro de eliminar la reserva?') }}" 
     data-borrable="{{ $reserva->borrable }}">
    
    <!-- Fecha -->
    <div class="fecha-badge">
        <div class="mes">{{ $reserva->mes }}</div>
        <div class="dia">{{ $reserva->dia }}</div>
    </div>

    <!-- Info reserva -->
    <div class="reserva-info">
        <div class="usuario">{{ $reserva->usuario->name }}</div>
        <div class="nombre-reserva">{{ $reserva->name }}</div>
    </div>

    <!-- Botón borrar -->
    <div class="reserva-actions">
        @if($reserva->borrable)
        <button class="btn btn-md btn-borrar-min btn-danger" onclick="triggerParentClick(event,this);">
            <i class="bi bi-trash"></i>
        </button>
        @endif
    </div>

</div>
@endforeach



                                @endif
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
                            <a href="{{ route('reservas.create') }}" class="btn btn-primary fondo-rojo borde-rojo mx-1"><i class="bi bi-plus-circle"></i></a>
                            <a href="{{ route('reservas.calendario') }}" class="btn btn-primary fondo-rojo borde-rojo mx-1"><i class="bi bi-calendar-week"></i></a>
                        </div>
                    </form>
                </div>
				
@endsection