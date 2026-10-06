<?php

namespace App\Http\Controllers\Modules;

use App\Events\PedidoCambio;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\InsumoMovimiento;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeseroController extends Controller
{
    public function index()
    {
        return view('mesero.app');
    }

    public function live(Request $request)
    {
        $user = $request->user();
        $soloMias = $user->role === 'mesero';

        $mesas = Mesa::with(['mesero:id,name', 'pedidoActivo.items'])
            ->when($soloMias, fn ($q) => $q->where('mesero_id', $user->id))
            ->orderByRaw('CAST(numero AS UNSIGNED)')
            ->get();

        $mesaIds = $mesas->pluck('id');

        $pedidos = Pedido::with('mesa:id,numero,mesero_id,estado', 'items')
            ->whereIn('mesa_id', $mesaIds)
            ->whereNotIn('estado', ['pagado', 'cancelado'])
            ->latest()
            ->get();

        return response()->json([
            'yo' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
            'mesas' => $mesas->map(fn (Mesa $m) => $this->mesaJson($m))->values(),
            'pedidos' => $pedidos->map(fn (Pedido $p) => $this->pedidoJson($p))->values(),
            'ts' => now()->toIso8601String(),
        ]);
    }

    public function carta()
    {
        $categorias = Categoria::where('activo', true)->orderBy('orden')->get(['id', 'nombre']);
        $productos = Producto::where('disponible', true)->orderBy('nombre')->get(['id', 'categoria_id', 'nombre', 'precio']);

        return response()->json([
            'categorias' => $categorias,
            'productos' => $productos,
        ]);
    }

    /** El mesero manda una comanda nueva de su mesa. Cocina la recibe como pendiente. */
    public function pedir(Request $request)
    {
        $data = $request->validate([
            'mesa_id' => 'required|exists:mesas,id',
            'notas' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.cantidad' => 'required|integer|min:1|max:30',
            'items.*.nota' => 'nullable|string|max:120',
        ]);

        $user = $request->user();
        $mesa = Mesa::findOrFail($data['mesa_id']);
        $this->autorizarMesa($user, $mesa);

        $config = Restaurante::actual();

        $pedido = DB::transaction(function () use ($data, $mesa, $user, $config, $request) {
            $subtotal = 0;
            $detalle = [];

            foreach ($data['items'] as $item) {
                $prod = Producto::where('disponible', true)->find($item['id']);
                if (! $prod) {
                    abort(422, 'Un producto ya no está disponible.');
                }
                $sub = (float) $prod->precio * $item['cantidad'];
                $subtotal += $sub;
                $detalle[] = [$prod, $item['cantidad'], $sub, $item['nota'] ?? null];
            }

            $impuesto = round($subtotal * ((float) $config->igv / 100), 2);

            $pedido = Pedido::create([
                'codigo' => Pedido::generarCodigo(),
                'mesa_id' => $mesa->id,
                'user_id' => $user->id,
                'tipo' => 'mesa',
                'estado' => 'pendiente',
                'notas' => $data['notas'] ?? null,
                'subtotal' => $subtotal,
                'descuento' => 0,
                'impuesto' => $impuesto,
                'total' => $subtotal + $impuesto,
            ]);

            foreach ($detalle as [$prod, $cant, $sub, $nota]) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $prod->id,
                    'nombre_producto' => $prod->nombre,
                    'cantidad' => $cant,
                    'precio' => $prod->precio,
                    'subtotal' => $sub,
                    'notas' => $nota,
                ]);
                $prod->increment('vendidos', $cant);
                if ($prod->controla_stock) {
                    $prod->decrement('stock', $cant);
                }
                foreach ($prod->recetas as $r) {
                    if ($r->insumo) {
                        InsumoMovimiento::registrar($r->insumo, 'salida', (float) $r->cantidad * $cant, 'Venta', $pedido->codigo, $request->user()->id);
                    }
                }
            }

            $mesa->update(['estado' => 'ocupada']);

            return $pedido;
        });

        event(new PedidoCambio($pedido->fresh(['mesa', 'items']), 'creado'));

        return response()->json([
            'ok' => true,
            'pedido' => $this->pedidoJson($pedido->fresh(['mesa', 'items'])),
        ]);
    }

    /** Marca la mesa para que caja la cobre. No cobra. */
    public function cuenta(Request $request, Mesa $mesa)
    {
        $this->autorizarMesa($request->user(), $mesa);

        if (! $mesa->pedidoActivo) {
            return response()->json(['ok' => false, 'mensaje' => 'Esta mesa no tiene pedido abierto.'], 422);
        }

        $mesa->update(['estado' => 'cuenta']);

        return response()->json(['ok' => true, 'estado' => 'cuenta']);
    }

    public function ticket(Request $request, Pedido $pedido)
    {
        $pedido->load('mesa', 'items', 'user', 'cliente');
        if ($pedido->mesa) {
            $this->autorizarMesa($request->user(), $pedido->mesa);
        } elseif ($request->user()->role === 'mesero') {
            abort(403);
        }

        $config = Restaurante::actual();

        return view('modules.pedidos.ticket', compact('pedido', 'config'));
    }

    public function llevar(Request $request, Pedido $pedido)
    {
        $user = $request->user();
        $pedido->load('mesa');

        if ($pedido->mesa) {
            $this->autorizarMesa($user, $pedido->mesa);
        }

        if ($pedido->estado !== 'servido') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Cocina todavía no lo marcó como listo.',
            ], 422);
        }

        $pedido->update(['entregado_at' => now()]);

        if ($pedido->mesa && $pedido->mesa->estado !== 'cuenta') {
            $pedido->mesa->update(['estado' => 'ocupada']);
        }

        if (class_exists(PedidoCambio::class)) {
            event(new PedidoCambio($pedido->fresh(['mesa', 'items']), 'entregado'));
        }

        return response()->json(['ok' => true]);
    }

    private function autorizarMesa($user, Mesa $mesa): void
    {
        if ($user->role === 'mesero' && (int) $mesa->mesero_id !== (int) $user->id) {
            abort(403, 'Esa mesa no es tuya.');
        }
    }

    private function pedidoJson(Pedido $p): array
    {
        return [
            'id' => $p->id,
            'codigo' => $p->codigo,
            'estado' => $p->estado,
            'tipo' => $p->tipo,
            'notas' => $p->notas,
            'total' => (float) $p->total,
            'mesa' => $p->mesa?->numero,
            'mesa_id' => $p->mesa_id,
            'minutos' => (int) $p->created_at->diffInMinutes(now()),
            'entregado' => (bool) $p->entregado_at,
            'puede_llevar' => $p->estado === 'servido' && ! $p->entregado_at,
            'items' => $p->items->map(fn ($i) => [
                'nombre' => $i->nombre_producto,
                'cantidad' => $i->cantidad,
                'notas' => $i->notas,
            ])->values(),
        ];
    }

    private function mesaJson(Mesa $m): array
    {
        $pedido = $m->pedidoActivo;
        $salon = $m->estado;
        if ($m->estado === 'cuenta') {
            $salon = 'cuenta';
        } elseif ($pedido && in_array($pedido->estado, ['pendiente', 'preparando'], true)) {
            $salon = 'esperando';
        } elseif ($pedido && $pedido->estado === 'servido' && ! $pedido->entregado_at) {
            $salon = 'listo';
        } elseif ($pedido) {
            $salon = 'ocupada';
        } elseif ($m->estado !== 'reservada') {
            $salon = 'libre';
        }

        return [
            'id' => $m->id,
            'numero' => $m->numero,
            'nombre' => $m->nombre,
            'zona' => $m->zona,
            'estado' => $m->estado,
            'estado_salon' => $salon,
            'capacidad' => $m->capacidad,
            'mesero' => $m->mesero?->name,
            'pedido' => $pedido ? $this->pedidoJson($pedido) : null,
        ];
    }
}
