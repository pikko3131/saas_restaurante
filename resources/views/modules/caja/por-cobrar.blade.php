@if(($porCobrar ?? collect())->isNotEmpty())
    <div class="card mb-6">
        <h2 class="mb-1 font-bold text-slate-800">Mesas para cobrar</h2>
        <p class="mb-3 text-sm text-slate-500">El mesero ya pidió la cuenta. Cobra aquí y la mesa queda libre.</p>
        <div class="space-y-3">
            @foreach($porCobrar as $mesa)
                @php
                    $abiertos = $mesa->pedidos;
                    $total = $abiertos->sum('total');
                @endphp
                <div class="rounded-xl bg-indigo-50 px-3 py-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-bold text-slate-800">Mesa {{ $mesa->numero }}</p>
                            <p class="text-xs text-slate-500">{{ $abiertos->count() }} pedido(s)</p>
                        </div>
                        <p class="text-lg font-extrabold text-slate-900">${{ number_format((float) $total, 2) }}</p>
                    </div>
                    <ul class="mt-2 text-sm text-slate-700">
                        @foreach($abiertos as $pedido)
                            @foreach($pedido->items as $it)
                                <li>{{ $it->cantidad }}× {{ $it->nombre_producto }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                    <form method="POST" action="{{ route('caja.cobrar', $mesa) }}" class="mt-3 flex gap-2">
                        @csrf
                        <select name="metodo_pago" class="form-input-c">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Cobrar</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endif
