@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-10 col-lg-8 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo">
                    <i class="bi bi-plus-circle"></i> {{ __('Nuevo Proveedor') }}
                </div>

                <div class="card-body overflow-auto flex-fill">
                    <form method="POST" action="{{ route('proveedores.store') }}" id="formProveedor">
                        @csrf

                        <!-- Información Básica -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Información Básica</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="nombre" class="form-label">Nombre del Proveedor <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                               id="nombre" name="nombre" value="{{ old('nombre') }}" required autofocus>
                                        @error('nombre')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="cif" class="form-label">CIF/NIF</label>
                                        <input type="text" class="form-control @error('cif') is-invalid @enderror" 
                                               id="cif" name="cif" value="{{ old('cif') }}">
                                        @error('cif')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               id="email" name="email" value="{{ old('email') }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="text" class="form-control @error('telefono') is-invalid @enderror" 
                                               id="telefono" name="telefono" value="{{ old('telefono') }}">
                                        @error('telefono')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="contacto_principal" class="form-label">Persona de Contacto</label>
                                    <input type="text" class="form-control @error('contacto_principal') is-invalid @enderror" 
                                           id="contacto_principal" name="contacto_principal" value="{{ old('contacto_principal') }}">
                                    @error('contacto_principal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Dirección -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Dirección</strong>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <input type="text" class="form-control @error('direccion') is-invalid @enderror" 
                                           id="direccion" name="direccion" value="{{ old('direccion') }}">
                                    @error('direccion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="ciudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control @error('ciudad') is-invalid @enderror" 
                                               id="ciudad" name="ciudad" value="{{ old('ciudad') }}">
                                        @error('ciudad')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="codigo_postal" class="form-label">Código Postal</label>
                                        <input type="text" class="form-control @error('codigo_postal') is-invalid @enderror" 
                                               id="codigo_postal" name="codigo_postal" value="{{ old('codigo_postal') }}">
                                        @error('codigo_postal')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="pais" class="form-label">País</label>
                                        <input type="text" class="form-control @error('pais') is-invalid @enderror" 
                                               id="pais" name="pais" value="{{ old('pais', 'España') }}">
                                        @error('pais')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Condiciones Comerciales -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Condiciones Comerciales</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="dias_pago" class="form-label">Días de Pago</label>
                                        <input type="number" class="form-control @error('dias_pago') is-invalid @enderror" 
                                               id="dias_pago" name="dias_pago" value="{{ old('dias_pago', 30) }}" min="0" max="365">
                                        <small class="text-muted">Plazo de pago en días</small>
                                        @error('dias_pago')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="descuento_general" class="form-label">Descuento General (%)</label>
                                        <input type="number" class="form-control @error('descuento_general') is-invalid @enderror" 
                                               id="descuento_general" name="descuento_general" value="{{ old('descuento_general', 0) }}" 
                                               min="0" max="100" step="0.01">
                                        @error('descuento_general')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="condiciones_pago" class="form-label">Condiciones de Pago</label>
                                    <textarea class="form-control @error('condiciones_pago') is-invalid @enderror" 
                                              id="condiciones_pago" name="condiciones_pago" rows="2">{{ old('condiciones_pago') }}</textarea>
                                    <small class="text-muted">Ejemplo: 30 días fecha factura, transferencia bancaria</small>
                                    @error('condiciones_pago')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="cuenta_bancaria" class="form-label">Cuenta Bancaria (IBAN)</label>
                                    <input type="text" class="form-control @error('cuenta_bancaria') is-invalid @enderror" 
                                           id="cuenta_bancaria" name="cuenta_bancaria" value="{{ old('cuenta_bancaria') }}" 
                                           maxlength="34" placeholder="ES00 0000 0000 0000 0000 0000">
                                    @error('cuenta_bancaria')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Notas y Estado -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>Información Adicional</strong>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="notas" class="form-label">Notas</label>
                                    <textarea class="form-control @error('notas') is-invalid @enderror" 
                                              id="notas" name="notas" rows="3">{{ old('notas') }}</textarea>
                                    @error('notas')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                           value="1" {{ old('activo', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="activo">
                                        Proveedor activo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
<div class="card-footer">
    <div class="d-flex align-items-center justify-content-center gap-2">
        <a href="{{ route('proveedores.index') }}" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-chevron-left"></i>
        </a>
        <button type="submit" form="formProveedor" class="btn btn-primary fondo-rojo borde-rojo">
            <i class="bi bi-floppy"></i>
        </button>
    </div>
</div>
@endsection
