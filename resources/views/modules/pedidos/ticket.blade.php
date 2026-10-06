<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $pedido->codigo }} - {{ $config->nombre }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            background: #e2e8f0;
            padding: 20px 10px;
            font-size: 13px;
            line-height: 1.3;
        }

        /* Contenedor del Ticket */
        .ticket-wrapper {
            background: #fff;
            margin: 0 auto;
            padding: 16px 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: width 0.2s ease;
        }

        /* Anchos Térmicos Estándar */
        .ticket-80mm { width: 80mm; max-width: 80mm; }
        .ticket-58mm { width: 58mm; max-width: 58mm; font-size: 11px; }

        .center { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .bold { font-weight: bold; }
        .muted { color: #333; }
        .sm { font-size: 0.85em; }
        .xs { font-size: 0.75em; }
        .lg { font-size: 1.15em; }
        .xl { font-size: 1.35em; }

        .logo-img {
            max-width: 50mm;
            max-height: 25mm;
            object-fit: contain;
            display: block;
            margin: 0 auto 6px;
            filter: grayscale(100%) contrast(150%);
        }

        .hr {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .hr-double {
            border-top: 2px solid #000;
            margin: 6px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
            word-break: break-word;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        th {
            border-bottom: 1px dashed #000;
            padding: 2px 0;
            font-size: 0.9em;
        }
        td {
            padding: 3px 0;
            vertical-align: top;
        }

        .delivery-box {
            border: 1px solid #000;
            padding: 6px;
            margin: 6px 0;
            border-radius: 4px;
        }

        /* Barra de Control en Pantalla */
        .controls-bar {
            max-width: 420px;
            margin: 0 auto 16px;
            background: #fff;
            padding: 12px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
            font-family: system-ui, -apple-system, sans-serif;
        }
        .btn {
            background: #ea580c;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
        }

        /* REGLAS ESTRICTAS PARA IMPRESORA TÉRMICA */
        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000 !important;
            }
            .no-print {
                display: none !important;
            }
            .ticket-wrapper {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 2mm 3mm !important;
            }
            .ticket-80mm {
                width: 78mm !important;
                max-width: 78mm !important;
            }
            .ticket-58mm {
                width: 54mm !important;
                max-width: 54mm !important;
            }
            @page {
                margin: 0;
                size: auto;
            }
        }
    </style>
</head>
<body>
    @php
        $m = $config->moneda;
        $esDelivery = $pedido->tipo === 'delivery';
    @endphp

    {{-- Barra de Herramientas no imprimible --}}
    <div class="controls-bar no-print">
        <div style="display: flex; gap: 6px; align-items: center;">
            <label style="font-size: 12px; font-weight: 600;">Formato:</label>
            <button onclick="setPaper('80mm')" id="btn-80" class="btn">80mm</button>
            <button onclick="setPaper('58mm')" id="btn-58" class="btn-secondary">58mm</button>
        </div>
        <button onclick="window.print()" class="btn" style="background: #16a34a;">🖨️ Imprimir</button>
    </div>

    {{-- Ticket Térmico --}}
    <div id="ticket" class="ticket-wrapper ticket-80mm">
        {{-- Cabecera con Logotipo opcional --}}
        <div class="center">
            @if($config->logo)
                <img src="{{ asset('storage/'.$config->logo) }}" alt="Logo" class="logo-img">
            @endif
            <p class="bold lg">{{ $config->nombre }}</p>
            @if($config->ruc)<p class="sm">RFC: {{ $config->ruc }}</p>@endif
            @if($config->direccion)<p class="xs">{{ $config->direccion }}</p>@endif
            @if($config->telefono)<p class="xs">Tel: {{ $config->telefono }}</p>@endif
        </div>

        <div class="hr"></div>

        <div class="center">
            <p class="bold">{{ $esDelivery ? '*** TICKET DE DELIVERY ***' : '*** TICKET DE VENTA ***' }}</p>
            <p class="bold xl">{{ $pedido->codigo }}</p>
        </div>

        <div class="hr"></div>

        <div class="row sm">
            <span>Fecha:</span>
            <span>{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row sm">
            <span>Canal / Tipo:</span>
            <span class="bold">{{ strtoupper($pedido->tipo) }}</span>
        </div>
        @if(!$esDelivery)
            <div class="row sm">
                <span>Mesa:</span>
                <span class="bold">{{ $pedido->mesa?->nombre ?? 'Mostrador' }}</span>
            </div>
        @endif
        <div class="row sm">
            <span>Cliente:</span>
            <span>{{ $pedido->cliente?->nombre ?? 'Público General' }}</span>
        </div>
        <div class="row sm">
            <span>Atendido por:</span>
            <span>{{ $pedido->user?->name ?? 'Cajero' }}</span>
        </div>

        {{-- Datos especiales de Delivery --}}
        @if($esDelivery)
            <div class="delivery-box">
                <p class="bold sm">DATOS DE ENTREGA:</p>
                <div class="row xs">
                    <span class="bold">Dirección:</span>
                    <span class="right">{{ $pedido->delivery_direccion ?? 'No especificada' }}</span>
                </div>
                @if($pedido->delivery_referencia)
                    <div class="row xs">
                        <span>Ref:</span>
                        <span class="right">{{ $pedido->delivery_referencia }}</span>
                    </div>
                @endif
                <div class="row xs">
                    <span>Teléfono:</span>
                    <span>{{ $pedido->delivery_telefono ?? $pedido->cliente?->telefono ?? '—' }}</span>
                </div>
                @if($pedido->delivery_repartidor)
                    <div class="row xs">
                        <span>Repartidor:</span>
                        <span class="bold">{{ $pedido->delivery_repartidor }}</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="hr"></div>

        {{-- Lista de Productos / Items --}}
        <table>
            <thead>
                <tr>
                    <th class="left">CANT/DESCRIPCIÓN</th>
                    <th class="right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pedido->items as $it)
                    <tr>
                        <td>
                            <span class="bold">{{ $it->cantidad }}x</span> {{ $it->nombre_producto }}
                            @if($it->notas)<br><span class="xs muted">↳ {{ $it->notas }}</span>@endif
                        </td>
                        <td class="right bold" style="white-space:nowrap;">
                            {{ $m }} {{ number_format($it->subtotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="hr"></div>

        {{-- Desglose de Totales --}}
        <div class="row sm">
            <span>Subtotal:</span>
            <span>{{ $m }} {{ number_format($pedido->subtotal, 2) }}</span>
        </div>
        @if($pedido->descuento > 0)
            <div class="row sm">
                <span>Descuento Aplicado:</span>
                <span>- {{ $m }} {{ number_format($pedido->descuento, 2) }}</span>
            </div>
        @endif
        @if($esDelivery && $pedido->delivery_costo_envio > 0)
            <div class="row sm">
                <span>Costo Envío (Delivery):</span>
                <span>+ {{ $m }} {{ number_format($pedido->delivery_costo_envio, 2) }}</span>
            </div>
        @endif
        @if($pedido->impuesto > 0)
            <div class="row sm">
                <span>Impuesto / IVA ({{ $config->igv }}%):</span>
                <span>{{ $m }} {{ number_format($pedido->impuesto, 2) }}</span>
            </div>
        @endif

        <div class="hr-double"></div>

        <div class="row lg bold">
            <span>TOTAL A PAGAR:</span>
            <span>{{ $m }} {{ number_format($pedido->total + ($pedido->delivery_costo_envio ?? 0), 2) }}</span>
        </div>

        <div class="hr-double"></div>

        <div class="row sm">
            <span>Forma de Pago:</span>
            <span class="bold uppercase">{{ $pedido->metodo_pago ? ucfirst($pedido->metodo_pago) : 'Pendiente' }}</span>
        </div>
        <div class="row sm">
            <span>Estado:</span>
            <span class="bold uppercase">{{ $pedido->estado }}</span>
        </div>

        @if($pedido->puntos_ganados > 0 || $pedido->puntos_usados > 0)
            <div class="hr"></div>
            <div class="center sm bold">PROGRAMA DE FIDELIDAD</div>
            @if($pedido->puntos_ganados > 0)
                <div class="row xs"><span>Puntos obtenidos hoy:</span><span>+{{ $pedido->puntos_ganados }} pts</span></div>
            @endif
            @if($pedido->puntos_usados > 0)
                <div class="row xs"><span>Puntos canjeados:</span><span>-{{ $pedido->puntos_usados }} pts</span></div>
            @endif
            @if($pedido->cliente)
                <div class="row xs bold"><span>Saldo total de puntos:</span><span>{{ $pedido->cliente->puntos }} pts</span></div>
            @endif
        @endif

        @if($pedido->notas)
            <div class="hr"></div>
            <p class="xs bold">NOTAS DEL PEDIDO:</p>
            <p class="xs">{{ $pedido->notas }}</p>
        @endif

        <div class="hr"></div>

        <div class="center sm muted" style="margin-top: 8px;">
            <p>¡Gracias por su visita y preferencia!</p>
            <p class="xs">Conserve este comprobante para cualquier aclaración.</p>
        </div>
    </div>

    <script>
        function setPaper(size) {
            const ticket = document.getElementById('ticket');
            const btn80 = document.getElementById('btn-80');
            const btn58 = document.getElementById('btn-58');

            if (size === '58mm') {
                ticket.className = 'ticket-wrapper ticket-58mm';
                btn58.className = 'btn';
                btn80.className = 'btn-secondary';
                localStorage.setItem('thermal_paper_size', '58mm');
            } else {
                ticket.className = 'ticket-wrapper ticket-80mm';
                btn80.className = 'btn';
                btn58.className = 'btn-secondary';
                localStorage.setItem('thermal_paper_size', '80mm');
            }
        }

        // Cargar preferencia guardada o por defecto 80mm
        const saved = localStorage.getItem('thermal_paper_size') || '80mm';
        setPaper(saved);

        // Autoimprimir si viene con parámetro print=1
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
