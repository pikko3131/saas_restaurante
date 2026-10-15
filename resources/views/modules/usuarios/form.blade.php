<x-app-layout :title="$usuario->exists ? 'Editar usuario' : 'Nuevo usuario'">
    <x-page-header :title="$usuario->exists ? 'Editar usuario' : 'Nuevo usuario'">
        <a href="{{ route('usuarios.index') }}" class="btn-secondary">← Volver</a>
    </x-page-header>

    <div class="card max-w-2xl">
        <x-val-errors />
        <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="space-y-5">
            @csrf
            @if($usuario->exists) @method('PUT') @endif
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nombre *</label>
                    <input name="name" value="{{ old('name', $usuario->name) }}" class="form-input-c" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $usuario->email) }}" class="form-input-c" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Rol *</label>
                    <select name="role" class="form-input-c" required>
                        @foreach (['admin'=>'Administrador','cajero'=>'Cajero','mesero'=>'Mesero','cocina'=>'Cocina'] as $k=>$v)
                            <option value="{{ $k }}" @selected(old('role', $usuario->role)==$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Teléfono</label>
                    <input name="telefono" value="{{ old('telefono', $usuario->telefono) }}" class="form-input-c">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Contraseña {{ $usuario->exists ? '(dejar en blanco para no cambiar)' : '*' }}</label>
                    <input type="password" name="password" class="form-input-c" {{ $usuario->exists ? '' : 'required' }}>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-input-c" {{ $usuario->exists ? '' : 'required' }}>
                </div>
                <div class="sm:col-span-2 rounded-2xl bg-slate-50 p-4 border border-slate-200">
                    <p class="text-sm font-bold text-slate-800">Accesos y funciones permitidas</p>
                    <p class="text-xs text-slate-500 mb-3">Ideal para empleados multifunción (ej. cajera que también atiende mesas o toma pedidos).</p>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700 bg-white p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:border-brand-300">
                            <input type="checkbox" name="permisos[]" value="caja" @checked(in_array('caja', old('permisos', $usuario->permisos ?? ($usuario->role==='cajero'?['caja']:[])))) class="rounded border-slate-300 text-brand-600 focus:ring-brand-400">
                            <span>💵 Caja / Cobros</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 bg-white p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:border-brand-300">
                            <input type="checkbox" name="permisos[]" value="pos" @checked(in_array('pos', old('permisos', $usuario->permisos ?? (in_array($usuario->role, ['cajero','admin'])?['pos']:[])))) class="rounded border-slate-300 text-brand-600 focus:ring-brand-400">
                            <span>🛒 Mostrador / POS</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 bg-white p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:border-brand-300">
                            <input type="checkbox" name="permisos[]" value="mesero" @checked(in_array('mesero', old('permisos', $usuario->permisos ?? ($usuario->role==='mesero'?['mesero']:[])))) class="rounded border-slate-300 text-brand-600 focus:ring-brand-400">
                            <span>🍽️ Salón / Mesas</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 bg-white p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:border-brand-300">
                            <input type="checkbox" name="permisos[]" value="cocina" @checked(in_array('cocina', old('permisos', $usuario->permisos ?? ($usuario->role==='cocina'?['cocina']:[])))) class="rounded border-slate-300 text-brand-600 focus:ring-brand-400">
                            <span>🍳 Pantalla Cocina</span>
                        </label>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', $usuario->activo ?? true)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-400">
                    Usuario activo
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('usuarios.index') }}" class="btn-secondary">Cancelar</a>
                <button class="btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</x-app-layout>
