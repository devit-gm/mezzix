@extends('layouts.app')

@section('content')
@php
    $modoOperacion = $ajustes->modo_operacion ?? 'fichas';
    $esAgenciaEventos = ($modoOperacion === 'agencia_eventos');
    $rutaPrefix = $esAgenciaEventos ? 'eventos.gestion' : 'fichas';
@endphp

<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo"><i class="bi bi-receipt"></i> {{ $esAgenciaEventos ? __('Editar evento') : __('Editar ficha') }}</div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="row justify-content-center align-items-center">
                        <div class="d-grid gap-2 d-md-flex justify-content-end col-sm-12 col-md-8 col-lg-12">
                            <button class="btn btn-lg btn-light border border-dark">{{number_format($ficha->precio,2)}} <i class="bi bi-currency-euro"></i></button>
                        </div>
                        <div class="col-12 col-md-12 col-lg-12">
                            <form id="editar-ficha" action="{{ route($rutaPrefix . '.update', ['uuid'=>$ficha->uuid]) }}" method="post" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @if ($errors->any())
                                <div class="custom-error-container" id="custom-error-container">
                                    <ul class="custom-error-list">
                                        @foreach ($errors->all() as $error)
                                        <li class="custom-error-item">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                <input type="hidden" name="user_id" value="{{ $ficha->user_id }}" />
                                
                                @if($esAgenciaEventos)
                                <!-- Modo Agencia de Eventos -->
                                <input type="hidden" name="tipo" value="4" />
                                
                                <div class="form-group mb-3 required">
                                    <label for="fecha" class="fw-bold form-label">{{ __('Fecha') }}:</label><br>
                                    <input type="date" id="fecha" name="fecha" value="{{ $fechaCambiada }}" @if($ficha->estado == 1) disabled @endif required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="hora" class="fw-bold">{{ __('Hora') }}:</label><br>
                                    <input type="time" id="hora" name="hora" value="{{ $ficha->hora }}" @if($ficha->estado == 1) disabled @endif>
                                </div>
                                
                                <div class="form-group mb-3 required">
                                    <label for="descripcion" class="fw-bold form-label">{{ __('Nombre del evento') }}</label>
                                    <input type="text" class="form-control" id="descripcion" name="descripcion" value="{{ $ficha->descripcion }}" @if($ficha->estado == 1) disabled @endif required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="descripcion_evento" class="fw-bold form-label">{{ __('Descripción del evento') }}</label>
                                    <textarea class="form-control" id="descripcion_evento" name="descripcion_evento" rows="8" @if($ficha->estado == 1) disabled @endif>{{ $ficha->descripcion_evento }}</textarea>
                                    <small class="form-text text-muted">{{ __('Máximo 65,000 caracteres') }}</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="ubicacion_evento" class="fw-bold form-label">{{ __('Ubicación') }}</label>
                                    <input type="text" class="form-control" id="ubicacion_evento" name="ubicacion_evento" value="{{ $ficha->ubicacion_evento }}" maxlength="255" placeholder="{{ __('Ej: Salón Principal, Calle Mayor 123, etc.') }}" @if($ficha->estado == 1) disabled @endif>
                                    <small class="form-text text-muted">{{ __('Dónde se realizará el evento') }}</small>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="foto_evento" class="fw-bold form-label">{{ __('Foto del evento') }}</label>
                                    @if($ficha->foto_evento)
                                    <div class="mb-2">
                                        <strong class="d-block mb-2">{{ __('Imagen actual') }}</strong>
                                        <img src="{{ asset('storage/' . $ficha->foto_evento) }}" alt="Foto evento" class="img-thumbnail" style="max-height: 150px;">
                                    </div>
                                    @endif
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="file-name-foto" readonly placeholder="{{ __('Ningún archivo seleccionado') }}" @if($ficha->estado == 1) disabled @endif>
                                        <label for="foto_evento" class="btn btn-outline-secondary" style="cursor: pointer;" @if($ficha->estado == 1) onclick="return false;" @endif>
                                            <i class="bi bi-upload"></i> 
                                        </label>
                                        <button type="button" class="btn btn-outline-danger" id="clear-foto" style="display: none;" onclick="clearImageSelection('foto_evento', 'file-name-foto', 'preview-foto')" @if($ficha->estado == 1) disabled @endif>
                                            <i class="bi bi-x"></i>
                                        </button>
                                        <input type="file" id="foto_evento" name="foto_evento" onchange="handleImageSelection(this, 'file-name-foto', 'preview-foto', 'clear-foto')" accept="image/jpeg,image/jpg,image/png,image/webp" style="display: none;" @if($ficha->estado == 1) disabled @endif />
                                    </div>
                                    <small class="form-text text-muted">{{ __('Formatos: JPG, PNG, WEBP. Máximo 2MB') }}</small>
                                    <div id="preview-foto" class="text-center mt-2" style="display: none;">
                                        <img src="" alt="Preview" class="img-thumbnail" style="max-height: 200px; max-width: 100%;">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="aforo_maximo" class="fw-bold form-label">{{ __('Aforo máximo') }}</label>
                                        <input type="number" class="form-control" id="aforo_maximo" name="aforo_maximo" min="1" value="{{ $ficha->aforo_maximo }}" @if($ficha->estado == 1) disabled @endif>
                                        <small class="form-text text-muted">{{ __('Deja vacío si no hay límite') }}</small>
                                    </div>
                                    <div class="col-md-6 form-group mb-3">
                                        <label for="inscritos_actuales" class="fw-bold form-label">{{ __('Inscritos actuales') }}</label>
                                        <input type="number" class="form-control" id="inscritos_actuales" value="{{ $ficha->inscritos_actuales }}" disabled>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="precio" class="fw-bold form-label">{{ __('Precio') }}</label>
                                    <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" value="{{ $ficha->precio }}" @if($ficha->estado == 1) disabled @endif>
                                </div>
                                
                                <div class="form-group mb-3 required">
                                    <label for="estado" class="fw-bold form-label">{{ __('Estado') }}</label>
                                    <select name="estado" id="estado" class="form-select form-select-sm" aria-label=".form-select-sm example" required @if($ficha->estado == 1) disabled @endif>
                                        <option value="0" @if($ficha->estado == '0') selected @endif>{{ __('Abierto') }}</option>
                                        <option value="1" @if($ficha->estado == '1') selected @endif>{{ __('Cerrado') }}</option>
                                    </select>
                                </div>
                                
                                <input type="hidden" name="invitados_grupo" value="0" />
                                
                                @else
                                <!-- Modo Fichas normal -->
                                <div class="form-group mb-3 required">
                                    <label for="tipo" class="fw-bold form-label">{{ __('Tipo') }}</label>
                                    <select name="tipo" id="tipo" class="form-select form-select-sm" aria-label=".form-select-sm example" required @if($ficha->estado == 1) disabled @endif>
                                        <option value="1" @if($ficha->tipo == 1) selected @endif>{{ __('Individual') }}</option>
                                        <option value="2" @if($ficha->tipo == 2) selected @endif>{{ __('Conjunta') }}</option>
                                        <option value="3" @if($ficha->tipo == 3) selected @endif>{{ __('Compra') }}</option>
                                        <option value="4" @if($ficha->tipo == 4) selected @endif>{{ __('Evento') }}</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="descripcion" class="fw-bold form-label">{{ __('Nombre') }}</label>
                                    <input type="text" class="form-control" id="descripcion" name="descripcion" value="{{ $ficha->descripcion }}" @if($ficha->estado == 1) disabled @endif>
                                </div>
                                
                                <div class="form-group mb-3 required">
                                    <label for="fecha" class="fw-bold form-label">{{ __('Fecha') }}:</label><br>
                                    <input type="date" id="fecha" name="fecha" value="{{ $fechaCambiada }}" @if($ficha->estado == 1) disabled @endif>
                                </div>
                                
                                <div class="form-group mb-3 required">
                                    <label for="estado" class="fw-bold form-label">{{ __('Estado') }}</label>
                                    <select name="estado" id="estado" class="form-select form-select-sm" aria-label=".form-select-sm example" required @if($ficha->estado == 1) disabled @endif>
                                        <option value="0" @if($ficha->estado == '0') selected @endif>{{ __('Abierta') }}</option>
                                        <option value="1" @if($ficha->estado == '1') selected @endif>{{ __('Cerrada') }}</option>
                                    </select>
                                </div>
                                
                                @if($ficha->tipo == 2)
                                <div class="form-group mb-3">
                                    <label for="invitados_grupo" class="fw-bold form-label">{{ __('Invitados grupo') }}</label>
                                    <input class="form-control" type="number" min="0" name="invitados_grupo" id="invitados_grupo" value="{{ $ficha->invitados_grupo }}">
                                </div>
                                @else
                                <input type="hidden" name="invitados_grupo" value="{{ $ficha->invitados_grupo }}" />
                                @endif

                                <div class="form-group mb-3">
                                    <label for="hora" class="fw-bold">{{ __('Hora') }}:</label><br>
                                    <input type="time" id="hora" name="hora" value="{{ $ficha->hora }}">
                                    <small class="form-text text-muted">{{ __('Sólo para la edición de eventos') }}</small>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="menu" class="fw-bold">{{ __('Menú') }}:</label><br>
                                    <input type="text" class="form-control" id="menu" name="menu" value="{{ $ficha->menu }}">
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="responsables" class="fw-bold">{{ __('Responsable/s') }}:</label><br>
                                    <input type="text" class="form-control" id="responsables" name="responsables" value="{{ $ficha->responsables }}">
                                </div>

                                <div class="form-group mb-3 required">
                                    <input type="hidden" id="precio" name="precio" value="{{ $ficha->precio }}" disabled>
                                </div>
                                @endif
                            </form>
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
                    <form action="{{ route('fichas.destroy', ['uuid'=>$ficha->uuid]) }}" method="post">
                        <div class="d-flex align-items-center justify-content-center">

                            <a class="btn btn-dark mx-1" href={{ route($rutaPrefix . '.index') }}><i class="bi bi-chevron-left"></i></a>
                            @if(!$esAgenciaEventos)
                            <a href="{{ route('fichas.familias', ['uuid'=>$ficha->uuid]) }}" title="{{ __('Ver contenido de la ficha') }}" class="btn btn-info mx-1 my-1"><i class="bi bi-cup-straw"></i></a>
                            @endif


                            @if($ficha->estado == 0)
                            <button type="button" class="btn btn-success mx-1" onclick="document.getElementById('editar-ficha').submit();"><i class="bi bi-floppy"></i></button>
                            @endif

                            @if ($ficha->borrable == 1)

                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger mx-1" onclick="return confirm('{{ __('¿Está seguro de eliminar la ficha?') }}');"><i class="bi bi-trash"></i></button>

                            @endif
                        </div>
                    </form>
                </div>
