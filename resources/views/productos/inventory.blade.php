@extends('layouts.app')
@section('content')
<div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
        <div class="col-md-12 col-sm-12 col-lg-12 d-flex h-100">
            <div class="card flex-fill d-flex flex-column">
                <div class="card-header fondo-rojo"><i class="bi bi-cup-straw"></i> {{ __('Productos - Inventario') }}</div>

                <div class="card-body overflow-auto flex-fill">

                    <div class="container-fluid">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-12 col-md-12 col-lg-12">


                                <form id='editar-inventario' action="{{ route('productos.inventory') }}" method="post">
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
                                                        <th scope="col-auto" class="text-center">{{ __('Imagen') }}</th>
                                                        <th scope="col-auto">{{ __('Nombre') }}</th>
                                                        <th scope="col-auto" class="text-center">
                                                            {{ __('Stock') }}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($productos as $producto)
                                                    @php
                                                        $controlStockActivo = ($ajustes->permitir_comprar_sin_stock ?? 0) == 0;
                                                        $stockDisponible = max(0, $producto->stock - ($producto->stock_reservado ?? 0));
                                                        $esStockBajo = $controlStockActivo && $stockDisponible <= ($ajustes->stock_minimo ?? 5) && $stockDisponible > 0;
                                                        $esAgotado = $controlStockActivo && $stockDisponible <= 0;
                                                    @endphp
                                                    <tr style="height: 80px;" class="{{ $esAgotado ? 'table-danger' : ($esStockBajo ? 'table-warning' : '') }}">
                                                        <td class="align-middle">
                                                            <img width="60" height="60" class="img-fluid rounded img-responsive" 
                                                                 src="{{ cachedImage($producto->imagen) }}" 
                                                                 alt="{{ $producto->nombre }}"
                                                                 loading="lazy"
                                                                 decoding="async" />
                                                            <input type="hidden" name="uuid[{{ $producto->uuid }}]" value="{{ $producto->uuid }}">
                                                        </td>

                                                        <td class="align-middle">
                                                            {{ $producto->nombre }}
                                                            @if($controlStockActivo)
                                                                @if($esAgotado)
                                                                    <span class="badge bg-danger ms-2">{{ __('Agotado') }}</span>
                                                                @elseif($esStockBajo)
                                                                    <span class="badge bg-warning text-dark ms-2">{{ __('Stock bajo') }}</span>
                                                                @endif
                                                                @if($producto->stock_reservado > 0)
                                                                    <br><small class="text-muted">({{ number_format($producto->stock_reservado, 2) }} reservado)</small>
                                                                @endif
                                                            @endif
                                                        </td>
                                                        <td width="100" class="align-middle text-center">
                                                            <div class="form-group">
                                                                <input class="form-control text-center" type="number" min="0" step="0.01" name="stock[{{ $producto->uuid }}]" id="stock[{{ $producto->uuid }}]" value="{{ $producto->stock }}">
                                                            </div>
                                                            @if($controlStockActivo)
                                                                <small class="text-muted d-block mt-1">
                                                                    Disponible: <strong class="{{ $esAgotado ? 'text-dark' : ($esStockBajo ? 'text-dark' : 'text-dark') }}">{{ number_format($stockDisponible, 2) }}</strong>
                                                                </small>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
        
                                </form>
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
                            <a class="btn btn-secondary mx-1" href={{ route('productos.index') }}><i class="bi bi-chevron-left"></i></a>
                            @if(isset($ajustes) && $ajustes->permitir_lectura_codigo_barras == 1)
                            <button type="button" id="btn-open-scanner" class="btn btn-primary mx-1">
                                <i class="bi bi-upc-scan"></i>
                            </button>
                            @endif
                            <button type="button" onclick="document.getElementById('editar-inventario').submit();" class="btn btn-success mx-1"><i class="bi bi-floppy"></i></button>
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
</style>
@endpush

@push('scripts')
<!-- Cargar Quagga desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2@1.8.4/dist/quagga.min.js"></script>

<script>
    // Inicializar tooltips de Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<script>
