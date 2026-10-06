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
        <p class="mt-2 text-xs text-orange-100/80">Toca una mesa para tomar la orden. Cocina avisa cuando esté listo.</p>
        <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
            <div class="rounded-xl bg-white/10 py-2">
                <p class="text-lg font-bold" x-text="mesas.length">0</p>
                <p class="text-orange-100/70">Mesas</p>
            </div>
            <div class="rounded-xl bg-white/10 py-2">
                <p class="text-lg font-bold" x-text="porLlevar.length">0</p>
                <p class="text-orange-100/70">Por llevar</p>
            </div>
            <div class="rounded-xl bg-white/10 py-2">
                <p class="text-lg font-bold" x-text="pedidos.length">0</p>
                <p class="text-orange-100/70">Abiertos</p>
            </div>
        </div>
    </header>

    <nav class="sticky top-[148px] z-10 grid grid-cols-2 bg-white text-sm font-semibold shadow-sm">
        <button @click="tab='mesas'; mesa=null" :class="tab==='mesas' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-slate-500'" class="py-3">Mesas</button>
        <button @click="tab='pedidos'; mesa=null" :class="tab==='pedidos' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-slate-500'" class="py-3">Pedidos</button>
    </nav>

    <main class="flex-1 space-y-3 p-4 pb-24">
        <p x-show="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700" x-text="error"></p>
        <p x-show="ok" class="rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-700" x-text="ok"></p>

        <div x-show="tab==='mesas' && !mesa" class="grid grid-cols-2 gap-3">
            <template x-for="m in mesas" :key="m.id">
                <button @click="abrir(m)" class="rounded-2xl bg-white p-3 text-left shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs text-slate-400" x-text="m.zona"></p>
                            <p class="text-xl font-extrabold" x-text="'Mesa '+m.numero"></p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="color(m.estado_salon)" x-text="etiqueta(m.estado_salon)"></span>
                    </div>
                    <p class="mt-2 text-xs text-slate-500" x-show="m.pedido" x-text="m.pedido?.codigo"></p>
                    <p class="mt-2 text-xs text-slate-400" x-show="!m.pedido">Sin pedido</p>
                </button>
            </template>
            <p x-show="!mesas.length" class="col-span-2 py-10 text-center text-sm text-slate-400">Aún no te asignaron mesas.</p>
        </div>

        <div x-show="mesa" class="space-y-3">
            <button @click="mesa=null" class="text-sm font-semibold text-brand-700">← Volver a mesas</button>
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-extrabold" x-text="'Mesa '+mesa?.numero"></h2>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="color(mesa?.estado_salon)" x-text="etiqueta(mesa?.estado_salon)"></span>
                </div>
                <p class="mt-1 text-xs text-slate-500" x-show="mesa?.pedido">Cuenta abierta: <span x-text="'$ '+Number(mesa?.pedido?.total||0).toFixed(2)"></span></p>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-1">
                <button @click="cat=0" :class="cat===0 ? 'bg-brand-600 text-white' : 'bg-white text-slate-600'" class="shrink-0 rounded-full px-3 py-1.5 text-xs font-bold">Todo</button>
                <template x-for="c in categorias" :key="c.id">
                    <button @click="cat=c.id" :class="cat===c.id ? 'bg-brand-600 text-white' : 'bg-white text-slate-600'" class="shrink-0 rounded-full px-3 py-1.5 text-xs font-bold" x-text="c.nombre"></button>
                </template>
            </div>

            <template x-for="p in filtrados" :key="p.id">
                <div class="flex items-center justify-between rounded-2xl bg-white px-3 py-3 shadow-sm">
                    <div>
                        <p class="text-sm font-bold" x-text="p.nombre"></p>
                        <p class="text-xs text-slate-500" x-text="'$ '+Number(p.precio).toFixed(2)"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="sumar(p, -1)" class="h-8 w-8 rounded-full bg-slate-100 font-bold">−</button>
                        <span class="w-5 text-center text-sm font-bold" x-text="cant(p.id)"></span>
                        <button @click="sumar(p, 1)" class="h-8 w-8 rounded-full bg-brand-600 font-bold text-white">+</button>
                    </div>
                </div>
            </template>

            <label class="block text-xs font-semibold text-slate-500">Nota para cocina
                <input x-model="nota" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Sin cebolla, término medio…">
            </label>

            <button @click="enviar()" :disabled="enviando || !carrito.length" class="w-full rounded-xl bg-brand-600 py-3 text-sm font-bold text-white disabled:opacity-40">
                Enviar a cocina · <span x-text="carrito.length"></span>
            </button>
            <div class="grid grid-cols-2 gap-2">
                <button @click="pedirCuenta()" :disabled="!mesa?.pedido" class="rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white disabled:opacity-40">Pedir cuenta</button>
                <button @click="verTicket()" :disabled="!mesa?.pedido" class="rounded-xl bg-slate-800 py-3 text-sm font-bold text-white disabled:opacity-40">Ver ticket</button>
            </div>
        </div>

        <div x-show="tab==='pedidos' && !mesa" class="space-y-3">
            <template x-for="p in pedidos" :key="p.id">
                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold" x-text="p.codigo"></p>
                            <p class="text-xs text-slate-500">Mesa <span x-text="p.mesa || '—'"></span> · <span x-text="p.minutos"></span> min</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="color(p.entregado ? 'llevado' : p.estado)" x-text="p.entregado ? 'llevado' : p.estado"></span>
                    </div>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        <template x-for="(it, i) in p.items" :key="i">
                            <li>
                                <span class="font-semibold" x-text="it.cantidad+'×'"></span>
                                <span x-text="it.nombre"></span>
                                <span x-show="it.notas" class="block text-xs text-slate-400" x-text="it.notas"></span>
                            </li>
                        </template>
                    </ul>
                    <p class="mt-2 text-right text-sm font-bold" x-text="'$ '+Number(p.total||0).toFixed(2)"></p>
                    <p x-show="p.estado!=='servido' && !p.entregado" class="mt-2 text-xs text-slate-400">Esperando a cocina…</p>
                    <button x-show="p.puede_llevar" @click="llevar(p)" class="mt-3 w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-bold text-white">Ya lo llevé a la mesa</button>
                    <button @click="window.open('/mesero/pedidos/'+p.id+'/ticket', '_blank')" class="mt-2 w-full rounded-lg bg-slate-100 py-2 text-sm font-semibold text-slate-700">Ticket 80 mm</button>
                </article>
            </template>
            <p x-show="!pedidos.length" class="py-10 text-center text-sm text-slate-400">No hay pedidos abiertos en tus mesas.</p>
        </div>
    </main>
