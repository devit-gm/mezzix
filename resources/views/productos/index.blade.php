@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo"><i class="bi bi-cup-straw"></i> {{ __('Products') }}</div>

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
                                <table class="table table-bordered table-responsive table-hover">
                                    <thead>
                                        <tr class="">
                                            <th scope="col-auto" class="text-center" style="width: 90px;">{{ __('Imagen') }}</th>
                                            <th scope="col-auto">{{ __('Nombre') }}</th>
                                            <th scope="col-auto" class="text-center">{{ __('Precio') }}</th>
                                           
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($productos as $producto)
                                        @php
                                            $stockDisponible = max(0, $producto->stock - ($producto->stock_reservado ?? 0));
                                            $esStockBajo = $stockDisponible <= ($ajustes->stock_minimo ?? 5) && $stockDisponible > 0;
                                            $esAgotado = $stockDisponible <= 0;
                                        @endphp
                                        <tr class="clickable-row" data-href="{{ route('productos.edit', $producto->uuid) }}" data-hrefborrar="{{ route('productos.destroy', $producto->uuid) }}" data-textoborrar="{{ __('¿Está seguro de eliminar el producto?') }}" data-borrable="{{$producto->borrable}}">
                                            <td class="align-middle">
                                                <img width="80" height="80" 
                                                     class="img-fluid rounded img-responsive" 
                                                     src="{{ cachedImage($producto->imagen) }}" 
                                                     alt="{{ $producto->nombre }}"
                                                     loading="lazy"
                                                     decoding="async" />
                                            </td>
                                            <td class="align-middle">
                                                {{ $producto->nombre }}
                                                <br />
                                                @if ($producto->familia)
                                                <span class="badge bg-secondary">{{ $producto->familia->nombre }}</span>
                                                @endif
                                                <br />
                                                @if ($producto->combinado == 1)
                                                <span class="badge btn bt-sm btn-info color-negro">{{ __('Combinado') }}</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-center">
                                                {{ $producto->precio }}€
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
        </div>
    </div>
    @endsection
	@section('footer')

                <div class="card-footer">
                    <form>
                        <div class="d-flex align-items-center justify-content-center">

                            @if (Auth::user()->role_id < \App\Enums\Role::USUARIO_MESAS) <a href="{{ route('productos.inventory') }}" class="btn btn-secondary mx-1"><i class="bi bi-card-checklist"></i></a>
                                <a href="{{ route('productos.create') }}" class="btn btn-primary fondo-rojo borde-rojo mx-1"><i class="bi bi-plus-circle"></i></a>
                                @endif
                        </div>
                    </form>
                </div>

@push('scripts')
<script>
    // Inicializar tooltips de Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
@endsection