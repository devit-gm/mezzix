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
            font-size: 14px;
            line-height: 1.55;
            padding: 1.8mm;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
        }

        .header h1 {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .header h3 {
            font-size: 17px;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 13px;
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
            width: 98px;
        }

        .items-section {
            margin-bottom: 10px;
        }

        .item {
            margin: 8px 0;
        }

        .item-line {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 13px;
        }

        .item-line td {
            padding: 0;
            vertical-align: top;
            white-space: nowrap;
        }

        .item-name {
            width: 52%;
            font-weight: bold;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 4px;
        }

        .item-unit {
            width: 24%;
            text-align: right;
            padding-right: 4px;
        }

        .item-total {
            width: 24%;
            text-align: right;
            font-weight: bold;
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

        .total-line {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .total-line td {
            white-space: nowrap;
            padding: 0;
        }

        .total-label {
            width: 52%;
        }

        .total-value {
            width: 48%;
            text-align: right;
        }

        .grand-total {
            font-size: 21px;
            font-weight: bold;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 2px solid #000;
        }

        .extra-charge-row {
            margin-top: 6px;
            font-size: 13px;
        }

        .extra-charge-row .total-label,
        .extra-charge-row .total-value {
            font-weight: bold;
        }

        .iva-desglose {
            margin: 8px 0;
            font-size: 12px;
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
            font-size: 13px;
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
            <table class="item-line">
                <tr>
                    <td class="item-name">{{ $linea['nombre'] }}</td>
                    <td class="item-unit">{{ $linea['cantidad'] }}x{{ number_format($linea['precio_unitario'], 2, ',', '.') }}</td>
                    <td class="item-total">{{ number_format($linea['total'], 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
        @endforeach
    </div>

    <div class="totals-section">
        <div class="total-row grand-total">
            <table class="total-line">
                <tr>
                    <td class="total-label">TOTAL:</td>
                    <td class="total-value">{{ number_format($total, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>

        @if(isset($cargoInvitados) && ($cargoInvitados['importe'] ?? 0) > 0)
        <div class="total-row extra-charge-row">
            <table class="total-line">
                <tr>
                    <td class="total-label">Cargo invitados ({{ $cargoInvitados['cantidad_cobrada'] }}):</td>
                    <td class="total-value">{{ number_format($cargoInvitados['importe'], 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
        @endif
    </div>



    <div class="footer">
        <p>¡GRACIAS POR SU VISITA!</p>
        <p>Este documento no tiene validez fiscal</p>
        <p style="margin-top: 3px; font-size: 11px;">{{ __('Generado el') }} {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>

</html>