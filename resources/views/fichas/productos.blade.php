@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-journal-text"></i> {{ $ajustes->modo_operacion === 'mesas' ? $ficha->descripcion : __("Ficha") . ' - '  . __("Products") }}</span>
                    
                        <span class="badge bg-light text-dark fs-5">{{ number_format($ficha->precio,2) }} <i class="bi bi-currency-euro"></i></span>
                    
                </div>
                <div class="card-body overflow-auto flex-fill">

                   
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

                    <div class="productos-grid">
                        @foreach($productos as $producto)
                        @if($productosAgotados->contains('uuid', $producto->uuid))
                        <div class="producto-card producto-agotado">
                            <form method="post">
                                @csrf
                                <button type="button" id="{{ $producto->uuid }}" class="btn p-0 position-relative border-0 w-100" disabled style="cursor: not-allowed;">
                                    <img 
                                        src="{{ cachedImage($producto->imagen) }}" 
                                        class="img-fluid rounded" 
                                        style="opacity: 0.5;" 
                                        alt="{{ $producto->nombre }}"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                    <span 
                                        class="position-absolute top-50 start-50 translate-middle" 
                                        style="background: rgba(220, 53, 69, 0.85); color: #fff; padding: 6px 12px; border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-align: center;"
                                    >
                                        {{ __('Agotado') }}
                                    </span>
                                </button>
                            </form>
                        </div>
                        @elseif($productosStockBajo->contains('uuid', $producto->uuid))
                        <div class="producto-card producto-stock-bajo">
                            <form action="{{ fichaRoute('addproduct',[$ficha->uuid, $familia->uuid]) }}" method="post" name="sumarcantidadform_{{$producto->uuid}}" id="sumarcantidadform_{{$producto->uuid}}">
                                @csrf
                                <input type="hidden" name="idFicha" value="{{ $ficha->uuid }}" />
                                <input type="hidden" name="idProducto" value="{{ $producto->uuid }}" />
                                <input type="hidden" name="idFamilia" value="{{ $familia->uuid }}" />
                                <input type="hidden" name="cantidad" id="sumarcantidadformcantidad_{{$producto->uuid}}" value="1" />
                                <button type="button" id="{{$producto->uuid}}" class="btn p-0 clickable-row w-100 position-relative" data-hrefsumarcantidadpreguntar="true" data-hrefsumarcantidad="self">
                                    <img src="{{ cachedImage($producto->imagen) }}" 
                                         class="img-fluid rounded" 
                                         alt="{{ $producto->nombre }}"
                                         loading="lazy"
                                         decoding="async">
                                    <span 
                                        class="position-absolute badge rounded-pill" 
                                        style="background: rgba(255, 193, 7, 0.9); color: #000; margin: 8px; padding: 4px 8px; top:0; left:0;"
                                    >
                                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.2rem;"></i>
                                    </span>
                                </button>
                            </form>
                        </div>
                        @else
                        <div class="producto-card">
                            <form action="{{ fichaRoute('addproduct',[$ficha->uuid, $familia->uuid]) }}" method="post" name="sumarcantidadform_{{$producto->uuid}}" id="sumarcantidadform_{{$producto->uuid}}">
                                @csrf
                                <input type="hidden" name="idFicha" value="{{ $ficha->uuid }}" />
                                <input type="hidden" name="idProducto" value="{{ $producto->uuid }}" />
                                <input type="hidden" name="idFamilia" value="{{ $familia->uuid }}" />
                                <input type="hidden" name="cantidad" id="sumarcantidadformcantidad_{{$producto->uuid}}" value="1" />
                                <button type="button" id="{{$producto->uuid}}" class="btn p-0 clickable-row w-100" data-hrefsumarcantidadpreguntar="true" data-hrefsumarcantidad="self">
                                    <img src="{{ cachedImage($producto->imagen) }}" 
                                         class="img-fluid rounded" 
                                         alt="{{ $producto->nombre }}"
                                         loading="lazy"
                                         decoding="async">
                                </button>
                            </form>
                        </div>
                        @endif
                        @endforeach
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
                <a class="btn btn-dark mx-1" href="{{ fichaRoute('familias', ['uuid'=>$ficha->uuid]) }}"><i class="bi bi-chevron-left"></i></a>
                <a class="btn btn-primary mx-1" href="{{ fichaRoute('lista', ['uuid'=>$ficha->uuid]) }}"><i class="bi bi-cart"></i></a>
            </div>
        </form>
    </div>
    @endsection

    @push('styles')
    <style>
    .productos-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        padding: 0.5rem;
    }

    .producto-card {
        display: flex;
        justify-content: center;
        align-items: center;
        transition: transform 0.2s ease;
    }

    .producto-card:hover:not(.producto-agotado) {
        transform: scale(1.05);
    }

    .producto-card img {
        width: 100%;
        height: auto;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .producto-card form,
    .producto-card button {
        width: 100%;
    }

    .producto-agotado {
        opacity: 0.7;
    }

    /* Responsive */
    @media (min-width: 768px) {
        .productos-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
    }

    @media (min-width: 1200px) {
        .productos-grid {
            grid-template-columns: repeat(8, 1fr);
            gap: 1.25rem;
        }
    }
    </style>
    @endpush