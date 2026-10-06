@if(($porCobrar ?? collect())->isNotEmpty())
    <div class="card mb-6">
        <h2 class="mb-1 font-bold text-slate-800">Mesas para cobrar</h2>
        <p class="mb-3 text-sm text-slate-500">El mesero ya pidió la cuenta. Abre el ticket y cobra el pedido en Pedidos.</p>
        <div class="space-y-2">
            @foreach($porCobrar as $mesa)
                <div class="flex items-center justify-between rounded-xl bg-indigo-50 px-3 py-2">
                    <div>
                        <p class="font-bold text-slate-800">Mesa {{ $mesa->numero }}</p>
                        <p class="text-xs text-slate-500">{{ $mesa->pedidoActivo?->codigo }} · ${{ number_format((float) ($mesa->pedidoActivo?->total ?? 0), 2) }}</p>
                    </div>
                    @if($mesa->pedidoActivo)
                        <a href="{{ route('pedidos.ticket', $mesa->pedidoActivo) }}" target="_blank" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white">Ticket</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