</div>

<script>
function meseroApp() {
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    return {
        tab: 'mesas',
        mesas: [],
        pedidos: [],
        categorias: [],
        productos: [],
        cat: 0,
        mesa: null,
        carrito: [],
        nota: '',
        error: '',
        ok: '',
        enviando: false,
        timer: null,
        get porLlevar() { return this.pedidos.filter(p => p.puede_llevar); },
        get filtrados() {
            return this.productos.filter(p => !this.cat || p.categoria_id === this.cat);
        },
        etiqueta(e) {
            return { libre:'Libre', esperando:'Esperando', listo:'Listo', ocupada:'Ocupada', cuenta:'Para cobrar', reservada:'Reservada', llevado:'Llevado', pendiente:'Pendiente', preparando:'Cocinando', servido:'Listo' }[e] || e || '';
        },
        color(e) {
            return {
                libre:'bg-emerald-50 text-emerald-700',
                esperando:'bg-amber-50 text-amber-700',
                listo:'bg-sky-50 text-sky-700',
                ocupada:'bg-rose-50 text-rose-700',
                cuenta:'bg-indigo-50 text-indigo-700',
                reservada:'bg-amber-50 text-amber-700',
                llevado:'bg-slate-100 text-slate-600',
                pendiente:'bg-amber-100 text-amber-800',
                preparando:'bg-orange-100 text-orange-800',
                servido:'bg-emerald-100 text-emerald-800'
            }[e] || 'bg-slate-100 text-slate-600';
        },
        async load() {
            try {
                const r = await fetch('/mesero/live', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!r.ok) throw new Error('No se pudo leer el salón');
                const data = await r.json();
                this.mesas = data.mesas;
                this.pedidos = data.pedidos;
                if (this.mesa) this.mesa = this.mesas.find(m => m.id === this.mesa.id) || this.mesa;
                this.error = '';
            } catch (e) {
                this.error = e.message || 'Error de conexión';
            }
        },
        async abrir(m) {
            this.mesa = m;
            this.carrito = [];
            this.nota = '';
            this.ok = '';
            if (!this.productos.length) {
                const r = await fetch('/mesero/carta', { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                this.categorias = data.categorias;
                this.productos = data.productos;
            }
        },
        cant(id) { return this.carrito.find(i => i.id === id)?.cantidad || 0; },
        sumar(p, n) {
            let i = this.carrito.find(x => x.id === p.id);
            if (!i && n > 0) this.carrito.push({ id: p.id, nombre: p.nombre, cantidad: 1 });
            else if (i) {
                i.cantidad += n;
                if (i.cantidad <= 0) this.carrito = this.carrito.filter(x => x.id !== p.id);
            }
        },
        async enviar() {
            this.enviando = true; this.ok = ''; this.error = '';
            try {
                const r = await fetch('/mesero/pedir', {
                    method: 'POST',
                    headers: { 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ mesa_id: this.mesa.id, notas: this.nota, items: this.carrito.map(i => ({ id: i.id, cantidad: i.cantidad })) })
                });
                const data = await r.json();
                if (!r.ok) throw new Error(data.message || data.mensaje || 'No se pudo enviar');
                this.ok = 'Enviado a cocina: ' + data.pedido.codigo;
                this.carrito = []; this.nota = '';
                await this.load();
            } catch (e) { this.error = e.message; }
            this.enviando = false;
        },
        async pedirCuenta() {
            this.ok = ''; this.error = '';
            const r = await fetch('/mesero/mesas/'+this.mesa.id+'/cuenta', { method:'POST', headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': csrf } });
            const data = await r.json();
            if (!r.ok) { this.error = data.mensaje || 'No se pudo pedir la cuenta'; return; }
            this.ok = 'Mesa marcada para cobrar. Caja ya la ve.';
            this.load();
        },
        verTicket() {
            if (this.mesa?.pedido) window.open('/mesero/pedidos/'+this.mesa.pedido.id+'/ticket', '_blank');
        },
        async llevar(p) {
            this.ok = ''; this.error = '';
            try {
                const r = await fetch('/mesero/pedidos/'+p.id+'/llevar', { method:'POST', headers:{ 'Accept':'application/json', 'X-CSRF-TOKEN': csrf } });
                const data = await r.json();
                if (!r.ok) throw new Error(data.mensaje || 'No se pudo marcar');
                this.ok = 'Marcado: ya lo llevaste a la mesa ' + (p.mesa || '');
                this.load();
            } catch (e) { this.error = e.message; }
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
