@if(session('ultimo_recibo'))
    <div class="mb-4 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <span>Último ticket generado para Mesa <strong>{{ session('ultimo_recibo')['mesa'] ?? '—' }}</strong> (${{ number_format(session('ultimo_recibo')['total'] ?? 0, 2) }})</span>
        <a href="{{ route('caja.recibo') }}" target="_blank" class="btn-secondary text-xs">🖨️ Reimprimir ticket</a>
    </div>
@endif

@if(($porCobrar ?? collect())->isNotEmpty())
    <div class="card mb-6">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-800">Mesas listas para cobro</h2>
                <p class="text-xs text-slate-500">Cobra la cuenta completa, divídela en partes iguales o separa por platillos.</p>
            </div>
            <span class="badge bg-indigo-50 text-indigo-700 font-bold">{{ $porCobrar->count() }} mesa(s)</span>
        </div>

        <div class="space-y-4">
            @foreach($porCobrar as $mesa)
                @php
                    $abiertos = $mesa->pedidos;
                    $totalMesa = (float) $abiertos->sum('total');
                    $abonado = (float) $abiertos->sum('abonado');
                    $saldo = round($totalMesa - $abonado, 2);
                    $itemsList = $abiertos->flatMap(fn($p) => $p->items)->map(fn($it) => [
                        'id' => $it->id,
                        'nombre' => $it->nombre_producto,
                        'precio' => (float) $it->precio,
                        'cantidad' => (int) $it->cantidad,
                        'subtotal' => (float) $it->subtotal,
                    ])->values();
                @endphp
                @continue($saldo <= 0)

                <div class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm" x-data="{
                    tab: 'completa',
                    sub: {{ $saldo }},
                    tipo: 'monto',
                    desc: 0,
                    propina: 0,
                    metodo: 'efectivo',
                    recibido: 0,
                    personas: 2,
                    itemsDisponibles: @js($itemsList),
                    itemsSeleccionados: {},
                    get descuento() {
                        return this.tipo === 'porcentaje'
                            ? round2(this.sub * Math.min(this.desc, 100) / 100)
                            : Math.min(this.desc, this.sub);
                    },
                    get pagar() {
                        return Math.max(0, round2(this.sub - this.descuento + Number(this.propina || 0)));
                    },
                    get cambio() {
                        return this.metodo === 'efectivo' ? Math.max(0, round2(Number(this.recibido || 0) - this.pagar)) : 0;
                    },
                    get montoPorPersona() {
                        return this.personas > 0 ? (this.sub / this.personas).toFixed(2) : '0.00';
                    },
                    get cambioParte() {
                        return this.metodo === 'efectivo' ? Math.max(0, round2(Number(this.recibido || 0) - Number(this.montoPorPersona))) : 0;
                    },
                    get subtotalItems() {
                        let t = 0;
                        for (let it of this.itemsDisponibles) {
                            let q = Number(this.itemsSeleccionados[it.id] || 0);
                            t += q * it.precio;
                        }
                        return round2(t);
                    },
                    get seleccionadosCount() {
                        return Object.values(this.itemsSeleccionados).filter(q => q > 0).length;
                    },
                    toggleItem(it) {
                        if (this.itemsSeleccionados[it.id]) {
                            delete this.itemsSeleccionados[it.id];
                        } else {
                            this.itemsSeleccionados[it.id] = it.cantidad;
                        }
                    }
                }">
                    {{-- Cabecera de la mesa --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-100 font-extrabold text-brand-800">
                                {{ $mesa->numero }}
                            </span>
                            <div>
                                <h3 class="font-extrabold text-slate-800">Mesa {{ $mesa->numero }}</h3>
                                <p class="text-xs text-slate-400">
                                    Total: ${{ number_format($totalMesa, 2) }}
                                    @if($abonado > 0)
                                        <span class="text-emerald-600 font-semibold">· Abonado: ${{ number_format($abonado, 2) }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-xs font-semibold uppercase text-slate-400">Saldo pendiente</p>
                            <p class="text-xl font-extrabold text-brand-700">${{ number_format($saldo, 2) }}</p>
                        </div>
                    </div>

                    {{-- Pestañas de modo de cobro --}}
                    <div class="mt-3 flex gap-1 rounded-xl bg-slate-100 p-1 text-xs font-bold text-slate-600">
                        <button type="button" @click="tab='completa'"
                                :class="tab==='completa' ? 'bg-white text-brand-700 shadow-sm' : 'hover:text-slate-900'"
                                class="flex-1 rounded-lg py-2 transition">
                            💵 Cuenta completa
                        </button>
                        <button type="button" @click="tab='partes'"
                                :class="tab==='partes' ? 'bg-white text-indigo-700 shadow-sm' : 'hover:text-slate-900'"
                                class="flex-1 rounded-lg py-2 transition">
                            👥 En partes iguales
                        </button>
                        <button type="button" @click="tab='platillos'"
                                :class="tab==='platillos' ? 'bg-white text-emerald-700 shadow-sm' : 'hover:text-slate-900'"
                                class="flex-1 rounded-lg py-2 transition">
                            🍽️ Por platillos
                        </button>
                    </div>

                    {{-- ==================== MODO 1: CUENTA COMPLETA ==================== --}}
                    <div x-show="tab==='completa'" class="mt-3">
                        <ul class="mb-3 max-h-36 overflow-y-auto divide-y divide-slate-100 rounded-xl bg-slate-50 p-2.5 text-xs text-slate-600">
                            @foreach($abiertos as $pedido)
                                @foreach($pedido->items as $it)
                                    <li class="flex justify-between py-1">
                                        <span><strong>{{ $it->cantidad }}×</strong> {{ $it->nombre_producto }}</span>
                                        <span class="font-semibold text-slate-700">${{ number_format((float)$it->subtotal, 2) }}</span>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>

                        <form method="POST" action="{{ route('caja.cobrar', $mesa) }}" class="grid grid-cols-2 gap-2 text-sm">
                            @csrf
                            <select name="metodo_pago" x-model="metodo" class="form-input-c col-span-2">
                                <option value="efectivo">💵 Efectivo</option>
                                <option value="tarjeta">💳 Tarjeta de crédito/débito</option>
                                <option value="transferencia">📱 Transferencia (SPEI / Yape / Plin)</option>
                                <option value="mixto">🔄 Mixto (Efectivo + Tarjeta)</option>
                            </select>

                            <select name="descuento_tipo" x-model="tipo" class="form-input-c">
                                <option value="monto">Descuento ($)</option>
                                <option value="porcentaje">Descuento (%)</option>
                            </select>
                            <input type="number" step="0.01" min="0" name="descuento" x-model.number="desc" class="form-input-c" placeholder="0.00">

                            <input name="descuento_motivo" class="form-input-c col-span-2" placeholder="Motivo del descuento (opcional)">

                            <div class="col-span-2 sm:col-span-1">
                                <label class="mb-1 block text-xs font-semibold text-slate-500">Propina voluntaria ($)</label>
                                <input type="number" step="0.01" min="0" name="propina" x-model.number="propina" class="form-input-c" placeholder="0.00">
                            </div>

                            <div class="col-span-2 sm:col-span-1" x-show="metodo==='efectivo'">
                                <label class="mb-1 block text-xs font-semibold text-slate-500">Efectivo recibido ($)</label>
                                <input type="number" step="0.01" min="0" name="recibido" x-model.number="recibido" class="form-input-c" placeholder="0.00">
                            </div>

                            <div class="col-span-2 grid grid-cols-2 gap-2" x-show="metodo==='mixto'">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-500">Parte efectivo ($)</label>
                                    <input type="number" step="0.01" min="0" name="pago_efectivo" class="form-input-c" placeholder="0.00">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-500">Parte tarjeta ($)</label>
                                    <input type="number" step="0.01" min="0" name="pago_tarjeta" class="form-input-c" placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-span-2 rounded-xl bg-slate-50 p-2.5 text-center">
                                <p class="text-xs text-slate-400 uppercase font-semibold">Total a liquidar</p>
                                <p class="text-2xl font-black text-brand-700" x-text="'$ ' + pagar.toFixed(2)"></p>
                                <p x-show="metodo==='efectivo' && cambio > 0" class="text-xs font-bold text-emerald-600 mt-0.5">
                                    Cambio a devolver: <span x-text="'$ ' + cambio.toFixed(2)"></span>
                                </p>
                            </div>

                            <button class="col-span-2 rounded-xl bg-emerald-600 py-3 text-sm font-extrabold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700 transition">
                                Cobrar cuenta completa e imprimir ticket
                            </button>
                        </form>
                    </div>

                    {{-- ==================== MODO 2: EN PARTES IGUALES ==================== --}}
                    <div x-show="tab==='partes'" class="mt-3" style="display:none">
                        <div class="mb-3 rounded-xl bg-indigo-50/80 p-3 text-xs text-indigo-900">
                            <p class="font-bold">Calculadora de división equitativa</p>
                            <p class="text-indigo-700 mt-0.5">Indica entre cuántas personas se divide el saldo (${{ number_format($saldo, 2) }}). Cada una puede pagar con su método de pago individual.</p>
                        </div>

                        <form method="POST" action="{{ route('caja.cobrar_parte', $mesa) }}" class="space-y-3 text-sm">
                            @csrf
                            <div class="grid grid-cols-2 gap-3 items-center">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">¿Entre cuántos comensales?</label>
                                    <input type="number" min="2" max="30" name="personas" x-model.number="personas" class="form-input-c text-center font-bold text-base" required>
                                </div>
                                <div class="rounded-xl bg-slate-100 p-2.5 text-center">
                                    <p class="text-[11px] font-semibold uppercase text-slate-400">Monto por persona</p>
                                    <p class="text-xl font-black text-indigo-700" x-text="'$ ' + montoPorPersona"></p>
                                </div>
                            </div>

                            <input type="hidden" name="monto" :value="montoPorPersona">

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Método de pago de este comensal</label>
                                <select name="metodo_pago" x-model="metodo" class="form-input-c">
                                    <option value="efectivo">💵 Efectivo</option>
                                    <option value="tarjeta">💳 Tarjeta de crédito/débito</option>
                                    <option value="transferencia">📱 Transferencia (SPEI / Yape)</option>
                                </select>
                            </div>

                            <div x-show="metodo==='efectivo'">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Efectivo recibido ($)</label>
                                <input type="number" step="0.01" min="0" name="recibido" x-model.number="recibido" class="form-input-c" placeholder="0.00">
                                <p x-show="cambioParte > 0" class="text-xs font-bold text-emerald-600 mt-1">
                                    Cambio a devolver: <span x-text="'$ ' + cambioParte.toFixed(2)"></span>
                                </p>
                            </div>

                            <button class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-extrabold text-white shadow-md shadow-indigo-600/20 hover:bg-indigo-700 transition">
                                Cobrar 1 parte (<span x-text="'$ ' + montoPorPersona"></span>) e imprimir recibo
                            </button>
                        </form>
                    </div>

                    {{-- ==================== MODO 3: POR PLATILLOS SELECCIONADOS ==================== --}}
                    <div x-show="tab==='platillos'" class="mt-3" style="display:none">
                        <div class="mb-3 rounded-xl bg-emerald-50/80 p-3 text-xs text-emerald-900">
                            <p class="font-bold">Separación por platillo / comensal</p>
                            <p class="text-emerald-700 mt-0.5">Selecciona los platillos que va a pagar este cliente. Los platillos no seleccionados seguirán en la mesa.</p>
                        </div>

                        <form method="POST" action="{{ route('caja.cobrar_items', $mesa) }}" class="space-y-3 text-sm">
                            @csrf
                            <div class="max-h-48 overflow-y-auto space-y-2 rounded-xl bg-slate-50 p-2.5">
                                <template x-for="(it, idx) in itemsDisponibles" :key="it.id">
                                    <div class="flex items-center justify-between gap-2 rounded-lg bg-white p-2 shadow-xs border border-slate-200/80">
                                        <label class="flex items-center gap-2 min-w-0 flex-1 cursor-pointer">
                                            <input type="checkbox" @change="toggleItem(it)" :checked="!!itemsSeleccionados[it.id]"
                                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                                            <span class="truncate text-xs font-bold text-slate-800" x-text="it.nombre"></span>
                                        </label>

                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-slate-400" x-text="'$' + it.precio.toFixed(2)"></span>
                                            <div x-show="itemsSeleccionados[it.id]" class="flex items-center gap-1">
                                                <input type="hidden" :name="'items['+idx+'][id]'" :value="it.id">
                                                <input type="number" :name="'items['+idx+'][cantidad]'" min="1" :max="it.cantidad"
                                                       x-model.number="itemsSeleccionados[it.id]"
                                                       class="w-14 rounded-lg border-slate-200 py-1 text-center text-xs font-bold">
                                                <span class="text-[11px] text-slate-400" x-text="'/ ' + it.cantidad"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div class="col-span-2">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Método de pago</label>
                                    <select name="metodo_pago" x-model="metodo" class="form-input-c">
                                        <option value="efectivo">💵 Efectivo</option>
                                        <option value="tarjeta">💳 Tarjeta</option>
                                        <option value="transferencia">📱 Transferencia</option>
                                    </select>
                                </div>
                                <div x-show="metodo==='efectivo'" class="col-span-2">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Efectivo recibido ($)</label>
                                    <input type="number" step="0.01" min="0" name="recibido" class="form-input-c" placeholder="0.00">
                                </div>
                            </div>

                            <div class="rounded-xl bg-slate-100 p-2.5 text-center">
                                <p class="text-[11px] font-semibold uppercase text-slate-400">Total de platillos seleccionados</p>
                                <p class="text-2xl font-black text-emerald-700" x-text="'$ ' + subtotalItems.toFixed(2)"></p>
                            </div>

                            <button :disabled="subtotalItems <= 0"
                                    class="w-full rounded-xl bg-emerald-600 py-3 text-sm font-extrabold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700 disabled:opacity-40 transition">
                                Cobrar platillos seleccionados (<span x-text="'$' + subtotalItems.toFixed(2)"></span>) e imprimir
                            </button>
                        </form>
                    </div>

                </div>
            @endforeach
        </div>
    </div>
@endif

<script>
    function round2(num) {
        return Math.round((Number(num) + Number.EPSILON) * 100) / 100;
    }
</script>
