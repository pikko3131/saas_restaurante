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
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">RFC</label>
                    <input name="ruc" value="{{ old('ruc', $config->ruc) }}" class="form-input-c" placeholder="RFC del negocio (opcional)">
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
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">IVA (%)</label>
                    <input type="number" step="0.01" name="igv" value="{{ old('igv', $config->igv) }}" class="form-input-c" placeholder="16" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Meta mensual de ventas *</label>
                    <input type="number" step="0.01" name="meta_mensual" value="{{ old('meta_mensual', $config->meta_mensual) }}" class="form-input-c" required>
                </div>
            </div>

            {{-- Sección Integración Plataformas Delivery --}}
            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 space-y-5">
                <div>
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <span>🛵</span> Integración con Plataformas de Delivery (Rappi & Uber Eats)
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Conecta tu cocina para recibir pedidos automáticamente de Rappi y Uber Eats en México vía Webhooks.</p>
                </div>

                {{-- Rappi --}}
                <div class="rounded-xl border border-orange-200 bg-white p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-orange-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-600 text-white font-black text-xs">R</span>
                            <span class="font-bold text-sm text-slate-800">Rappi México</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="rappi_activo" value="1" @checked(old('rappi_activo', $config->rappi_activo)) class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-500"></div>
                            <span class="ml-2 text-xs font-semibold text-slate-600">Habilitar</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Rappi Store ID</label>
                            <input name="rappi_store_id" value="{{ old('rappi_store_id', $config->rappi_store_id) }}" class="form-input-c text-xs" placeholder="Ej. 90012345">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">API Key</label>
                            <input name="rappi_api_key" type="password" value="{{ old('rappi_api_key', $config->rappi_api_key) }}" class="form-input-c text-xs" placeholder="Clave API Rappi">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Webhook Secret (HMAC)</label>
                            <input name="rappi_webhook_secret" type="password" value="{{ old('rappi_webhook_secret', $config->rappi_webhook_secret) }}" class="form-input-c text-xs" placeholder="Secret de firma">
                        </div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500 flex items-center justify-between">
                        <span>URL de Webhook para Rappi:</span>
                        <code class="font-mono text-orange-600 select-all">{{ url('/webhooks/rappi') }}</code>
                    </div>
                </div>

                {{-- Uber Eats --}}
                <div class="rounded-xl border border-emerald-200 bg-white p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-emerald-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-black text-emerald-400 font-black text-xs">Uber</span>
                            <span class="font-bold text-sm text-slate-800">Uber Eats México</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="ubereats_activo" value="1" @checked(old('ubereats_activo', $config->ubereats_activo)) class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                            <span class="ml-2 text-xs font-semibold text-slate-600">Habilitar</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Store UUID</label>
                            <input name="ubereats_store_id" value="{{ old('ubereats_store_id', $config->ubereats_store_id) }}" class="form-input-c text-xs" placeholder="UUID de la tienda">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Client ID</label>
                            <input name="ubereats_client_id" value="{{ old('ubereats_client_id', $config->ubereats_client_id) }}" class="form-input-c text-xs" placeholder="Client ID">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Client Secret / Firma</label>
                            <input name="ubereats_client_secret" type="password" value="{{ old('ubereats_client_secret', $config->ubereats_client_secret) }}" class="form-input-c text-xs" placeholder="Secret de webhook">
                        </div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500 flex items-center justify-between">
                        <span>URL de Webhook para Uber Eats:</span>
                        <code class="font-mono text-emerald-600 select-all">{{ url('/webhooks/ubereats') }}</code>
                    </div>
                </div>
            </div>
            <div class="flex justify-end pt-2 border-t border-slate-200">
                <button class="btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</x-app-layout>
