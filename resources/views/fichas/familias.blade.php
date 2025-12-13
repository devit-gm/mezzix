@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-journal-text"></i> {{ $ajustes->modo_operacion === 'mesas' ? $ficha->descripcion : __("Ficha") . ' - '  . __("Families") }}</span>
                   
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

    @foreach($familias as $familia)
        <div class="producto-card">
            <a href="{{ fichaRoute('productos', [$ficha->uuid, $familia->uuid]) }}">
                <img src="{{ cachedImage($familia->imagen) }}" 
                     class="img-fluid rounded" 
                     alt="{{ $familia->nombre }}"
                     loading="lazy"
                     decoding="async">
            </a>
        </div>
    @endforeach
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
            <a class="btn btn-dark mx-1" href="{{ fichaRoute('index') }}"><i class="bi bi-chevron-left"></i></a>
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

.producto-card:hover {
    transform: scale(1.05);
}

.producto-card img {
    width: 100%;
    height: auto;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.producto-card a {
    display: block;
    width: 100%;
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