<x-app-layout title="Gestión de Delivery">
    @php $m = $config->moneda; @endphp

    <x-page-header title="Gestión de Delivery & Envíos a Domicilio" subtitle="Despacho de pedidos propios y sincronización en tiempo real con Rappi y Uber Eats.">
        <div class="flex flex-wrap items-center gap-2">
            {{-- Botones de simulación para pruebas rápidas --}}
            <form method="POST" action="{{ route('delivery.simular') }}" class="inline">
                @csrf
                <input type="hidden" name="plataforma" value="rappi">
                <button type="submit" class="rounded-xl border border-orange-300 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 hover:bg-orange-100 transition-colors shadow-sm">
                    🟠 + Simular Pedido Rappi
                </button>
            </form>
            <form method="POST" action="{{ route('delivery.simular') }}" class="inline">
                @csrf
                <input type="hidden" name="plataforma" value="ubereats">
                <button type="submit" class="rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition-colors shadow-sm">
                    🟢 + Simular Pedido Uber Eats
                </button>
            </form>
            <a href="{{ route('pos.index') }}" class="btn-primary">➕ Nuevo Pedido POS</a>
        </div>
    </x-page-header>

    {{-- Filtro por Plataforma --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="text-xs font-bold text-slate-500 mr-1">Canal de Venta:</span>
        <a href="{{ route('delivery.index', ['estado' => $estado, 'plataforma' => 'todos']) }}"
           class="rounded-xl px-3 py-1.5 text-xs font-bold transition-all {{ $plataforma === 'todos' ? 'bg-slate-800 text-white shadow' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            Todos ({{ $conteo['todos'] }})
        </a>
        <a href="{{ route('delivery.index', ['estado' => $estado, 'plataforma' => 'directo']) }}"
           class="rounded-xl px-3 py-1.5 text-xs font-bold transition-all {{ $plataforma === 'directo' ? 'bg-brand-600 text-white shadow' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            🛵 Directo / Local ({{ $conteo['directo'] }})
        </a>
        <a href="{{ route('delivery.index', ['estado' => $estado, 'plataforma' => 'rappi']) }}"
           class="rounded-xl px-3 py-1.5 text-xs font-bold transition-all {{ $plataforma === 'rappi' ? 'bg-orange-600 text-white shadow' : 'bg-white text-orange-700 border border-orange-200 hover:bg-orange-50' }}">
            🟠 Rappi ({{ $conteo['rappi'] }})
        </a>
        <a href="{{ route('delivery.index', ['estado' => $estado, 'plataforma' => 'ubereats']) }}"
           class="rounded-xl px-3 py-1.5 text-xs font-bold transition-all {{ $plataforma === 'ubereats' ? 'bg-emerald-600 text-white shadow' : 'bg-white text-emerald-800 border border-emerald-200 hover:bg-emerald-50' }}">
            🟢 Uber Eats ({{ $conteo['ubereats'] }})
        </a>
    </div>

    {{-- Tarjetas de estado rápido --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <a href="{{ route('delivery.index', ['estado' => 'todos', 'plataforma' => $plataforma]) }}" class="card border transition-all hover:border-slate-400 {{ $estado === 'todos' ? 'ring-2 ring-brand-500 bg-brand-50/20' : '' }}">
            <p class="text-xs uppercase font-bold text-slate-400">Total Envíos</p>
            <p class="mt-2 text-2xl font-black text-slate-800">{{ $conteo['todos'] }}</p>
        </a>
        <a href="{{ route('delivery.index', ['estado' => 'pendiente', 'plataforma' => $plataforma]) }}" class="card border transition-all hover:border-amber-400 {{ $estado === 'pendiente' ? 'ring-2 ring-amber-500 bg-amber-50/30' : '' }}">
            <p class="text-xs uppercase font-bold text-amber-600">⏳ Por Despachar</p>
            <p class="mt-2 text-2xl font-black text-amber-600">{{ $conteo['pendiente'] }}</p>
        </a>
        <a href="{{ route('delivery.index', ['estado' => 'en_camino', 'plataforma' => $plataforma]) }}" class="card border transition-all hover:border-blue-400 {{ $estado === 'en_camino' ? 'ring-2 ring-blue-500 bg-blue-50/30' : '' }}">
            <p class="text-xs uppercase font-bold text-blue-600">🛵 En Camino</p>
            <p class="mt-2 text-2xl font-black text-blue-600">{{ $conteo['en_camino'] }}</p>
        </a>
        <a href="{{ route('delivery.index', ['estado' => 'entregado', 'plataforma' => $plataforma]) }}" class="card border transition-all hover:border-emerald-400 {{ $estado === 'entregado' ? 'ring-2 ring-emerald-500 bg-emerald-50/30' : '' }}">
            <p class="text-xs uppercase font-bold text-emerald-600">✅ Entregados</p>
            <p class="mt-2 text-2xl font-black text-emerald-600">{{ $conteo['entregado'] }}</p>
        </a>
    </div>

    {{-- Listado de Pedidos de Delivery --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/70 text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Canal / Pedido</th>
                        <th class="py-3 px-4">Cliente / Contacto</th>
                        <th class="py-3 px-4">Destino / Entrega</th>
                        <th class="py-3 px-4">Platillos</th>
                        <th class="py-3 px-4">Repartidor</th>
                        <th class="py-3 px-4 text-right">Total</th>
                        <th class="py-3 px-4 text-center">Estado Delivery</th>
                        <th class="py-3 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pedidos as $p)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            {{-- Canal / Pedido / Fecha --}}
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1.5 mb-1">
                                    @if($p->delivery_plataforma === 'rappi')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-orange-100 text-orange-800 border border-orange-200">
                                            🟠 Rappi
                                        </span>
                                    @elseif($p->delivery_plataforma === 'ubereats')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-900 border border-emerald-200">
                                            🟢 Uber Eats
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                            🛵 Directo
                                        </span>
                                    @endif
                                    @if($p->delivery_externo_id)
                                        <span class="font-mono text-[10px] text-slate-400">#{{ $p->delivery_externo_id }}</span>
                                    @endif
                                </div>
                                <a href="{{ route('pedidos.show', $p) }}" class="font-bold text-brand-700 hover:underline">{{ $p->codigo }}</a>
                                <p class="text-xs text-slate-400">{{ $p->created_at->format('d/m H:i') }}</p>
                            </td>

                            {{-- Cliente --}}
                            <td class="py-3 px-4">
                                <p class="font-semibold text-slate-800">{{ $p->cliente?->nombre ?? 'Público General' }}</p>
                                @php $tel = $p->delivery_telefono ?? $p->cliente?->telefono; @endphp
                                @if($tel)
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-xs text-slate-500">{{ $tel }}</span>
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $tel) }}" target="_blank" class="text-emerald-600 hover:text-emerald-700" title="Contactar por WhatsApp">
                                            💬
                                        </a>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Sin teléfono</span>
                                @endif
                            </td>

                            {{-- Dirección --}}
                            <td class="py-3 px-4 max-w-xs">
                                <p class="font-medium text-slate-800 truncate" title="{{ $p->delivery_direccion }}">{{ $p->delivery_direccion ?? 'No especificada' }}</p>
                                @if($p->delivery_referencia)
                                    <p class="text-xs text-slate-500 truncate" title="{{ $p->delivery_referencia }}">Ref: {{ $p->delivery_referencia }}</p>
                                @endif
                                @if($p->delivery_direccion)
                                    <a href="https://maps.google.com/?q={{ urlencode($p->delivery_direccion) }}" target="_blank" class="text-[11px] text-blue-600 hover:underline">
                                        📍 Ver en Mapa
                                    </a>
                                @endif
                            </td>

                            {{-- Platillos / Resumen Items --}}
                            <td class="py-3 px-4 max-w-xs">
                                <div class="space-y-0.5 text-xs text-slate-600">
                                    @foreach($p->items->take(2) as $it)
                                        <p class="truncate"><span class="font-bold">{{ $it->cantidad }}x</span> {{ $it->nombre_producto }}</p>
                                    @endforeach
                                    @if($p->items->count() > 2)
                                        <p class="text-[11px] text-slate-400">+{{ $p->items->count() - 2 }} más...</p>
                                    @endif
                                </div>
                            </td>

                            {{-- Repartidor --}}
                            <td class="py-3 px-4">
                                <span class="text-xs font-semibold text-slate-700">{{ $p->delivery_repartidor ?: '— Sin asignar —' }}</span>
                            </td>

                            {{-- Total y Pago --}}
                            <td class="py-3 px-4 text-right">
                                <p class="font-extrabold text-slate-900">{{ $m }} {{ number_format($p->total, 2) }}</p>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $p->estado === 'pagado' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $p->estado === 'pagado' ? 'Pagado' : 'Cobrar' }}
                                </span>
                            </td>

                            {{-- Estado del Delivery --}}
                            <td class="py-3 px-4 text-center">
                                @php
                                    $est = $p->delivery_estado ?? 'pendiente';
                                    $colores = match($est) {
                                        'en_camino' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'entregado' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'cancelado' => 'bg-rose-100 text-rose-800 border-rose-200',
                                        default => 'bg-amber-100 text-amber-800 border-amber-200'
                                    };
                                    $texto = match($est) {
                                        'en_camino' => '🛵 En camino',
                                        'entregado' => '✅ Entregado',
                                        'cancelado' => '❌ Cancelado',
                                        default => '⏳ Por salir'
                                    };
                                @endphp
                                <span class="inline-flex items-center border px-2.5 py-1 rounded-full text-xs font-bold {{ $colores }}">
                                    {{ $texto }}
                                </span>
                            </td>

                            {{-- Acciones y Cambio de Estado --}}
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    {{-- Ticket térmico --}}
                                    <a href="{{ route('pedidos.ticket', $p) }}" target="_blank" class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-100" title="Imprimir Ticket">
                                        🖨️
                                    </a>

                                    {{-- Botón rápido: despachar / en camino --}}
                                    @if(($p->delivery_estado ?? 'pendiente') === 'pendiente')
                                        <form method="POST" action="{{ route('delivery.estado', $p) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="delivery_estado" value="en_camino">
                                            <button type="submit" class="rounded-lg bg-blue-600 px-2 py-1 text-xs font-bold text-white hover:bg-blue-700" title="Despachar pedido">
                                                Despachar
                                            </button>
                                        </form>
                                    @elseif($p->delivery_estado === 'en_camino')
                                        <form method="POST" action="{{ route('delivery.estado', $p) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="delivery_estado" value="entregado">
                                            <input type="hidden" name="marcar_pagado" value="1">
                                            <button type="submit" class="rounded-lg bg-emerald-600 px-2 py-1 text-xs font-bold text-white hover:bg-emerald-700" title="Marcar como entregado y cobrado">
                                                Entregado
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <span class="text-4xl">🛵</span>
                                <p class="mt-2 text-sm">No se encontraron pedidos de delivery con el filtro seleccionado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pedidos->hasPages())
            <div class="border-t border-slate-200 p-4">
                {{ $pedidos->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
