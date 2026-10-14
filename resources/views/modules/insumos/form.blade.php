<x-app-layout :title="$insumo->exists ? 'Editar insumo' : 'Nuevo insumo'">
    <x-page-header :title="$insumo->exists ? 'Editar insumo' : 'Nuevo insumo'">
        <a href="{{ route('insumos.index') }}" class="btn-secondary">← Volver</a>
    </x-page-header>
    <div class="card max-w-2xl">
        <x-val-errors />
        <form method="POST" enctype="multipart/form-data" action="{{ $insumo->exists ? route('insumos.update', $insumo) : route('insumos.store') }}" class="space-y-5">
            @csrf
            @if($insumo->exists) @method('PUT') @endif
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nombre *</label>
                    <input name="nombre" value="{{ old('nombre', $insumo->nombre) }}" class="form-input-c" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Descripción</label>
                    <textarea name="descripcion" rows="2" class="form-input-c">{{ old('descripcion', $insumo->descripcion) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Foto</label>
                    <input type="file" name="imagen" accept="image/*" class="form-input-c">
                    @if($insumo->imagen)
                        <img src="{{ asset('storage/'.$insumo->imagen) }}" class="mt-2 h-20 rounded-lg object-cover">
                    @endif
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Unidad *</label>
                    <input name="unidad" value="{{ old('unidad', $insumo->unidad ?? 'g') }}" class="form-input-c" list="unidades" required>
                    <datalist id="unidades"><option>g</option><option>kg</option><option>ml</option><option>lt</option><option>unidad</option><option>caja</option></datalist>
                    <p class="mt-1 text-xs text-slate-400">Usa siempre la misma. Si aquí pones g, en la receta también van gramos.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Costo por unidad</label>
                    <input type="number" step="0.01" name="costo" value="{{ old('costo', $insumo->costo) }}" class="form-input-c">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Stock actual *</label>
                    <input type="number" step="0.001" name="stock" value="{{ old('stock', $insumo->stock ?? 0) }}" class="form-input-c" required>
                    <p class="mt-1 text-xs text-slate-400">Ej. 3 kg = 3000 si la unidad es g.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Stock mínimo *</label>
                    <input type="number" step="0.001" name="stock_minimo" value="{{ old('stock_minimo', $insumo->stock_minimo ?? 0) }}" class="form-input-c" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Proveedor</label>
                    <input name="proveedor" value="{{ old('proveedor', $insumo->proveedor) }}" class="form-input-c">
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('insumos.index') }}" class="btn-secondary">Cancelar</a>
                <button class="btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</x-app-layout>
