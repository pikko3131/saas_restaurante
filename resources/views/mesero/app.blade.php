<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#9a3412">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Mi salón · {{ auth()->user()->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900" x-data="meseroApp()" x-init="init()">
<div class="mx-auto flex min-h-screen max-w-lg flex-col">
    <header class="sticky top-0 z-20 bg-gradient-to-r from-brand-800 to-brand-950 px-4 pb-4 pt-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-orange-200/80">Hola</p>
                <h1 class="text-lg font-extrabold leading-tight">{{ auth()->user()->name }}</h1>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold">Salir</button>
            </form>
        </div>
        <p class="mt-2 text-xs text-orange-100/80">
            Solo ves <strong>tus mesas</strong> y los pedidos de esas mesas.
            <span class="ml-1 inline-flex items-center gap-1">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                en vivo
            </span>
        </p>
        <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
            <div class="rounded-xl bg-white/10 py-2">
                <p class="text-lg font-bold" x-text="mesas.length">0</p>
                <p class="text-orange-100/70">Mesas</p>
            </div>
            <div class="rounded-xl bg-white/10 py-2">
                <p class="text-lg font-bold" x-text="ocupadas">0</p>
                <p class="text-orange-100/70">Ocupadas</p>
            </div>
            <div class="rounded-xl bg-white/10 py-2">
                <p class="text-lg font-bold" x-text="pedidos.length">0</p>
                <p class="text-orange-100/70">Pedidos</p>
            </div>
        </div>
    </header>

    <nav class="sticky top-[148px] z-10 grid grid-cols-2 bg-white text-sm font-semibold shadow-sm">
        <button @click="tab='mesas'" :class="tab==='mesas' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-slate-500'" class="py-3">Mesas</button>
        <button @click="tab='pedidos'" :class="tab==='pedidos' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-slate-500'" class="py-3">Pedidos</button>
    </nav>

    <main class="flex-1 space-y-3 p-4">
        <p x-show="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700" x-text="error"></p>

        <div x-show="tab==='mesas'" class="grid grid-cols-2 gap-3">
            <template x-for="m in mesas" :key="m.id">
                <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-slate-400" x-text="m.zona"></p>
                            <p class="text-xl font-extrabold" x-text="'Mesa '+m.numero"></p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                              :class="{
                                  'bg-emerald-50 text-emerald-700': m.estado==='libre',
                                  'bg-rose-50 text-rose-700': m.estado==='ocupada',
                                  'bg-amber-50 text-amber-700': m.estado==='reservada',
                                  'bg-indigo-50 text-indigo-700': m.estado==='cuenta'
                              }"
                              x-text="m.estado"></span>
                    </div>
                    <p class="mt-2 text-xs text-slate-500" x-show="m.pedido">
                        <span x-text="m.pedido?.codigo"></span>
                        · <span x-text="m.pedido?.estado"></span>
                    </p>
                    <p class="mt-2 text-xs text-slate-400" x-show="!m.pedido">Sin pedido activo</p>
                </div>
            </template>
            <p x-show="!mesas.length" class="col-span-2 py-10 text-center text-sm text-slate-400">
                Aún no te asignaron mesas. Pídele al admin que te asigne mesas en el módulo Mesas.
            </p>
        </div>

        <div x-show="tab==='pedidos'" class="space-y-3">
            <template x-for="p in pedidos" :key="p.id">
                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold" x-text="p.codigo"></p>
                            <p class="text-xs text-slate-500">Mesa <span x-text="p.mesa || '—'"></span> · <span x-text="p.minutos"></span> min</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase" x-text="p.estado"></span>
                    </div>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        <template x-for="(it, i) in p.items" :key="i">
                            <li><span class="font-semibold" x-text="it.cantidad+'×'"></span> <span x-text="it.nombre"></span></li>
                        </template>
                    </ul>
                    <p class="mt-2 text-right text-sm font-bold" x-text="'S/ '+Number(p.total||0).toFixed(2)"></p>
                </article>
            </template>
            <p x-show="!pedidos.length" class="py-10 text-center text-sm text-slate-400">No hay pedidos abiertos en tus mesas.</p>
        </div>
    </main>
</div>

<script>
function meseroApp() {
    return {
        tab: 'mesas',
        mesas: [],
        pedidos: [],
        error: '',
        timer: null,
        get ocupadas() {
            return this.mesas.filter(m => m.estado === 'ocupada' || m.pedido).length;
        },
        async load() {
            try {
                const r = await fetch(@json(route('mesero.live')), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!r.ok) throw new Error('No se pudo leer el salón');
                const data = await r.json();
                this.mesas = data.mesas;
                this.pedidos = data.pedidos;
                this.error = '';
            } catch (e) {
                this.error = e.message || 'Error de conexión';
            }
        },
        init() {
            this.load();
            this.timer = setInterval(() => this.load(), 4000);
        }
    }
}
</script>
</body>
</html>
