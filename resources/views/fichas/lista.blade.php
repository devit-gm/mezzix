@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">

                <div class="card-header fondo-rojo d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-journal-text"></i> {{ $ajustes->modo_operacion === 'mesas' ? $ficha->descripcion :  __("FICHA - CONSUMO") }}</span>
                    
                        <span class="badge bg-light text-dark fs-5">{{ number_format($ficha->precio,2) }} <i class="bi bi-currency-euro"></i></span>
                   
                </div>

                <div class="card-body overflow-auto flex-fill">
                   
                    <div class="container-fluid p-0 @if($ajustes->modo_operacion !== 'mesas') @endif">
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
                                
                               <style>
    /* ---- ESTILO MINIMALISTA TABLA FICHA ---- */

    .tabla-ficha {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 6px;
        font-size: 0.95rem;
    }

    .tabla-ficha thead th {
        background: #f7f7f7;
        padding: 12px;
        font-weight: 600;
        border-bottom: 2px solid #e5e5e5;
    }

    .tabla-ficha tbody tr {
        background: #ffffff;
        border-radius: 8px;
        transition: background 0.2s ease;
        cursor: pointer;
        font-size:18px;
    }

    .tabla-ficha tbody tr:hover {
        background: #f4f7ff;
    }

    .tabla-ficha td {
        padding: 10px;
        vertical-align: middle;
        border-top: 1px solid #efefef;
    }

    .tabla-ficha td:first-child {
        border-left: 1px solid #efefef;
        border-radius: 8px 0 0 8px;
    }

    .tabla-ficha td:last-child {
        border-right: 1px solid #efefef;
        border-radius: 0 8px 8px 0;
    }

    /* ---- BOTÓN BORRAR ---- */
    .btn-borrar-min {
            font-size: 18px;
    }

    .btn-borrar-min:hover {
        background: #d93636;
    }
</style>



