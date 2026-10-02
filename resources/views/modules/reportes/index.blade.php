<x-app-layout title="Reportes Analíticos">
    @php $m = $config->moneda; @endphp
    
    <x-page-header title="Centro de Reportes y Analítica" subtitle="Métricas avanzadas para la toma de decisiones en tu restaurante.">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('reportes.exportar_ventas', request()->only('desde','hasta')) }}" class="btn-secondary text-xs">⬇️ Ventas (CSV)</a>
            <a href="{{ route('reportes.exportar', request()->only('desde','hasta')) }}" class="btn-secondary text-xs">⬇️ Rentabilidad (CSV)</a>
            <a href="{{ route('reportes.imprimir', request()->only('desde','hasta')) }}" target="_blank" class="btn-primary text-xs">🖨️ Imprimir / PDF</a>
        </div>
    </x-page-header>

    {{-- Filtro de fechas --}}
    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-3 bg-white shadow-sm border border-slate-200">
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Fecha Desde</label>
            <input type="date" name="desde" value="{{ \Illuminate\Support\Carbon::parse($desde)->toDateString() }}" class="form-input-c">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Fecha Hasta</label>
            <input type="date" name="hasta" value="{{ \Illuminate\Support\Carbon::parse($hasta)->toDateString() }}" class="form-input-c">
        </div>
        <button class="btn-primary">Filtrar período</button>
    </form>

    {{-- KPIs principales --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card bg-white border border-slate-200">
            <p class="text-xs uppercase font-bold tracking-wider text-slate-400">Ventas Totales</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $m }} {{ number_format($totalVentas,2) }}</p>
            <p class="mt-1 text-xs text-emerald-600 font-medium">Facturado en el período</p>
        </div>
        <div class="card bg-white border border-slate-200">
            <p class="text-xs uppercase font-bold tracking-wider text-slate-400">Pedidos Cobrados</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $numPedidos }}</p>
            <p class="mt-1 text-xs text-slate-500">Tickets finalizados</p>
        </div>
        <div class="card bg-white border border-slate-200">
            <p class="text-xs uppercase font-bold tracking-wider text-slate-400">Ticket Promedio</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $m }} {{ number_format($ticketPromedio,2) }}</p>
            <p class="mt-1 text-xs text-slate-500">Gasto medio por orden</p>
        </div>
        <div class="card bg-white border border-slate-200">
            <p class="text-xs uppercase font-bold tracking-wider text-slate-400">Margen Bruto Global</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-600">{{ $margenTotalR }}%</p>
            <p class="mt-1 text-xs text-slate-500">Ganancia: {{ $m }} {{ number_format($gananciaTotalR, 2) }}</p>
        </div>
    </div>

    {{-- Navegación por pestañas de los 8 reportes --}}
    <div x-data="{ tab: 'temporal' }" class="space-y-6">
        <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
            <button @click="tab = 'temporal'" :class="tab === 'temporal' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-xs font-bold transition-all">📈 1. Ventas y Horas Pico</button>
            <button @click="tab = 'rentabilidad'" :class="tab === 'rentabilidad' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-xs font-bold transition-all">🥩 2. Rentabilidad por Plato</button>
            <button @click="tab = 'colaboradores'" :class="tab === 'colaboradores' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-xs font-bold transition-all">🧑‍🍳 3. Ventas por Colaborador</button>
            <button @click="tab = 'metodos'" :class="tab === 'metodos' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-xs font-bold transition-all">💳 4. Métodos de Pago</button>
            <button @click="tab = 'canales'" :class="tab === 'canales' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-xs font-bold transition-all">🛵 5. Tipos de Pedido / Canales</button>
            <button @click="tab = 'inventario'" :class="tab === 'inventario' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-xs font-bold transition-all">📦 6. Consumo de Inventario</button>
            <button @click="tab = 'clientes'" :class="tab === 'clientes' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-xs font-bold transition-all">👥 7. Fidelización y Clientes</button>
            <button @click="tab = 'cajas'" :class="tab === 'cajas' ? 'bg-brand-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100'" class="rounded-xl px-4 py-2 text-xs font-bold transition-all">💰 8. Auditoría de Cajas</button>
        </div>

        {{-- TAB 1: Temporal y Horas Pico --}}
        <div x-show="tab === 'temporal'" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card lg:col-span-2">
                    <h3 class="mb-3 text-sm font-bold text-slate-800">Evolución de Ventas Diarias</h3>
                    <div id="chartVentas"></div>
                </div>
                <div class="card">
                    <h3 class="mb-3 text-sm font-bold text-slate-800">Distribución de Horas Pico</h3>
                    <div id="chartHoras"></div>
                </div>
            </div>

            <div class="card">
                <h3 class="mb-3 text-sm font-bold text-slate-800">Top 10 Productos Más Vendidos</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase text-slate-400">
                                <th class="py-2.5">#</th>
                                <th class="py-2.5">Producto</th>
                                <th class="py-2.5 text-center">Unidades</th>
                                <th class="py-2.5 text-right">Total Ingresos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($topProductos as $i => $tp)
                                <tr>
                                    <td class="py-2.5 text-slate-400">{{ $i+1 }}</td>
                                    <td class="py-2.5 font-semibold text-slate-800">{{ $tp->nombre_producto }}</td>
                                    <td class="py-2.5 text-center font-medium text-slate-700">{{ $tp->q }}</td>
                                    <td class="py-2.5 text-right font-bold text-slate-900">{{ $m }} {{ number_format($tp->t,2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-slate-400">Sin datos registrados en el rango seleccionado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 2: Rentabilidad por Plato --}}
        <div x-show="tab === 'rentabilidad'" style="display:none" class="space-y-6">
            <div class="card">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Margen y Rentabilidad por Producto</h3>
                        <p class="text-xs text-slate-500">Calculado a partir de los insumos y recetas de la carta.</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-4">
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-400">Ingresos Totales</p><p class="text-lg font-bold text-slate-800">{{ $m }} {{ number_format($ingresoTotalR,2) }}</p></div>
                    <div class="rounded-xl bg-rose-50 p-3"><p class="text-xs text-rose-500">Costo Insumos</p><p class="text-lg font-bold text-rose-700">{{ $m }} {{ number_format($costoTotalR,2) }}</p></div>
                    <div class="rounded-xl bg-emerald-50 p-3"><p class="text-xs text-emerald-600">Ganancia Neta</p><p class="text-lg font-bold text-emerald-700">{{ $m }} {{ number_format($gananciaTotalR,2) }}</p></div>
                    <div class="rounded-xl bg-brand-50 p-3"><p class="text-xs text-brand-600">Margen Promedio</p><p class="text-lg font-bold text-brand-700">{{ $margenTotalR }}%</p></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="py-2.5 font-semibold">Producto</th>
                                <th class="py-2.5 text-center font-semibold">Unid.</th>
                                <th class="py-2.5 text-right font-semibold">Ingreso Total</th>
                                <th class="py-2.5 text-right font-semibold">Costo Total</th>
                                <th class="py-2.5 text-right font-semibold">Ganancia</th>
                                <th class="py-2.5 text-right font-semibold">Margen %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($rentabilidad as $r)
                                <tr>
                                    <td class="py-2.5 font-semibold text-slate-800">{{ $r->nombre }}</td>
                                    <td class="py-2.5 text-center text-slate-600">{{ $r->unidades }}</td>
                                    <td class="py-2.5 text-right text-slate-700">{{ $m }} {{ number_format($r->ingreso,2) }}</td>
                                    <td class="py-2.5 text-right text-slate-500">{{ $m }} {{ number_format($r->costo_total,2) }}</td>
                                    <td class="py-2.5 text-right font-semibold text-emerald-600">{{ $m }} {{ number_format($r->ganancia,2) }}</td>
                                    <td class="py-2.5 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $r->margen >= 50 ? 'bg-emerald-100 text-emerald-800' : ($r->margen >= 20 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                            {{ $r->margen }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-6 text-center text-slate-400">Sin datos de rentabilidad disponibles.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 3: Colaboradores / Meseros --}}
        <div x-show="tab === 'colaboradores'" style="display:none" class="space-y-6">
            <div class="card">
                <h3 class="mb-4 text-sm font-bold text-slate-800">Desempeño de Personal (Meseros y Cajeros)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase text-slate-400">
                                <th class="py-2.5">Colaborador</th>
                                <th class="py-2.5">Rol</th>
                                <th class="py-2.5 text-center">Pedidos Atendidos</th>
                                <th class="py-2.5 text-right">Ticket Promedio</th>
                                <th class="py-2.5 text-right font-bold">Total Facturado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($porMesero as $pm)
                                <tr>
                                    <td class="py-3 font-semibold text-slate-800">{{ $pm->nombre }}</td>
                                    <td class="py-3"><span class="badge bg-slate-100 text-slate-700 capitalize">{{ $pm->rol }}</span></td>
                                    <td class="py-3 text-center text-slate-700 font-medium">{{ $pm->pedidos_count }}</td>
                                    <td class="py-3 text-right text-slate-600">{{ $m }} {{ number_format($pm->ticket_promedio, 2) }}</td>
                                    <td class="py-3 text-right font-bold text-brand-700">{{ $m }} {{ number_format($pm->total_ventas, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-slate-400">No se encontraron ventas asignadas a colaboradores en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 4: Métodos de Pago --}}
        <div x-show="tab === 'metodos'" style="display:none" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="card">
                    <h3 class="mb-3 text-sm font-bold text-slate-800">Gráfico de Métodos de Pago</h3>
                    <div id="chartMetodo"></div>
                </div>
                <div class="card">
                    <h3 class="mb-3 text-sm font-bold text-slate-800">Detalle por Método</h3>
                    <div class="divide-y divide-slate-100">
                        @foreach ($porMetodo as $pm)
                            @php $pct = $totalVentas > 0 ? round(($pm->t / $totalVentas) * 100, 1) : 0; @endphp
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-800 uppercase text-xs">{{ $pm->metodo_pago ?? 'Sin especificar' }}</p>
                                    <p class="text-xs text-slate-400">{{ $pm->c }} transacciones ({{ $pct }}%)</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-extrabold text-slate-900">{{ $m }} {{ number_format($pm->t, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 5: Canales / Tipos de Pedido --}}
        <div x-show="tab === 'canales'" style="display:none" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($porTipo as $pt)
                    @php 
                        $icono = match($pt->tipo) { 'mesa' => '🍽️ Salón (Mesa)', 'llevar' => '🛍️ Para Llevar', 'delivery' => '🛵 Delivery a Domicilio', default => '📦 Otro' };
                        $pctT = $totalVentas > 0 ? round(($pt->t / $totalVentas) * 100, 1) : 0;
                    @endphp
                    <div class="card border border-slate-200">
                        <p class="text-sm font-bold text-slate-800">{{ $icono }}</p>
                        <p class="mt-3 text-2xl font-black text-slate-900">{{ $m }} {{ number_format($pt->t, 2) }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $pt->c }} pedidos ({{ $pctT }}% del volumen)</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- TAB 6: Inventario y Consumo --}}
        <div x-show="tab === 'inventario'" style="display:none" class="space-y-6">
            <div class="card">
                <h3 class="mb-4 text-sm font-bold text-slate-800">Insumos Más Consumidos en el Período</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase text-slate-400">
                                <th class="py-2.5">Insumo</th>
                                <th class="py-2.5 text-center">Unidad</th>
                                <th class="py-2.5 text-center">Stock Actual</th>
                                <th class="py-2.5 text-center">Cantidad Consumida</th>
                                <th class="py-2.5 text-right font-bold">Costo del Consumo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($insumosConsumo as $ic)
                                <tr>
                                    <td class="py-2.5 font-semibold text-slate-800">{{ $ic->nombre }}</td>
                                    <td class="py-2.5 text-center text-slate-500">{{ $ic->unidad }}</td>
                                    <td class="py-2.5 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $ic->stock <= $ic->stock_minimo ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $ic->stock }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-center font-bold text-slate-800">{{ number_format($ic->consumido, 2) }}</td>
                                    <td class="py-2.5 text-right font-bold text-rose-600">{{ $m }} {{ number_format($ic->costo_consumo, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-slate-400">Sin consumos de inventario registrados en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 7: Clientes y Fidelización --}}
        <div x-show="tab === 'clientes'" style="display:none" class="space-y-6">
            <div class="card">
                <h3 class="mb-4 text-sm font-bold text-slate-800">Top Clientes Frecuentes y Puntos de Fidelidad</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase text-slate-400">
                                <th class="py-2.5">Cliente</th>
                                <th class="py-2.5">Teléfono</th>
                                <th class="py-2.5 text-center">Visitas / Pedidos</th>
                                <th class="py-2.5 text-center">Puntos Ganados</th>
                                <th class="py-2.5 text-center">Puntos Canjeados</th>
                                <th class="py-2.5 text-center">Saldo Actual</th>
                                <th class="py-2.5 text-right font-bold">Consumo Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($topClientes as $tc)
                                <tr>
                                    <td class="py-2.5 font-bold text-slate-800">{{ $tc->nombre }}</td>
                                    <td class="py-2.5 text-slate-500">{{ $tc->telefono ?? '—' }}</td>
                                    <td class="py-2.5 text-center font-semibold text-slate-700">{{ $tc->pedidos_count }}</td>
                                    <td class="py-2.5 text-center text-emerald-600 font-medium">+{{ $tc->puntos_ganados }}</td>
                                    <td class="py-2.5 text-center text-rose-500 font-medium">-{{ $tc->puntos_canjeados }}</td>
                                    <td class="py-2.5 text-center font-extrabold text-brand-600">{{ $tc->puntos }}</td>
                                    <td class="py-2.5 text-right font-bold text-slate-900">{{ $m }} {{ number_format($tc->total_consumido, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-400">Sin datos de clientes en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 8: Auditoría de Cajas --}}
        <div x-show="tab === 'cajas'" style="display:none" class="space-y-6">
            <div class="card">
                <h3 class="mb-4 text-sm font-bold text-slate-800">Historial y Arqueo de Cajas</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase text-slate-400">
                                <th class="py-2.5">Apertura</th>
                                <th class="py-2.5">Cierre</th>
                                <th class="py-2.5">Responsable</th>
                                <th class="py-2.5 text-right">Monto Inicial</th>
                                <th class="py-2.5 text-right">Efectivo Esperado</th>
                                <th class="py-2.5 text-right">Monto Contado</th>
                                <th class="py-2.5 text-right font-bold">Diferencia</th>
                                <th class="py-2.5 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($cierresCaja as $cj)
                                <tr>
                                    <td class="py-2.5 text-slate-600">{{ $cj->apertura_at?->format('d/m/Y H:i') ?? $cj->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-2.5 text-slate-600">{{ $cj->cierre_at?->format('d/m/Y H:i') ?? 'En curso' }}</td>
                                    <td class="py-2.5 font-semibold text-slate-800">{{ $cj->user?->name ?? '—' }}</td>
                                    <td class="py-2.5 text-right text-slate-600">{{ $m }} {{ number_format($cj->monto_inicial, 2) }}</td>
                                    <td class="py-2.5 text-right text-slate-600">{{ $m }} {{ number_format($cj->efectivo_esperado, 2) }}</td>
                                    <td class="py-2.5 text-right font-semibold text-slate-800">{{ $cj->monto_contado !== null ? $m.' '.number_format($cj->monto_contado, 2) : '—' }}</td>
                                    <td class="py-2.5 text-right font-bold {{ $cj->diferencia < 0 ? 'text-rose-600' : ($cj->diferencia > 0 ? 'text-emerald-600' : 'text-slate-700') }}">
                                        {{ $cj->diferencia !== null ? $m.' '.number_format($cj->diferencia, 2) : '—' }}
                                    </td>
                                    <td class="py-2.5 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $cj->estado === 'cerrada' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ ucfirst($cj->estado) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-6 text-center text-slate-400">Sin turnos de caja en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Gráfica de Ventas Diarias
            const dias = @json($porDia->pluck('d'));
            const montos = @json($porDia->pluck('t'));
            if (dias.length && document.querySelector('#chartVentas')) {
                new ApexCharts(document.querySelector('#chartVentas'), {
                    chart: { type: 'area', height: 260, toolbar: { show: false }, zoom: { enabled: false } },
                    stroke: { curve: 'smooth', width: 2.5 },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.45, opacityTo: 0.05 } },
                    colors: ['#ea580c'],
                    series: [{ name: 'Ventas ({{ $m }})', data: montos }],
                    xaxis: { categories: dias, labels: { style: { colors: '#94a3b8', fontSize: '11px' } } },
                    yaxis: { labels: { style: { colors: '#94a3b8', fontSize: '11px' }, formatter: v => '{{ $m }} ' + Number(v).toFixed(0) } },
                    tooltip: { y: { formatter: v => '{{ $m }} ' + Number(v).toFixed(2) } }
                }).render();
            }

            // Gráfica de Horas Pico
            const horas = @json($porHora->pluck('h'));
            const pedidosHora = @json($porHora->pluck('c'));
            if (horas.length && document.querySelector('#chartHoras')) {
                new ApexCharts(document.querySelector('#chartHoras'), {
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    colors: ['#f97316'],
                    series: [{ name: 'Pedidos', data: pedidosHora }],
                    xaxis: { categories: horas.map(h => h + ':00'), labels: { style: { colors: '#94a3b8', fontSize: '11px' } } },
                    yaxis: { labels: { style: { colors: '#94a3b8', fontSize: '11px' } } },
                }).render();
            }

            // Gráfica de Métodos de Pago
            const metodos = @json($porMetodo->pluck('metodo_pago').map(fn($v) => strtoupper($v ?? 'Otro')));
            const montosMetodo = @json($porMetodo->pluck('t'));
            if (metodos.length && document.querySelector('#chartMetodo')) {
                new ApexCharts(document.querySelector('#chartMetodo'), {
                    chart: { type: 'donut', height: 260 },
                    colors: ['#ea580c', '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b'],
                    series: montosMetodo.map(Number),
                    labels: metodos,
                    legend: { position: 'bottom', fontSize: '12px' },
                    tooltip: { y: { formatter: v => '{{ $m }} ' + Number(v).toFixed(2) } }
                }).render();
            }
        });
    </script>
    @endpush
</x-app-layout>
