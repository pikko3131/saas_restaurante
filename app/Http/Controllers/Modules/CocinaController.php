<?php
namespace App\Http\Controllers\Modules;
use App\Events\PedidoCambio;
use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\PedidoItem;
class CocinaController extends Controller
{
    public function index()
    {
        return view('modules.cocina.index');
    }

    public function data()
    {
        $activos = Pedido::with('mesa', 'items')
            ->whereIn('estado', ['pendiente', 'preparando'])
            ->orderByDesc('prioridad')
            ->orderBy('created_at')
            ->get();
        $listos = Pedido::with('mesa', 'items')
            ->where('estado', 'servido')
            ->where('updated_at', '>=', now()->subMinutes(30))
            ->orderByDesc('updated_at')
            ->get();
        $map = fn (Pedido $p) => [
            'id' => $p->id,
            'codigo' => $p->codigo,
            'tipo' => $p->tipo,
            'mesa' => $p->mesa?->numero,
            'estado' => $p->estado,
            'notas' => $p->notas,
            'prioridad' => (bool) $p->prioridad,
            'creado' => $p->created_at->toIso8601String(),
            'minutos' => (int) $p->created_at->diffInMinutes(now()),
            'items' => $p->items->map(fn ($i) => [
                'id' => $i->id,
                'nombre' => $i->nombre_producto,
                'cantidad' => $i->cantidad,
                'notas' => $i->notas,
                'listo' => (bool) $i->listo,
            ])->values(),
        ];
        $conteo = [];
        foreach ($activos as $p) {
            foreach ($p->items as $i) {
                if ($i->listo) continue;
                $conteo[$i->nombre_producto] = ($conteo[$i->nombre_producto] ?? 0) + $i->cantidad;
            }
        }
        arsort($conteo);
        return response()->json([
            'pendientes' => $activos->where('estado', 'pendiente')->map($map)->values(),
            'preparando' => $activos->where('estado', 'preparando')->map($map)->values(),
            'listos' => $listos->map($map)->values(),
            'conteo' => collect($conteo)->map(fn ($q, $n) => ['nombre' => $n, 'cantidad' => $q])->values(),
            'ts' => now()->toIso8601String(),
        ]);
    }

    public function avanzar(Pedido $pedido)
    {
        $siguiente = match ($pedido->estado) {
            'pendiente' => 'preparando',
            'preparando' => 'servido',
            default => $pedido->estado,
        };
        if ($siguiente === 'servido') {
            $pedido->items()->update(['listo' => true, 'listo_at' => now()]);
        }
        $pedido->update(['estado' => $siguiente]);
        event(new PedidoCambio($pedido->fresh(['mesa', 'items']), 'cocina'));
        return response()->json(['ok' => true, 'estado' => $siguiente]);
    }

    public function itemListo(PedidoItem $item)
    {
        $item->update(['listo' => ! $item->listo, 'listo_at' => $item->listo ? null : now()]);
        $pedido = $item->pedido()->with('items')->first();
        if ($pedido && $pedido->estado === 'preparando' && $pedido->items->every(fn ($i) => $i->listo)) {
            $pedido->update(['estado' => 'servido']);
            event(new PedidoCambio($pedido->fresh(['mesa', 'items']), 'cocina'));
        }
        return response()->json(['ok' => true]);
    }

    public function prioridad(Pedido $pedido)
    {
        $pedido->update(['prioridad' => ! $pedido->prioridad]);
        return response()->json(['ok' => true, 'prioridad' => (bool) $pedido->prioridad]);
    }

    public function recall(Pedido $pedido)
    {
        if ($pedido->estado === 'servido') {
            $pedido->update(['estado' => 'preparando']);
        }
        return response()->json(['ok' => true]);
    }

    public function cancelar(Pedido $pedido)
    {
        if ($pedido->estado !== 'cancelado') {
            $pedido->restaurarInventario();
        }
        $pedido->update(['estado' => 'cancelado']);
        if ($pedido->mesa) {
            $pedido->mesa->update(['estado' => 'libre']);
        }
        return response()->json(['ok' => true, 'estado' => 'cancelado']);
    }
}
