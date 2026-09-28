<x-app-layout title="Dashboard">
    @php $m = $config->moneda; @endphp

    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">Bienvenido de vuelta, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
            <p class="mt-1 text-sm text-slate-500 capitalize">{{ \Illuminate\Support\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
        </div>
        <a href="{{ route('pos.index') }}" class="btn-primary">+ Nueva venta</a>
    </div>

    @php
        $ocupacionPct = $mesasTotal > 0 ? (int) round(min(100, $mesasOcupadas / $mesasTotal * 100)) : 0;
        $ring = fn ($pct) => 138.23 - 138.23 * min(100, max(0, (int) $pct)) / 100;
    @endphp

    {{-- Tarjetas de métricas --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Ventas del día --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 p-5 text-white shadow-lg shadow-brand-600/30">
            <div class="absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20 backdrop-blur">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Ventas del día</p>
                    </div>
                    <p class="truncate text-2xl font-extrabold sm:text-3xl">{{ $m }} {{ number_format($ventasHoy, 2) }}</p>
                    <p class="mt-1 text-xs text-white/70">Pedidos pagados hoy</p>
                </div>
                <div class="relative flex h-16 w-16 shrink-0 items-center justify-center">
                    <svg class="h-16 w-16 -rotate-90" viewBox="0 0 56 56">
                        <circle cx="28" cy="28" r="22" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="6"/>
                        <circle cx="28" cy="28" r="22" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-dasharray="138.23" stroke-dashoffset="{{ $ring($ingresoPct) }}"/>
                    </svg>
                    <span class="absolute text-xs font-bold">{{ (int) $ingresoPct }}%</span>
                </div>
            </div>
        </div>

        {{-- Mesas ocupadas --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 p-5 text-white shadow-lg shadow-orange-500/30">
            <div class="absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20 backdrop-blur">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        </span>
                        <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Mesas ocupadas</p>
                    </div>
                    <p class="text-2xl font-extrabold sm:text-3xl">{{ $mesasOcupadas }}<span class="text-base font-semibold text-white/60">/{{ $mesasTotal }}</span></p>
                    <p class="mt-1 text-xs text-white/70">En servicio ahora</p>
                </div>
                <div class="relative flex h-16 w-16 shrink-0 items-center justify-center">
                    <svg class="h-16 w-16 -rotate-90" viewBox="0 0 56 56">
                        <circle cx="28" cy="28" r="22" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="6"/>
                        <circle cx="28" cy="28" r="22" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-dasharray="138.23" stroke-dashoffset="{{ $ring($ocupacionPct) }}"/>
                    </svg>
                    <span class="absolute text-xs font-bold">{{ $ocupacionPct }}%</span>
                </div>
            </div>
        </div>

        {{-- Pedidos activos --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 to-accent-500 p-5 text-white shadow-lg shadow-accent-500/30">
            <div class="absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20 backdrop-blur">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                        </span>
                        <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Pedidos activos</p>
                    </div>
                    <p class="text-2xl font-extrabold sm:text-3xl">{{ $pedidosActivos }}</p>
                    <p class="mt-1 text-xs text-white/70">Pendiente · Preparando · Servido</p>
                </div>
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
            </div>
        </div>

        {{-- Clientes nuevos --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-accent-500 to-accent-700 p-5 text-white shadow-lg shadow-accent-600/30">
            <div class="absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20 backdrop-blur">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </span>
                        <p class="text-xs font-semibold uppercase tracking-wide text-white/80">Clientes nuevos</p>
                    </div>
                    <p class="text-2xl font-extrabold sm:text-3xl">{{ $clientesNuevos }}</p>
                    <p class="mt-1 text-xs text-white/70">Registrados hoy</p>
                </div>
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-3-6.7"/></svg>
                </span>
            </div>
        </div>
    </div>

    {{-- Progreso meta mensual --}}
    <div class="card mt-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-sm font-bold text-slate-700">Progreso de meta mensual</p>
                <p class="text-xs text-slate-400">{{ $m }} {{ number_format($ventasMes, 2) }} de {{ $m }} {{ number_format($meta, 2) }}</p>
            </div>
            <span class="badge bg-brand-50 text-brand-700">{{ $progresoMeta }}%</span>
        </div>
        <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-500" style="width: {{ $progresoMeta }}%"></div>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="mb-2 flex items-center justify-between">
                <p class="text-sm font-bold text-slate-700">Actividad por hora</p>
                <span class="text-xs text-slate-400">Ventas de hoy</span>
            </div>
            <div id="chartActividad"></div>
        </div>

        <div class="card flex flex-col items-center">
            <p class="mb-1 self-start text-sm font-bold text-slate-700">Ingreso actual</p>
            <div id="chartGauge" class="w-full"></div>
            <p class="-mt-4 text-center text-xs text-slate-400">Avance vs. meta diaria<br>({{ $m }} {{ number_format($ventasHoy, 2) }})</p>
        </div>
    </div>

    {{-- Salones + Más vendidos --}}
    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm font-bold text-slate-700">Estado de salones</p>
                <a href="{{ route('mesas.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Ver todas →</a>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @forelse ($mesas as $mesa)
                    @php
                        $c = $mesa->color_estado;
                        $labels = ['libre'=>'Libre','ocupada'=>'Ocupada','reservada'=>'Reservada','cuenta'=>'Por cobrar'];
                    @endphp
                    <div class="rounded-2xl border border-{{ $c }}-100 bg-{{ $c }}-50 p-4 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-{{ $c }}-100 text-{{ $c }}-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16"/></svg>
                        </div>
                        <p class="mt-2 text-sm font-bold text-slate-700">{{ $mesa->nombre ?? 'Mesa '.$mesa->numero }}</p>
                        <p class="text-[11px] font-semibold text-{{ $c }}-600">{{ $labels[$mesa->estado] ?? $mesa->estado }}</p>
                    </div>
                @empty
                    <p class="col-span-full text-sm text-slate-400">No hay mesas registradas.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <p class="mb-4 text-sm font-bold text-slate-700">Más vendidos</p>
            <div class="space-y-3">
                @forelse ($masVendidos as $i => $prod)
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-600">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-700">{{ $prod->nombre_producto }}</p>
                            <p class="text-xs text-slate-400">{{ $prod->total }} unidades</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Aún no hay ventas registradas.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Pedidos recientes --}}
    <div class="card mt-4">
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm font-bold text-slate-700">Pedidos recientes</p>
            <a href="{{ route('pedidos.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Ver todos →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="pb-2 font-semibold">Código</th>
                        <th class="pb-2 font-semibold">Mesa</th>
                        <th class="pb-2 font-semibold">Atendido por</th>
                        <th class="pb-2 font-semibold">Estado</th>
                        <th class="pb-2 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($pedidosRecientes as $p)
                        <tr>
                            <td class="py-2.5 font-mono text-xs font-semibold text-slate-600">{{ $p->codigo }}</td>
                            <td class="py-2.5 text-slate-600">{{ $p->mesa?->nombre ?? '—' }}</td>
                            <td class="py-2.5 text-slate-600">{{ $p->user?->name ?? '—' }}</td>
                            <td class="py-2.5">
                                <span class="badge bg-{{ $p->color_estado }}-50 text-{{ $p->color_estado }}-600">{{ ucfirst($p->estado) }}</span>
                            </td>
                            <td class="py-2.5 text-right font-bold text-slate-700">{{ $m }} {{ number_format($p->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-slate-400">No hay pedidos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fmt = (v) => '{{ $m }} ' + Number(v).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            new ApexCharts(document.querySelector('#chartActividad'), {
                chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'Ventas', data: @json($serie) }],
                xaxis: { categories: @json($horas), labels: { style: { colors: '#94a3b8' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { style: { colors: '#94a3b8' }, formatter: (v) => Math.round(v) } },
                colors: ['#f59e0b'],
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
                stroke: { curve: 'smooth', width: 3 },
                dataLabels: { enabled: false },
                grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
                tooltip: { y: { formatter: fmt } }
            }).render();

            new ApexCharts(document.querySelector('#chartGauge'), {
                chart: { type: 'radialBar', height: 300, fontFamily: 'inherit' },
                series: [{{ $ingresoPct }}],
                colors: ['#7257f0'],
                plotOptions: { radialBar: {
                    hollow: { size: '62%' },
                    track: { background: '#ede9fe' },
                    dataLabels: {
                        name: { show: true, color: '#94a3b8', fontSize: '13px', offsetY: 22 },
                        value: { show: true, color: '#1e293b', fontSize: '30px', fontWeight: 700, offsetY: -12, formatter: (v) => v + '%' }
                    }
                }},
                fill: { type: 'gradient', gradient: { shade: 'dark', type: 'horizontal', gradientToColors: ['#ff5c97'], stops: [0, 100] } },
                labels: ['de la meta'],
            }).render();
        });
    </script>
    @endpush
</x-app-layout>
