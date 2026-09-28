<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\Pedido;
use Illuminate\Http\Request;

class MeseroController extends Controller
{
    /** App de bolsillo: solo mesas asignadas al mesero (admin ve todas). */
    public function index()
    {
        return view('mesero.app');
    }

    /** JSON para el refresco en vivo del celular. */
    public function live(Request $request)
    {
        $user = $request->user();
        $soloMias = $user->role === 'mesero';

        $mesas = Mesa::with(['mesero:id,name', 'pedidoActivo.items'])
            ->when($soloMias, fn ($q) => $q->where('mesero_id', $user->id))
            ->orderByRaw('CAST(numero AS UNSIGNED)')
            ->get();

        $mesaIds = $mesas->pluck('id');

        $pedidos = Pedido::with('mesa:id,numero,mesero_id', 'items')
            ->whereIn('mesa_id', $mesaIds)
            ->whereNotIn('estado', ['pagado', 'cancelado'])
            ->latest()
            ->get();

        $mapPedido = function (Pedido $p) {
            return [
                'id'      => $p->id,
                'codigo'  => $p->codigo,
                'estado'  => $p->estado,
                'tipo'    => $p->tipo,
                'notas'   => $p->notas,
                'total'   => (float) $p->total,
                'mesa'    => $p->mesa?->numero,
                'mesa_id' => $p->mesa_id,
                'minutos' => (int) $p->created_at->diffInMinutes(now()),
                'items'   => $p->items->map(fn ($i) => [
                    'nombre'   => $i->nombre_producto,
                    'cantidad' => $i->cantidad,
                ])->values(),
            ];
        };

        return response()->json([
            'yo'    => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
            'mesas' => $mesas->map(fn (Mesa $m) => [
                'id'        => $m->id,
                'numero'    => $m->numero,
                'nombre'    => $m->nombre,
                'zona'      => $m->zona,
                'estado'    => $m->estado,
                'capacidad' => $m->capacidad,
                'mesero'    => $m->mesero?->name,
                'pedido'    => $m->pedidoActivo ? $mapPedido($m->pedidoActivo) : null,
            ])->values(),
            'pedidos' => $pedidos->map($mapPedido)->values(),
            'ts'      => now()->toIso8601String(),
        ]);
    }
}
