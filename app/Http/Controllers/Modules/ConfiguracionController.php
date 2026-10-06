<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    public function edit()
    {
        $config = Restaurante::actual();
        return view('modules.configuracion.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $restaurante = Restaurante::actual();

        $data = $request->validate([
            'nombre_restaurante' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'moneda' => 'required|string|max:10',
            'igv' => 'required|numeric|min:0|max:100',
            'meta_mensual' => 'required|numeric|min:0',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'eliminar_logo' => 'nullable|boolean',
            'rappi_activo' => 'nullable|boolean',
            'rappi_store_id' => 'nullable|string|max:100',
            'rappi_api_key' => 'nullable|string|max:255',
            'rappi_webhook_secret' => 'nullable|string|max:255',
            'ubereats_activo' => 'nullable|boolean',
            'ubereats_store_id' => 'nullable|string|max:100',
            'ubereats_client_id' => 'nullable|string|max:255',
            'ubereats_client_secret' => 'nullable|string|max:255',
        ]);

        $data['rappi_activo'] = $request->boolean('rappi_activo');
        $data['ubereats_activo'] = $request->boolean('ubereats_activo');
        $data['nombre'] = $data['nombre_restaurante'];
        unset($data['nombre_restaurante']);

        if ($request->boolean('eliminar_logo')) {
            if ($restaurante->logo && Storage::disk('public')->exists($restaurante->logo)) {
                Storage::disk('public')->delete($restaurante->logo);
            }
            $data['logo'] = null;
        } elseif ($request->hasFile('logo')) {
            if ($restaurante->logo && Storage::disk('public')->exists($restaurante->logo)) {
                Storage::disk('public')->delete($restaurante->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        unset($data['eliminar_logo']);

        $restaurante->update($data);

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}
