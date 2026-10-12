<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuenta mesa {{ $mesa->numero }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f1f5f9; margin: 0; color: #0f172a; }
        .hoja { max-width: 420px; margin: 0 auto; background: #fff; min-height: 100vh; padding: 20px 16px 40px; }
        h1 { font-size: 22px; margin: 0; }
        .muted { color: #64748b; font-size: 13px; }
        .item { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .nota { color: #94a3b8; font-size: 12px; }
        .tot { display: flex; justify-content: space-between; margin-top: 8px; }
        .grande { font-size: 20px; font-weight: 800; margin-top: 12px; }
        .btn { display: block; width: 100%; margin-top: 16px; border: 0; border-radius: 12px; padding: 14px; font-weight: 700; font-size: 15px; }
        .print { background: #0f172a; color: #fff; }
        .back { background: #e2e8f0; color: #0f172a; text-align: center; text-decoration: none; }
        @media print { body { background: #fff; } .no-print { display: none; } .hoja { max-width: 80mm; padding: 0; } }
    </style>
</head>
<body>
<div class="hoja">
    <p class="muted">{{ $config->nombre ?? $config->nombre_restaurante ?? 'Restaurante' }}</p>
    <h1>Mesa {{ $mesa->numero }}</h1>
    <p class="muted">Cuenta para el cliente · {{ now()->format('d/m/Y H:i') }}</p>
    @forelse ($pedidos as $pedido)
        <p class="muted" style="margin-top:16px">{{ $pedido->codigo }}</p>
        @foreach ($pedido->items as $it)
            <div class="item">
                <div><strong>{{ $it->cantidad }}×</strong> {{ $it->nombre_producto }}@if ($it->notas)<div class="nota">{{ $it->notas }}</div>@endif</div>
                <div>${{ number_format($it->subtotal, 2) }}</div>
            </div>
        @endforeach
    @empty
        <p>Esta mesa no tiene pedidos abiertos.</p>
    @endforelse
    <div class="tot"><span>Subtotal</span><span>${{ number_format($subtotal, 2) }}</span></div>
    <div class="tot"><span>IVA</span><span>${{ number_format($impuesto, 2) }}</span></div>
    <div class="tot grande"><span>Total</span><span>${{ number_format($total, 2) }}</span></div>
    <p class="muted">Pendiente de cobro. Caja también la ve.</p>
    <button class="btn print no-print" onclick="window.print()">Imprimir cuenta</button>
    <a class="btn back no-print" href="/mesero">Volver a mis mesas</a>
</div>
</body>
</html>
