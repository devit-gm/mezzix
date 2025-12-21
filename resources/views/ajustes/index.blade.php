@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo"><i class="bi bi-gear"></i> {{ __('Settings') }}</div>

                <div class="card-body overflow-auto flex-fill">
                    <div class="container-fluid">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-12 col-md-12 col-lg-12">
                                <form id="editar-ajustes" action="{{ route('ajustes.update') }}" method="post">
                                    @csrf
                                    @method('PUT')

                                    @if (session('success'))
                                    <div class="custom-success-container" id="custom-success-container">
                                        <ul class="custom-success-list">
                                            <li class="custom-success-item">{{ session('success') }}</li>
                                        </ul>
                                    </div>
                                    @endif
                                    @if ($errors->any())
                                    <div class="custom-error-container">
                                        <ul class="custom-error-list">
                                            @foreach ($errors->all() as $error)
                                            <li class="custom-error-item">{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @endif

                                    <!-- Configuración del Sistema -->
                                    <div class="mb-4">
                                        <h5 class="mb-3 d-flex align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseConfiguracion" aria-expanded="true" aria-controls="collapseConfiguracion">
                                            <i class="bi bi-toggles me-2"></i> {{ __('Configuración del Sistema') }}
                                            <i class="bi bi-chevron-down ms-auto"></i>
                                        </h5>
                                        <div class="collapse show" id="collapseConfiguracion">
                                            <div class="border-start border-3 border-danger ps-3">
                                                <div class="form-group mb-3 required">
                                                    <label for="modo_operacion" class="fw-bold form-label">{{ __('Modo de operación') }}:</label>
                                                    <select name="modo_operacion" id="modo_operacion" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="fichas" @if( $ajustes->modo_operacion == 'fichas' ) selected @endif>{{ __('Fichas de Eventos') }}</option>
                                                        <option value="mesas" @if( $ajustes->modo_operacion == 'mesas' ) selected @endif>{{ __('Mesas de Restaurante') }}</option>
                                                        <option value="agencia_eventos" @if( $ajustes->modo_operacion == 'agencia_eventos' ) selected @endif>{{ __('Agencia de Eventos') }}</option>
                                                    </select>
                                                    <small class="form-text text-muted">{{ __('Selecciona si trabajas con fichas de eventos o mesas de restaurante') }}</small>
                                                </div>

                                                @if($ajustes->modo_operacion == 'mesas')
                                                <div class="form-group mb-3 required" id="opcion_mostrar_usuarios">
                                                    <label for="mostrar_usuarios" class="fw-bold form-label">{{ __('Mostrar gestión de usuarios/invitados') }}:</label>
                                                    <select name="mostrar_usuarios" id="mostrar_usuarios" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( $ajustes->mostrar_usuarios == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( $ajustes->mostrar_usuarios == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                    <small class="form-text text-muted">{{ __('Desactiva para modo restaurante') }}</small>
                                                </div>
                                                @endif

                                                @if($ajustes->modo_operacion == 'mesas')
                                                <div class="form-group mb-3 required" id="opcion_mostrar_gastos">
                                                    <label for="mostrar_gastos" class="fw-bold form-label">{{ __('Mostrar gestión de gastos') }}:</label>
                                                    <select name="mostrar_gastos" id="mostrar_gastos" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( $ajustes->mostrar_gastos == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( $ajustes->mostrar_gastos == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                    <small class="form-text text-muted">{{ __('Desactiva para modo restaurante') }}</small>
                                                </div>
                                                @endif

                                                @if($ajustes->modo_operacion == 'mesas')
                                                <div class="form-group mb-3 required" id="opcion_mostrar_compras">
                                                    <label for="mostrar_compras" class="fw-bold form-label">{{ __('Mostrar gestión de compras') }}:</label>
                                                    <select name="mostrar_compras" id="mostrar_compras" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( $ajustes->mostrar_compras == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( $ajustes->mostrar_compras == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                    <small class="form-text text-muted">{{ __('Desactiva para modo restaurante') }}</small>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Configuración de Invitados -->
                                    @if($ajustes->modo_operacion == 'fichas')
                                    <div class="mb-4" id="seccion_invitados">
                                        <h5 class="mb-3 d-flex align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseInvitados" aria-expanded="false" aria-controls="collapseInvitados">
                                            <i class="bi bi-people me-2"></i> {{ __('Configuración de Invitados') }}
                                            <i class="bi bi-chevron-down ms-auto"></i>
                                        </h5>
                                        <div class="collapse" id="collapseInvitados">
                                            <div class="border-start border-3 border-primary ps-3">
                                                <div class="form-group required mb-3">
                                                    <label for="precio_invitado" class="fw-bold form-label">{{ __('Cargo por invitado') }}:</label>
                                                    <input type="number" step="0.05" min='0.00' value='{{ $ajustes->precio_invitado }}' placeholder='0.00' class="form-control" id="precio_invitado" name="precio_invitado" required>
                                                </div>
                                                <div class="form-group required mb-3">
                                                    <label for="max_invitados_cobrar" class="fw-bold form-label">{{ __('Máximo de invitados con cargo') }}:</label>
                                                    <input type="number" min='0' placeholder='0' value="{{ $ajustes->max_invitados_cobrar }}" class="form-control" id="max_invitados_cobrar" name="max_invitados_cobrar" required>
                                                </div>

                                                <div class="form-group mb-3 required">
                                                    <label for="primer_invitado_gratis" class="fw-bold form-label">{{ __('Primer invitado sin cargo') }}:</label>
                                                    <select name="primer_invitado_gratis" id="primer_invitado_gratis" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( $ajustes->primer_invitado_gratis == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( $ajustes->primer_invitado_gratis == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                </div>

                                                <div class="form-group mb-3 required">
                                                    <label for="activar_invitados_grupo" class="fw-bold form-label">{{ __('Activar invitados de grupo') }}:</label>
                                                    <select name="activar_invitados_grupo" id="activar_invitados_grupo" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( $ajustes->activar_invitados_grupo == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( $ajustes->activar_invitados_grupo == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Configuración de Productos y Stock -->
                                    @if($ajustes->modo_operacion != 'agencia_eventos')
                                    <div class="mb-4">
                                        <h5 class="mb-3 d-flex align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseProductos" aria-expanded="false" aria-controls="collapseProductos">
                                            <i class="bi bi-box-seam me-2"></i> {{ __('Configuración de Productos y Stock') }}
                                            <i class="bi bi-chevron-down ms-auto"></i>
                                        </h5>
                                        <div class="collapse" id="collapseProductos">
                                            <div class="border-start border-3 border-success ps-3">
                                                <div class="form-group mb-3 required">
                                                    <label for="permitir_comprar_sin_stock" class="fw-bold form-label">{{ __('Permitir comprar sin stock') }}:</label>
                                                    <select name="permitir_comprar_sin_stock" id="permitir_comprar_sin_stock" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( $ajustes->permitir_comprar_sin_stock == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( $ajustes->permitir_comprar_sin_stock == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                </div>

                                                <div class="form-group mb-3 required">
                                                    <label for="stock_minimo" class="fw-bold form-label">{{ __('Stock mínimo para alertas') }}:</label>
                                                    <input type="number" min="0" placeholder="5" value="{{ $ajustes->stock_minimo ?? 5 }}" class="form-control" id="stock_minimo" name="stock_minimo" required>
                                                    <small class="form-text text-muted">{{ __('Se enviará notificación cuando el stock llegue a este valor o menos') }}</small>
                                                </div>

                                                <div class="form-group mb-3 required">
                                                    <label for="notificar_stock_bajo" class="fw-bold form-label">{{ __('Notificar stock bajo') }}:</label>
                                                    <select name="notificar_stock_bajo" id="notificar_stock_bajo" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( isset($ajustes->notificar_stock_bajo) && $ajustes->notificar_stock_bajo == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( !isset($ajustes->notificar_stock_bajo) || $ajustes->notificar_stock_bajo == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                    <small class="form-text text-muted">{{ __('Los usuarios con rol menor a USUARIO_MESAS recibirán emails y notificaciones push') }}</small>
                                                </div>

@if(request()->secure() || str_contains(request()->getHost(), '127.0.0.1'))                                                <div class="form-group mb-3 required">
                                                    <label for="permitir_lectura_codigo_barras" class="fw-bold form-label">{{ __('Permitir lectura de código de barras') }}:</label>
                                                    <select name="permitir_lectura_codigo_barras" id="permitir_lectura_codigo_barras" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( $ajustes->permitir_lectura_codigo_barras == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( $ajustes->permitir_lectura_codigo_barras == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Configuración de Facturación y Eventos -->
                                    @if($ajustes->modo_operacion == 'fichas')
                                    <div class="mb-4" id="seccion_facturacion">
                                        <h5 class="mb-3 d-flex align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseFacturacion" aria-expanded="false" aria-controls="collapseFacturacion">
                                            <i class="bi bi-receipt me-2"></i> {{ __('Configuración de Facturación y Eventos') }}
                                            <i class="bi bi-chevron-down ms-auto"></i>
                                        </h5>
                                        <div class="collapse" id="collapseFacturacion">
                                            <div class="border-start border-3 border-warning ps-3">
                                                <div class="form-group mb-3 required">
                                                    <label for="facturar_ficha_automaticamente" class="fw-bold form-label">{{ __('Facturar ficha automáticamente') }}:</label>
                                                    <select name="facturar_ficha_automaticamente" id="facturar_ficha_automaticamente" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( $ajustes->facturar_ficha_automaticamente == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( $ajustes->facturar_ficha_automaticamente == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                </div>

                                                <div class="form-group mb-3 required">
                                                    <label for="limite_inscripcion_dias_eventos" class="fw-bold form-label">{{ __('Límite de días anteriores al evento para apuntarse') }}:</label>
                                                    <input type="number" min='0' placeholder='0' value="{{ $ajustes->limite_inscripcion_dias_eventos }}" class="form-control" id="limite_inscripcion_dias_eventos" name="limite_inscripcion_dias_eventos" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif


                                    <!-- Configuración de Recordatorios de Reservas y Eventos -->
                                    <div class="mb-4">
                                        <h5 class="mb-3 d-flex align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseRecordatorios" aria-expanded="false" aria-controls="collapseRecordatorios">
                                            <i class="bi bi-bell me-2"></i> {{ __('Configuración de Recordatorios de Reservas y Eventos') }}
                                            <i class="bi bi-chevron-down ms-auto"></i>
                                        </h5>
                                        <div class="collapse" id="collapseRecordatorios">
                                            <div class="border-start border-3 border-info ps-3">
                                                <div class="form-group mb-3 required">
                                                    <label for="recordatorio_reservas_dias" class="fw-bold form-label">{{ __('Días de antelación para recordatorio de reservas') }}:</label>
                                                    <input type="number" min="1" step="1" placeholder="1" value="{{ $ajustes->recordatorio_reservas_dias ?? 1 }}" class="form-control" id="recordatorio_reservas_dias" name="recordatorio_reservas_dias" required>
                                                    <small class="form-text text-muted">{{ __('Se enviará un recordatorio a los usuarios que tengan reservas en el/los día(s) siguiente(s)') }}</small>
                                                </div>
                                                @if($ajustes->modo_operacion !== 'mesas')
                                                <div class="form-group mb-3 required">
                                                    <label for="limite_inscripcion_dias_eventos" class="fw-bold form-label">{{ __('Días de antelación para recordatorio de eventos') }}:</label>
                                                    <input type="number" min="1" step="1" placeholder="1" value="{{ $ajustes->limite_inscripcion_dias_eventos ?? 1 }}" class="form-control" id="limite_inscripcion_dias_eventos" name="limite_inscripcion_dias_eventos" required>
                                                    <small class="form-text text-muted">{{ __('Se enviará un recordatorio a todos los usuarios cuando falten estos días para el cierre de inscripción a un evento') }}</small>
                                                </div>
                                                @endif
                                                <div class="form-group mb-3 required">
                                                    <label for="recordatorio_reservas_email" class="fw-bold form-label">{{ __('Enviar recordatorio por email') }}:</label>
                                                    <select name="recordatorio_reservas_email" id="recordatorio_reservas_email" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( isset($ajustes->recordatorio_reservas_email) && $ajustes->recordatorio_reservas_email == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( !isset($ajustes->recordatorio_reservas_email) || $ajustes->recordatorio_reservas_email == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                    <small class="form-text text-muted">{{ __('El email se enviará tanto para reservas como para eventos') }}</small>
                                                </div>
                                                <div class="form-group mb-3 required">
                                                    <label for="recordatorio_reservas_push" class="fw-bold form-label">{{ __('Enviar recordatorio por notificación push') }}:</label>
                                                    <select name="recordatorio_reservas_push" id="recordatorio_reservas_push" class="form-select form-select-sm" aria-label=".form-select-sm example" required>
                                                        <option value="0" @if( isset($ajustes->recordatorio_reservas_push) && $ajustes->recordatorio_reservas_push == 0 ) selected @endif>{{ __('No') }}</option>
                                                        <option value="1" @if( !isset($ajustes->recordatorio_reservas_push) || $ajustes->recordatorio_reservas_push == 1 ) selected @endif>{{ __('Sí') }}</option>
                                                    </select>
                                                    <small class="form-text text-muted">{{ __('La notificación push se enviará tanto para reservas como para eventos') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </form>
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
                            <button type="button" onclick="document.getElementById('editar-ajustes').submit();" class="btn btn-success mx-1"><i class="bi bi-floppy"></i></button>
                        </div>
                    </form>
                </div>
		@endsection