<table class="tabla-ficha table-responsive">
    <thead>
        <tr>
            <th>{{ __('Producto') }}</th>
            <th class="text-center" width="100">{{ __('Total') }}</th>
            @if($ficha->estado == 0 || (isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas'))
            <th class="text-center"></th>
            @endif
        </tr>
    </thead>

    <tbody>
        @php
            if(isset($ajustes->modo_operacion) && $ajustes->modo_operacion != 'mesas'){
            $clickable = ($ficha->estado == 0) ? 'clickable-row' : '';
            } else {
            $clickable = '';
            }
        @endphp

        @foreach ($productosFicha as $componente)
        @php
            $estado = $componente->estado ?? '';
            $badge = '';
            $rowClass = $clickable;
            if ($estado === 'pendiente') {
                $badge = '<i class="bi bi-hourglass-split ms-2"></i>';
                $rowClass .= ' border-warning';
            } elseif ($estado === 'preparado') {
                $badge = '<i class="bi bi-check-circle-fill ms-2"></i>';
                $rowClass .= ' border-success';
            }
            
            // En modo mesas, usar UUID único de ficha_producto, sino usar id_producto
            $uuidBorrar = (isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas') 
                ? $componente->uuid 
                : $componente->id_producto;
        @endphp
        <tr class="{!! $rowClass !!}"
            style="min-height: 90px;"
            data-uuid="{{ $ficha->uuid }}"
            data-uuid2="{{ $uuidBorrar }}"
            data-borrable="{{ $componente->borrable }}"
            data-textoborrar="{{ __('¿Está seguro de eliminar el artículo de la lista?') }}"
            data-hrefborrar="{{ fichaRoute('destroylista', ['uuid' => $ficha->uuid, 'uuid2' => $uuidBorrar]) }}">
            <td>
                {!! $badge !!} {{ $componente->cantidad }}x {{ $componente->producto->nombre }} 
            </td>

            <td class="text-center">
                {{ number_format($componente->precio,2) }}
                <i class="bi bi-currency-euro"></i>
            </td>

            @if($ficha->estado == 0 || (isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas'))
            <td class="text-center d-flex justify-content-center align-items-center gap-1" style="vertical-align: middle;">
                @if(isset($ajustes->modo_operacion) && $ajustes->modo_operacion != 'mesas')
                <form class="form-cantidad-accion d-inline" method="POST" style="display:inline;">
                    @csrf
                    @method('PUT')
                    <button type="button" class="btn btn-sm btn-borrar-min btn-danger" title="Restar cantidad" onclick="enviarCantidad(this, -1)">
                        <i class="bi bi-dash"></i>
                    </button>
                </form>
                @endif
                <button type="button" class="btn btn-sm btn-borrar-min btn-danger" onclick="triggerParentClick(event,this);" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
                @if(isset($ajustes->modo_operacion) && $ajustes->modo_operacion != 'mesas')
                <form class="form-cantidad-accion d-inline" method="POST" style="display:inline;">
                    @csrf
                    @method('PUT')
                    <button type="button" class="btn btn-sm btn-borrar-min btn-danger" title="Sumar cantidad" onclick="enviarCantidad(this, 1)">
                        <i class="bi bi-plus"></i>
                    </button>
                </form>
                @endif
            </td>
            @endif
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
</div>
@endsection
@section('footer')

<div class="card-footer">
    <form>
        <div class="d-flex align-items-center justify-content-center">
            @php
            if($ficha->estado == 0 || (isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas')){
            $ruta = fichaRoute('familias', ['uuid'=>$ficha->uuid]);
            }else{
            $ruta = fichaRoute('index');
            }
            @endphp
            <a class="btn btn-dark mx-1" href="{{ $ruta }}"><i class="bi bi-chevron-left"></i></a>
            @if(isset($ajustes) && $ajustes->permitir_lectura_codigo_barras == 1 && (request()->secure() || str_contains(request()->getHost(), '127.0.0.1')))
            <button type="button" id="btn-open-scanner" class="btn btn-primary mx-1">
                <i class="bi bi-upc-scan"></i>
            </button>
            @endif
            @if(!$productosFicha->isEmpty() && isset($ajustes->modo_operacion) && $ajustes->modo_operacion == 'mesas')
                <a href="{{ route('fichas.enviarCocina', ['uuid' => $ficha->uuid]) }}" class="btn btn-danger mx-1">
                    <i class="bi bi-fire"></i>
                </a>
            @endif
            @if(!isset($ajustes->modo_operacion) || $ajustes->modo_operacion == 'fichas' || (isset($ajustes->mostrar_usuarios) && $ajustes->mostrar_usuarios == 1))
                <a class="btn btn-dark mx-1" href="{{ fichaRoute('usuarios', ['uuid'=>$ficha->uuid]) }}"><i class="bi bi-chevron-right"></i></a>
            @else
                <a class="btn btn-dark mx-1" href="{{ fichaRoute('servicios', ['uuid'=>$ficha->uuid]) }}"><i class="bi bi-chevron-right"></i></a>
            @endif
        </div>
    </form>
</div>

<!-- Modal Scanner Código de Barras -->
<div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header fondo-rojo">
                <h5 class="modal-title text-white">
                    <i class="bi bi-upc-scan"></i> {{ __('Escanear Código de Barras') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="scanner-container" style="position: relative; width: 100%; height: 200px; background: #000; overflow: hidden;">
                    <div id="barcode-scanner" style="position: relative; width: 100%; height: 100%;">
                        <video style="width: 100%; height: 100%; object-fit: cover;"></video>
                        <canvas class="drawingBuffer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></canvas>
                    </div>
                    <div id="scanner-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: 10;">
                        <!-- Línea roja de guía -->
                        <div style="position: absolute; top: 50%; left: 10%; right: 10%; height: 3px; background: red; transform: translateY(-50%); box-shadow: 0 0 10px rgba(255,0,0,0.8);"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" id="btn-stop-scanner">
                    <i class="bi bi-stop-circle"></i>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Asegurar que el backdrop y modal estén correctamente ordenados */
    .modal-backdrop.show {
        z-index: -1 !important;
    }
    
    #scannerModal.show {
        z-index: 1060 !important;
    }
    
    #scannerModal .modal-dialog {
        z-index: 1061 !important;
        position: relative;
    }

    /* Botones + y - minimalistas */
    .btn-sumar-cantidad, .btn-restar-cantidad {
        font-size: 1.2rem;
        min-width: 32px;
        min-height: 32px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
    }
    .btn-sumar-cantidad:hover, .btn-restar-cantidad:hover {
        background: #e5e5e5;
    }
</style>
@endpush

@push('scripts')
<!-- Cargar Quagga desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2@1.8.4/dist/quagga.min.js"></script>

<script>
function enviarCantidad(btn, cantidad) {
    var tr = btn.closest('tr');
    var uuid = tr.getAttribute('data-uuid');
    var uuid2 = tr.getAttribute('data-uuid2'); // En modo mesas será el UUID de ficha_producto
    var form = btn.closest('form');
    var action = '';
    if(window.location.pathname.includes('/mesas/')) {
        action = '/mesas/' + uuid + '/lista/' + uuid2 + '/' + cantidad;
    } else {
        action = '/fichas/' + uuid + '/lista/' + uuid2 + '/' + cantidad;
    }
    form.action = action;
    form.submit();
}
</script>

<script>
// Objeto BarcodeScanner
window.BarcodeScanner = {
    isScanning: false,
    onDetected: null,
    videoStream: null,
    
    // Iniciar el scanner
    init(videoElementId, onDetectedCallback) {
        this.onDetected = onDetectedCallback;
        this.detectionCache = {}; // Cache para validar lecturas múltiples
        
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#' + videoElementId),
                constraints: {
                    width: 640,
                    height: 480,
                    facingMode: "environment"
                },
                area: {
                    top: "5%",
                    right: "5%",
                    left: "5%",
                    bottom: "5%"
                },
                singleChannel: false
            },
            locator: {
                patchSize: "medium",
                halfSample: true
            },
            decoder: {
                readers: [
                    "ean_reader",
                    "ean_8_reader",
                    "code_128_reader",
                    "code_39_reader",
                    "upc_reader",
                    "upc_e_reader"
                ],
                debug: {
                    drawBoundingBox: true,
                    showFrequency: false,
                    drawScanline: true,
                    showPattern: false
                },
                multiple: false
            },
            locate: true,
            numOfWorkers: 2,
            frequency: 10
        }, (err) => {
            if (err) {
                console.error('Error al inicializar Quagga:', err);
                alert('Error al acceder a la cámara: ' + err.message);
                return;
            }
            console.log("Quagga inicializado correctamente");
            this.isScanning = true;
            Quagga.start();
            
            // Guardar referencia al stream de video
            const videoElement = document.querySelector('#' + videoElementId + ' video');
            if (videoElement && videoElement.srcObject) {
                this.videoStream = videoElement.srcObject;
            }
        });
        
        // Evento cuando se detecta un código
        Quagga.onDetected(this.handleDetection.bind(this));
        
        // Dibujar rectángulo alrededor del código detectado
        Quagga.onProcessed((result) => {
            const drawingCtx = Quagga.canvas.ctx.overlay;
            const drawingCanvas = Quagga.canvas.dom.overlay;

            if (result) {
                if (result.boxes) {
                    drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                    result.boxes.filter(box => box !== result.box).forEach(box => {
                        Quagga.ImageDebug.drawPath(box, { x: 0, y: 1 }, drawingCtx, { color: "green", lineWidth: 2 });
                    });
                }

                if (result.box) {
                    Quagga.ImageDebug.drawPath(result.box, { x: 0, y: 1 }, drawingCtx, { color: "#00F", lineWidth: 2 });
                }

                if (result.codeResult && result.codeResult.code) {
                    Quagga.ImageDebug.drawPath(result.line, { x: 'x', y: 'y' }, drawingCtx, { color: 'red', lineWidth: 3 });
                }
            }
        });
    },
    
    // Manejar detección con validación de calidad
    handleDetection(result) {
        // Validar calidad de la lectura
        if (!result || !result.codeResult) {
            return;
        }
        
        const code = result.codeResult.code;
        const format = result.codeResult.format;
        
        // Filtrar lecturas con baja confianza
        const errors = result.codeResult.decodedCodes
            .filter(_ => _.error !== undefined)
            .map(_ => _.error);
        const avgError = errors.reduce((a, b) => a + b, 0) / errors.length;
        
        console.log('Código detectado:', code, 'Formato:', format, 'Error promedio:', avgError);
        
        // Solo aceptar lecturas con buena calidad (error < 0.1)
        if (avgError > 0.1) {
            console.log('Lectura rechazada por baja calidad');
            return;
        }
        
        // Validar que el código tenga longitud razonable
        if (code.length < 8) {
            console.log('Código muy corto, rechazado');
            return;
        }
        
        // Sistema de validación: requiere 2 lecturas consecutivas del mismo código
        if (!this.detectionCache[code]) {
            this.detectionCache[code] = 1;
            console.log('Primera lectura de:', code, '- esperando confirmación');
            return;
        } else {
            this.detectionCache[code]++;
            if (this.detectionCache[code] >= 2) {
                console.log('Código confirmado:', code);
                if (this.onDetected && typeof this.onDetected === 'function') {
                    this.onDetected(code, format);
                }
                this.stop();
            }
        }
    },
    
    // Detener el scanner y la cámara
    stop() {
        if (this.isScanning) {
            try {
                // Remover todos los listeners de Quagga antes de detener
                Quagga.offDetected();
                Quagga.offProcessed();
                
                Quagga.stop();
                
                // Detener todos los tracks del stream de video
                if (this.videoStream) {
                    this.videoStream.getTracks().forEach(track => {
                        track.stop();
                        console.log('Track de video detenido:', track.kind);
                    });
                    this.videoStream = null;
                }
                
                // Limpiar el video element
                const videoElements = document.querySelectorAll('#barcode-scanner video');
                videoElements.forEach(video => {
                    if (video.srcObject) {
                        video.srcObject.getTracks().forEach(track => track.stop());
                        video.srcObject = null;
                    }
                    video.src = '';
                });
                
                this.isScanning = false;
                this.onDetected = null;
                console.log('Scanner y cámara detenidos');
            } catch (error) {
                console.error('Error al detener scanner:', error);
            }
        }
    },
    
    // Pausar temporalmente
    pause() {
        if (this.isScanning) {
            Quagga.pause();
        }
    },
    
    // Reanudar
    resume() {
        if (this.isScanning) {
            Quagga.start();
        }
    }
};

let detectedBarcode = null;
let scannerModal = null;
let scannerInitialized = false;

// Inicializar modal cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar mensaje de éxito si viene en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const successMessage = urlParams.get('success');
    if (successMessage) {
        // Crear elemento de mensaje de éxito
        const successContainer = document.createElement('div');
        successContainer.className = 'custom-success-container';
        successContainer.id = 'custom-success-container-scanner';
        successContainer.innerHTML = `
            <ul class="custom-success-list">
                <li class="custom-success-item">${successMessage}</li>
            </ul>
        `;
        
        // Insertar después de los errores/success existentes
        const cardBody = document.querySelector('.card-body');
        const firstChild = cardBody.querySelector('.container-fluid');
        if (firstChild) {
            cardBody.insertBefore(successContainer, firstChild);
        } else {
            cardBody.insertBefore(successContainer, cardBody.firstChild);
        }
        
        // Limpiar URL sin recargar página
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: newUrl}, '', newUrl);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(function() {
            successContainer.style.transition = 'opacity 0.5s';
            successContainer.style.opacity = '0';
            setTimeout(function() {
                successContainer.remove();
            }, 500);
        }, 5000);
    }
    
    const modalElement = document.getElementById('scannerModal');
    const btnOpenScanner = document.getElementById('btn-open-scanner');
    const btnStopScanner = document.getElementById('btn-stop-scanner');
    
    // Si el botón no existe (ajuste deshabilitado), no hacer nada
    if (!btnOpenScanner) {
        return;
    }
    
    if (!modalElement) {
        console.error('Modal del scanner no encontrado');
        return;
    }
    
    // Botón para abrir el scanner
    btnOpenScanner.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Detener cualquier instancia previa antes de abrir
        if (scannerInitialized) {
            console.log('Deteniendo scanner previo antes de abrir...');
            BarcodeScanner.stop();
            scannerInitialized = false;
        }
        
        // Limpiar cualquier backdrop existente
        const existingBackdrops = document.querySelectorAll('.modal-backdrop');
        existingBackdrops.forEach(b => b.remove());
        
        // Usar Bootstrap Modal
        const bsModal = window.bootstrap?.Modal;
        
        if (bsModal) {
            // Bootstrap 5 está disponible
            if (!scannerModal) {
                scannerModal = new bsModal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
            }
            scannerModal.show();
        } else {
            // Fallback: usar data attributes
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            modalElement.style.zIndex = '1055';
            document.body.classList.add('modal-open');
            
            // Crear backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'scanner-backdrop';
            backdrop.style.zIndex = '1050';
            document.body.appendChild(backdrop);
            
            // Iniciar scanner manualmente si no hay evento Bootstrap
            initScanner();
        }
    });
    
    // Iniciar scanner cuando el modal esté visible
    function initScanner() {
        // Evitar inicializaciones múltiples
        if (scannerInitialized) {
            console.log('Scanner ya inicializado, omitiendo...');
            return;
        }
        
        setTimeout(function() {
            console.log('Iniciando scanner...');
            scannerInitialized = true;
            BarcodeScanner.init('barcode-scanner', function(code, format) {
                detectedBarcode = code;
                
                // Reproducir sonido de éxito (beep del dispositivo)
                if (navigator.vibrate) {
                    navigator.vibrate(200); // Vibración en móviles
                }
                
                // Buscar automáticamente el producto
                buscarProducto(code);
            });
        }, 500);
    }
    
    // Iniciar scanner cuando el modal esté completamente visible
    modalElement.addEventListener('shown.bs.modal', initScanner);
    
    // Detener scanner al cerrar modal
    modalElement.addEventListener('hidden.bs.modal', function() {
        console.log('Modal cerrado, deteniendo scanner...');
        if (scannerInitialized) {
            BarcodeScanner.stop();
            scannerInitialized = false;
        }
        detectedBarcode = null;
    });
    
    // Botón cerrar (X) del modal
    const btnCloseModal = modalElement.querySelector('.btn-close');
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', function() {
            console.log('Botón X presionado, deteniendo scanner...');
            if (scannerInitialized) {
                BarcodeScanner.stop();
                scannerInitialized = false;
            }
            closeModal();
        });
    }
    
    // Botón detener
    if (btnStopScanner) {
        btnStopScanner.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Botón detener presionado, deteniendo scanner...');
            if (scannerInitialized) {
                BarcodeScanner.stop();
                scannerInitialized = false;
            }
            closeModal();
        });
    }
    
    // Función para cerrar modal
    function closeModal() {
        if (scannerModal) {
            scannerModal.hide();
        } else {
            // Fallback manual
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
            document.body.classList.remove('modal-open');
            
            // Eliminar todos los backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(b => b.remove());
        }
    }
    
    // Función para buscar producto automáticamente
    function buscarProducto(code) {
        if (!code) return;
        
        // Buscar producto por código EAN13
        fetch('{{ route("fichas.buscar.barcode") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ 
                ean13: code,
                ficha_uuid: '{{ $ficha->uuid }}'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar modal
                closeModal();
                
                // Redirigir a la vista de lista con mensaje de éxito
                window.location.href = data.redirect_url;
            } else {
                // Mostrar mensaje de error
                alert(data.message || '{{ __('Producto no encontrado') }}');
                
                // Detener scanner y cerrar modal
                if (scannerInitialized) {
                    BarcodeScanner.stop();
                    scannerInitialized = false;
                }
                closeModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('{{ __('Error al buscar el producto') }}');
            
            // Detener scanner y cerrar modal en caso de error
            if (scannerInitialized) {
                BarcodeScanner.stop();
                scannerInitialized = false;
            }
            closeModal();
        });
    }
});
</script>
@endpush