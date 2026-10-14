<x-app-layout title="Cocina (KDS)">
    <div x-data="kds()" x-init="init()" class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">Comandas a Cocina</h1>
                <p class="text-sm text-slate-500">Toca un platillo para marcarlo. Al quedar todos listos, suena el celular del mesero.</p>
            </div>
            <button @click="load()" class="btn-secondary">Actualizar</button>
        </div>

        <div class="flex gap-2 overflow-x-auto pb-1" x-show="data.conteo.length">
            <template x-for="c in data.conteo" :key="c.nombre">
                <span class="shrink-0 rounded-full bg-slate-900 px-3 py-1 text-xs font-bold text-white">
                    <span x-text="c.cantidad"></span>× <span x-text="c.nombre"></span>
                </span>
            </template>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <section class="rounded-2xl bg-amber-50/70 p-3">
                <h2 class="mb-2 px-1 text-sm font-bold uppercase text-amber-700">Pendientes · <span x-text="data.pendientes.length"></span></h2>
                <template x-for="p in data.pendientes" :key="p.id"><div x-html="card(p, 'empezar')"></div></template>
                <p x-show="!data.pendientes.length" class="py-8 text-center text-sm text-amber-700/60">Sin comandas.</p>
            </section>
            <section class="rounded-2xl bg-orange-50/70 p-3">
                <h2 class="mb-2 px-1 text-sm font-bold uppercase text-brand-700">Preparando · <span x-text="data.preparando.length"></span></h2>
                <template x-for="p in data.preparando" :key="p.id"><div x-html="card(p, 'listo')"></div></template>
                <p x-show="!data.preparando.length" class="py-8 text-center text-sm text-brand-700/50">Nada en preparación.</p>
            </section>
            <section class="rounded-2xl bg-emerald-50/70 p-3">
                <h2 class="mb-2 px-1 text-sm font-bold uppercase text-emerald-700">Listos · <span x-text="data.listos.length"></span></h2>
                <template x-for="p in data.listos" :key="p.id"><div x-html="card(p, 'recall')"></div></template>
                <p x-show="!data.listos.length" class="py-8 text-center text-sm text-emerald-700/50">Aún no hay listos.</p>
            </section>
        </div>
    </div>
    @push('scripts')
    <script>
        function kds() {
            return {
                data: { pendientes: [], preparando: [], listos: [], conteo: [] },
                prev: null,
                csrf: document.querySelector('meta[name="csrf-token"]').content,
                init() { this.load(); setInterval(() => this.load(), 6000); },
                beep() {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const o = ctx.createOscillator(); const g = ctx.createGain();
                        o.type = 'square'; o.frequency.value = 880; o.connect(g); g.connect(ctx.destination);
                        g.gain.setValueAtTime(0.08, ctx.currentTime); o.start(); o.stop(ctx.currentTime + 0.18);
                    } catch (e) {}
                },
                async load() {
                    const r = await fetch('/cocina/data', { headers: { Accept: 'application/json' } });
                    const data = await r.json();
                    const n = (data.pendientes || []).length;
                    if (this.prev !== null && n > this.prev) this.beep();
                    this.prev = n;
                    this.data = data;
                },
                async post(url) {
                    await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' } });
                    this.load();
                },
                nota(t) {
                    if (!t) return '';
                    const alerta = /alerg|sin |extra|t[eé]rmino|picante|poco hecho|bien hecho/i.test(t);
                    return `<p class="${alerta ? 'mt-1 rounded-lg bg-rose-100 px-2 py-1 text-sm font-extrabold text-rose-700' : 'mt-1 text-xs text-slate-500'}">📝 ${t}</p>`;
                },
                card(p, modo) {
                    const dest = p.mesa ? `Mesa ${p.mesa}` : (p.tipo === 'delivery' ? 'Delivery' : 'Para llevar');
                    const color = p.minutos >= 15 ? 'bg-rose-100 text-rose-700' : (p.minutos >= 8 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');
                    const items = p.items.map(i => `
                        <button onclick="document.querySelector('[x-data]').__x.$data.post('/cocina/items/${i.id}/listo')" class="flex w-full items-start gap-2 py-1 text-left text-sm ${i.listo ? 'opacity-40 line-through' : ''}">
                            <span class="mt-0.5 flex h-5 min-w-[20px] items-center justify-center rounded ${i.listo ? 'bg-emerald-600' : 'bg-brand-600'} px-1 text-xs font-bold text-white">${i.cantidad}</span>
                            <span class="flex-1">${i.nombre}${this.nota(i.notas)}</span>
                        </button>`).join('');
                    const pri = p.prioridad ? '<span class="ml-2 rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">URGENTE</span>' : '';
                    let acc = '';
                    if (modo === 'empezar') acc = `<button onclick="document.querySelector('[x-data]').__x.$data.post('/cocina/${p.id}/avanzar')" class="mt-3 w-full rounded-lg bg-brand-600 py-2 text-sm font-semibold text-white">Empezar</button>`;
                    if (modo === 'listo') acc = `<button onclick="document.querySelector('[x-data]').__x.$data.post('/cocina/${p.id}/avanzar')" class="mt-3 w-full rounded-lg bg-emerald-600 py-2 text-sm font-semibold text-white">Todo listo</button>`;
                    if (modo === 'recall') acc = `<button onclick="document.querySelector('[x-data]').__x.$data.post('/cocina/${p.id}/recall')" class="mt-3 w-full rounded-lg bg-slate-700 py-2 text-sm font-semibold text-white">Deshacer</button>`;
                    return `<article class="mb-3 rounded-xl bg-white p-4 shadow-sm ${p.prioridad ? 'ring-2 ring-rose-400' : 'ring-1 ring-slate-100'}">
                        <div class="flex items-center justify-between">
                            <div><p class="text-xs font-mono text-slate-400">${p.codigo}</p><p class="font-extrabold text-slate-800">${dest}${pri}</p></div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold ${color}">${p.minutos} min</span>
                        </div>
                        <div class="mt-2 divide-y divide-slate-50">${items}</div>
                        ${this.nota(p.notas)}
                        <div class="mt-2 flex gap-2">
                            <button onclick="document.querySelector('[x-data]').__x.$data.post('/cocina/${p.id}/prioridad')" class="flex-1 rounded-lg bg-rose-50 py-1.5 text-xs font-bold text-rose-700">${p.prioridad ? 'Quitar urgente' : 'Urgente'}</button>
                            <button onclick="if(confirm('¿Cancelar esta comanda?')) document.querySelector('[x-data]').__x.$data.post('/cocina/${p.id}/cancelar')" class="flex-1 rounded-lg bg-slate-100 py-1.5 text-xs font-bold text-slate-600">Cancelar</button>
                        </div>
                        ${acc}
                    </article>`;
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
