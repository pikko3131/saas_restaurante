<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><title>Ticket</title>
<style>
body{font-family:ui-monospace,monospace;width:80mm;margin:0 auto;color:#111}
h1{font-size:16px;text-align:center;margin:0}
.m{text-align:center;font-size:12px}
table{width:100%;font-size:12px;border-collapse:collapse}
td{padding:2px 0}
.r{text-align:right}
.b{font-weight:700}
hr{border:none;border-top:1px dashed #999}
@media print{.no{display:none}}
</style></head><body onload="window.print()">
<h1>{{ $rest->nombre ?? 'Ticket' }}</h1>
<p class="m">Mesa {{ $r['mesa'] }} · {{ now()->format('d/m/Y H:i') }}</p>
<hr>
<table>
@foreach($pedidos as $p)
  @foreach($p->items as $it)
    <tr><td>{{ $it->cantidad }}× {{ $it->nombre_producto }}</td><td class="r">${{ number_format($it->subtotal,2) }}</td></tr>
  @endforeach
@endforeach
</table>
<hr>
<table>
<tr><td>Subtotal</td><td class="r">${{ number_format($r['subtotal'],2) }}</td></tr>
@if(($r['descuento']??0)>0)<tr><td>Descuento{{ $r['motivo']?' ('.$r['motivo'].')':'' }}</td><td class="r">-${{ number_format($r['descuento'],2) }}</td></tr>@endif
@if(($r['propina']??0)>0)<tr><td>Propina</td><td class="r">${{ number_format($r['propina'],2) }}</td></tr>@endif
<tr class="b"><td>Total</td><td class="r">${{ number_format($r['total'],2) }}</td></tr>
<tr><td>{{ ucfirst($r['metodo']) }}</td><td class="r"></td></tr>
@if($r['metodo']==='efectivo')
<tr><td>Recibido</td><td class="r">${{ number_format($r['recibido'],2) }}</td></tr>
<tr class="b"><td>Cambio</td><td class="r">${{ number_format($r['cambio'],2) }}</td></tr>
@endif
</table>
<p class="m">Gracias</p>
<p class="no" style="text-align:center;margin-top:12px"><a href="{{ route('caja.index') }}">Volver a caja</a> · <button onclick="window.print()">Reimprimir</button></p>
</body></html>