@endsection

@push('scripts')
<script>
function handleImageSelection(input, inputId, previewId, clearBtnId) {
    const fileNameInput = document.getElementById(inputId);
    const previewContainer = document.getElementById(previewId);
    const clearBtn = document.getElementById(clearBtnId);
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validar tipo de archivo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('{{ __('Por favor selecciona una imagen válida (JPG, PNG o WEBP)') }}');
            input.value = '';
            return;
        }
        
        // Validar tamaño (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('{{ __('La imagen no debe superar los 2MB') }}');
            input.value = '';
            return;
        }
        
        // Mostrar nombre del archivo
        fileNameInput.value = file.name;
        
        // Mostrar preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = previewContainer.querySelector('img');
            img.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        // Mostrar botón limpiar
        if (clearBtn) clearBtn.style.display = 'inline-block';
    }
}

function clearImageSelection(inputId, fileNameId, previewId) {
    const input = document.getElementById(inputId);
    const fileNameInput = document.getElementById(fileNameId);
    const previewContainer = document.getElementById(previewId);
    const clearBtn = document.getElementById('clear-foto');
    
    input.value = '';
    fileNameInput.value = '';
    previewContainer.style.display = 'none';
    if (clearBtn) clearBtn.style.display = 'none';
}
</script>
@endpush