// Objeto BarcodeScanner
window.BarcodeScanner = {
    isScanning: false,
    onDetected: null,
    videoStream: null,
    
    init(videoElementId, onDetectedCallback) {
        this.onDetected = onDetectedCallback;
        this.detectionCache = {};
        
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
            
            const videoElement = document.querySelector('#' + videoElementId + ' video');
            if (videoElement && videoElement.srcObject) {
                this.videoStream = videoElement.srcObject;
            }
        });
        
        Quagga.onDetected(this.handleDetection.bind(this));
        
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
    
    handleDetection(result) {
        if (!result || !result.codeResult) {
            return;
        }
        
        const code = result.codeResult.code;
        const format = result.codeResult.format;
        
        const errors = result.codeResult.decodedCodes
            .filter(_ => _.error !== undefined)
            .map(_ => _.error);
        const avgError = errors.reduce((a, b) => a + b, 0) / errors.length;
        
        console.log('Código detectado:', code, 'Formato:', format, 'Error promedio:', avgError);
        
        if (avgError > 0.1) {
            console.log('Lectura rechazada por baja calidad');
            return;
        }
        
        if (code.length < 8) {
            console.log('Código muy corto, rechazado');
            return;
        }
        
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
    
    stop() {
        if (this.isScanning) {
            try {
                Quagga.offDetected();
                Quagga.offProcessed();
                Quagga.stop();
                
                if (this.videoStream) {
                    this.videoStream.getTracks().forEach(track => {
                        track.stop();
                    });
                    this.videoStream = null;
                }
                
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
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('scannerModal');
    const btnOpenScanner = document.getElementById('btn-open-scanner');
    const btnStopScanner = document.getElementById('btn-stop-scanner');
    
    if (!btnOpenScanner || !modalElement) {
        console.log('Elementos del scanner no encontrados');
        return;
    }
    
    let scannerModal = null;
    let scannerInitialized = false;
    let detectedBarcode = null;
    
    // Intentar crear instancia de Bootstrap Modal
    try {
        const Bootstrap = window.bootstrap || window.Bootstrap;
        if (Bootstrap && Bootstrap.Modal) {
            scannerModal = new Bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true
            });
        }
    } catch (e) {
        console.log('Bootstrap Modal no disponible, usando método alternativo');
    }
    
    btnOpenScanner.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Abriendo modal del scanner...');
        
        if (scannerModal) {
            scannerModal.show();
        } else {
            // Fallback manual
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            document.body.classList.add('modal-open');
            
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'scanner-backdrop-inventory';
            document.body.appendChild(backdrop);
            
            // Iniciar scanner manualmente
            setTimeout(initScanner, 500);
        }
    });
    
    function initScanner() {
        if (scannerInitialized) {
            console.log('Scanner ya inicializado, omitiendo...');
            return;
        }
        
        setTimeout(function() {
            console.log('Iniciando scanner...');
            scannerInitialized = true;
            BarcodeScanner.init('barcode-scanner', function(code, format) {
                detectedBarcode = code;
                
                if (navigator.vibrate) {
                    navigator.vibrate(200);
                }
                
                buscarProducto(code);
            });
        }, 500);
    }
    
    modalElement.addEventListener('shown.bs.modal', initScanner);
    
    modalElement.addEventListener('hidden.bs.modal', function() {
        console.log('Modal cerrado, deteniendo scanner...');
        if (scannerInitialized) {
            BarcodeScanner.stop();
            scannerInitialized = false;
        }
        detectedBarcode = null;
    });
    
    const btnCloseModal = modalElement.querySelector('.btn-close');
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', function() {
            if (scannerInitialized) {
                BarcodeScanner.stop();
                scannerInitialized = false;
            }
            closeModal();
        });
    }
    
    if (btnStopScanner) {
        btnStopScanner.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (scannerInitialized) {
                BarcodeScanner.stop();
                scannerInitialized = false;
            }
            closeModal();
        });
    }
    
    function closeModal() {
        if (scannerModal && scannerModal.hide) {
            scannerModal.hide();
        } else {
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
            document.body.classList.remove('modal-open');
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(b => b.remove());
        }
    }
    
    // Eventos de Bootstrap Modal si está disponible
    if (scannerModal) {
        modalElement.addEventListener('shown.bs.modal', initScanner);
        
        modalElement.addEventListener('hidden.bs.modal', function() {
            console.log('Modal cerrado, deteniendo scanner...');
            if (scannerInitialized) {
                BarcodeScanner.stop();
                scannerInitialized = false;
            }
            detectedBarcode = null;
        });
    }
    
    function buscarProducto(code) {
        if (!code) return;
        
        fetch('{{ route("productos.buscar.barcode") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ 
                ean13: code
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.producto) {
                closeModal();
                
                // Preguntar cantidad a añadir
                const cantidad = prompt('{{ __("Producto encontrado") }}: ' + data.producto.nombre + '\n{{ __("\u00bfCuántas unidades deseas añadir al inventario?") }}', '1');
                
                if (cantidad !== null && cantidad !== '' && !isNaN(cantidad) && parseInt(cantidad) > 0) {
                    actualizarInventario(data.producto.uuid, parseInt(cantidad));
                }
            } else {
                alert(data.message || '{{ __("Producto no encontrado") }}');
                if (scannerInitialized) {
                    BarcodeScanner.stop();
                    scannerInitialized = false;
                }
                closeModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('{{ __("Error al buscar el producto") }}');
            if (scannerInitialized) {
                BarcodeScanner.stop();
                scannerInitialized = false;
            }
            closeModal();
        });
    }
    
    function actualizarInventario(productoUuid, cantidad) {
        const inputStock = document.querySelector('input[name="stock[' + productoUuid + ']"]');
        
        if (inputStock) {
            const stockActual = parseInt(inputStock.value) || 0;
            const nuevoStock = stockActual + cantidad;
            inputStock.value = nuevoStock;
            
            // Buscar la fila (tr) que contiene este input
            const fila = inputStock.closest('tr');
            const stockMinimo = {{ $ajustes->stock_minimo ?? 5 }};
            
            // Si el nuevo stock es mayor que el mínimo, quitar el fondo rojo y el badge
            if (nuevoStock > stockMinimo && fila) {
                // Quitar clase table-danger de la fila
                fila.classList.remove('table-danger');
                
                // Buscar y eliminar el badge "Stock bajo"
                const badge = fila.querySelector('.badge.bg-danger');
                if (badge) {
                    badge.remove();
                }
            }
            
            // Resaltar el campo actualizado
            inputStock.classList.add('border-success', 'border-3');
            setTimeout(() => {
                inputStock.classList.remove('border-success', 'border-3');
            }, 2000);
            
            alert('{{ __("Stock actualizado") }}: ' + cantidad + ' {{ __("unidades añadidas") }}. {{ __("Total") }}: ' + nuevoStock);
        } else {
            console.error('Input de stock no encontrado para UUID:', productoUuid);
            alert('{{ __("Error: No se pudo actualizar el stock") }}');
        }
    }
});
</script>
@endpush