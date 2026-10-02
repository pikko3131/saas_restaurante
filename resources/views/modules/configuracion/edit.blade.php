<x-app-layout title="Configuración">
    <x-page-header title="Configuración del restaurante" subtitle="Datos generales, logotipo y parámetros del negocio." />

    <div class="card max-w-3xl">
        <x-val-errors />
        <form method="POST" action="{{ route('configuracion.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf @method('PUT')

            {{-- Sección Logotipo --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                <label class="mb-2 block text-sm font-semibold text-slate-800">Logotipo del restaurante</label>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-white shadow-sm">
                        @if ($config->logo)
                            <img src="{{ asset('storage/' . $config->logo) }}" alt="Logo" class="h-full w-full object-cover">
                        @else
                            <span class="text-3xl">🍽️</span>
                        @endif
                    </div>
                    <div class="flex-1 space-y-2">
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <p class="text-xs text-slate-500">Formatos recomendados: PNG, JPG o WEBP con fondo transparente. Máx: 2MB.</p>
                        @if ($config->logo)
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-rose-600 cursor-pointer">
                                <input type="checkbox" name="eliminar_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <span>Eliminar logotipo actual</span>
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nombre del restaurante *</label>
                    <input name="nombre_restaurante" value="{{ old('nombre_restaurante', $config->nombre_restaurante) }}" class="form-input-c" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">RUC / NIT / CIF</label>
                    <input name="ruc" value="{{ old('ruc', $config->ruc) }}" class="form-input-c">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Teléfono</label>
                    <input name="telefono" value="{{ old('telefono', $config->telefono) }}" class="form-input-c">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Dirección</label>
                    <input name="direccion" value="{{ old('direccion', $config->direccion) }}" class="form-input-c">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Email de contacto</label>
                    <input type="email" name="email" value="{{ old('email', $config->email) }}" class="form-input-c">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Símbolo de Moneda *</label>
                    <input name="moneda" value="{{ old('moneda', $config->moneda) }}" class="form-input-c" placeholder="S/, $, €" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Impuesto / IGV (%) *</label>
                    <input type="number" step="0.01" name="igv" value="{{ old('igv', $config->igv) }}" class="form-input-c" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Meta mensual de ventas *</label>
                    <input type="number" step="0.01" name="meta_mensual" value="{{ old('meta_mensual', $config->meta_mensual) }}" class="form-input-c" required>
                </div>
            </div>
            <div class="flex justify-end pt-2 border-t border-slate-200">
                <button class="btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</x-app-layout>
