@if(session('ultimo_recibo'))
    <div class="mb-4"><a href="{{ route('caja.recibo') }}" target="_blank" class="btn-secondary text-xs">Reimprimir último ticket</a></div>
@endif
@if(($porCobrar ?? collect())->isNotEmpty())
    <div class="card mb-6">
        <h2 class="mb-1 font-bold text-slate-800">Mesas para cobrar</h2>
        <div class="space-y-4">
            @foreach($porCobrar as $mesa)
                @php $abiertos = $mesa->pedidos; $total = (float) $abiertos->sum('total'); @endphp
                @continue($total <= 0)
                <div class="rounded-xl bg-indigo-50 px-3 py-3" x-data="{
                    sub: {{ $total }}, tipo:'monto', desc:0, propina:0, metodo:'efectivo', recibido:0,
                    get descuento(){ return this.tipo==='porcentaje' ? this.sub*Math.min(this.desc,100)/100 : Math.min(this.desc,this.sub); },
                    get pagar(){ return Math.max(0, this.sub - this.descuento + Number(this.propina||0)); },
                    get cambio(){ return this.metodo==='efectivo' ? Math.max(0, Number(this.recibido||0) - this.pagar) : 0; }
                }">
                    <div class="flex items-center justify-between">
                        <p class="font-bold text-slate-800">Mesa {{ $mesa->numero }}</p>
                        <p class="text-lg font-extrabold">${{ number_format($total, 2) }}</p>
                    </div>
                    <ul class="mt-1 text-sm text-slate-600">
                        @foreach($abiertos as $pedido)@foreach($pedido->items as $it)<li>{{ $it->cantidad }}× {{ $it->nombre_producto }}</li>@endforeach @endforeach
                    </ul>
                    <form method="POST" action="{{ route('caja.cobrar', $mesa) }}" class="mt-3 grid grid-cols-2 gap-2">
                        @csrf
                        <select name="metodo_pago" x-model="metodo" class="form-input-c col-span-2">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="mixto">Mixto</option>
                        </select>
                        <select name="descuento_tipo" x-model="tipo" class="form-input-c"><option value="monto">Desc. $</option><option value="porcentaje">Desc. %</option></select>
                        <input type="number" step="0.01" min="0" name="descuento" x-model.number="desc" class="form-input-c" placeholder="0">
                        <input name="descuento_motivo" class="form-input-c col-span-2" placeholder="Motivo del descuento">
                        <input type="number" step="0.01" min="0" name="propina" x-model.number="propina" class="form-input-c" placeholder="Propina">
                        <input x-show="metodo==='efectivo'" type="number" step="0.01" min="0" name="recibido" x-model.number="recibido" class="form-input-c" placeholder="Efectivo recibido">
                        <input x-show="metodo==='mixto'" type="number" step="0.01" min="0" name="pago_efectivo" class="form-input-c" placeholder="Parte en efectivo">
                        <input x-show="metodo==='mixto'" type="number" step="0.01" min="0" name="pago_tarjeta" class="form-input-c" placeholder="Parte en tarjeta">
                        <input type="number" step="0.01" min="0" name="cobrar_ahora" class="form-input-c col-span-2" placeholder="Cobrar solo una parte (déjalo vacío para cobrar todo)">
                        <p class="col-span-2 text-sm">A pagar <b x-text="'$ '+pagar.toFixed(2)"></b> <span x-show="metodo==='efectivo'">· Cambio <b x-text="'$ '+cambio.toFixed(2)"></b></span></p>
                        <button class="col-span-2 rounded-lg bg-emerald-600 py-2 text-sm font-bold text-white">Cobrar e imprimir</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endif
