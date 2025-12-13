<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.3;
            padding: 5mm;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
        }
        
        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .header p {
            font-size: 9px;
            margin: 1px 0;
        }
        
        .info-section {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }
        
        .info-row {
            margin: 2px 0;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }
        
        .items-section {
            margin-bottom: 10px;
        }
        
        .item {
            margin: 4px 0;
        }
        
        .item-header {
            font-weight: bold;
            margin-bottom: 1px;
        }
        
        .item-name {
            display: inline-block;
            width: 70%;
        }
        
        .item-price {
            display: inline-block;
            width: 28%;
            text-align: right;
        }
        
        .item-detail {
            font-size: 9px;
            padding-left: 5px;
            color: #333;
        }
        
        .separator {
            border-bottom: 1px dashed #000;
            margin: 8px 0;
        }
        
        .totals-section {
            margin-top: 10px;
        }
        
        .total-row {
            margin: 2px 0;
        }
        
        .total-label {
            display: inline-block;
            width: 70%;
        }
        
        .total-value {
            display: inline-block;
            width: 28%;
            text-align: right;
        }
        
        .grand-total {
            font-size: 13px;
            font-weight: bold;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 2px solid #000;
        }
        
        .iva-desglose {
            margin: 8px 0;
            font-size: 8px;
            border-top: 1px dashed #000;
            padding-top: 8px;
        }
        
        .iva-desglose-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 8px;
            border-top: 2px dashed #000;
            font-size: 9px;
        }
        
        .footer p {
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $site->titulo ?? 'TICKET' }}</h1>
        <h3>{{ siteName() }}</h3>
        @if($site->direccion)
            <p>{{ $site->direccion }}</p>
        @endif
        @if($site->codigo_postal || $site->ciudad)
            <p>{{ $site->codigo_postal }} {{ $site->ciudad }}</p>
        @endif
        @if($site->telefono)
            <p>Tel: {{ $site->telefono }}</p>
        @endif
        @if($site->cif)
            <p>CIF: {{ $site->cif }}</p>
        @endif
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">FECHA:</span>
            <span>{{ $ficha->hora_cierre ? $ficha->hora_cierre->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
        </div>
        @if($ficha->camarero)
        <div class="info-row">
            <span class="info-label">CAMARERO:</span>
            <span>{{ $ficha->camarero->name }}</span>
        </div>
        @endif
        @php
            $totalComensales = 0;
            if ($ficha->usuarios && $ficha->usuarios->count() > 0) {
                $totalComensales = $ficha->usuarios->count() + $ficha->usuarios->sum('invitados') + $ficha->usuarios->sum('ninos');
            }
        @endphp
        @if($totalComensales > 0)
        <div class="info-row">
            <span class="info-label">COMENSALES:</span>
            <span>{{ $totalComensales }}</span>
        </div>
        @endif
    </div>
    
    <div class="items-section">
        @foreach($lineas as $linea)
        <div class="item">
            <div class="item-header">
                <span class="item-name">{{ $linea['nombre'] }}</span>
                <span class="item-price">{{ number_format($linea['total'], 2, ',', '.') }} €</span>
            </div>
            <div class="item-detail">
                {{ $linea['cantidad'] }} x {{ number_format($linea['precio_unitario'], 2, ',', '.') }} € (IVA {{ number_format($linea['iva'], 0) }}%)
            </div>
        </div>
        @endforeach
    </div>
        
    <div class="totals-section">
        <div class="total-row grand-total">
            <span class="total-label">TOTAL:</span>
            <span class="total-value">{{ number_format($total, 2, ',', '.') }} €</span>
        </div>
    </div>
    
    
    
    <div class="footer">
        <p>¡GRACIAS POR SU VISITA!</p>
        <p>Este documento no tiene validez fiscal</p>
        <p style="margin-top: 3px; font-size: 8px;">{{ __('Generado el') }} {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
