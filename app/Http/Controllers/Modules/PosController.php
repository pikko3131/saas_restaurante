<?php

namespace App\Http\Controllers\Modules;

use App\Events\PedidoCambio;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\InsumoMovimiento;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $categorias = Categoria::where('activo', true)->orderBy('orden')->get();
        $productos = Producto::with('categoria')->where('disponible', true)->orderBy('nombre')->get();
        $mesas = Mesa::orderByRaw('CAST(numero AS UNSIGNED)')->get();
        $clientes = Cliente::orderBy('nombre')->get(['id', 'nombre', 'puntos']);
        $promos = Promocion::where('activo', true)->get();
        $config = Restaurante::actual();

        return view('modules.pos.index', compact('categorias', 'productos', 'mesas', 'clientes', 'promos', 'config'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mesa_id' => 'nullable|exists:mesas,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'tipo' => 'required|in:mesa,llevar,delivery',
            'metodo_pago' => 'nullable|in:efectivo,tarjeta,transferencia,oxxo,mercadopago,clip,codi',
            'pagar' => 'nullable|boolean',
            'descuento' => 'nullable|numeric|min:0',
            'promocion_id' => 'nullable|exists:promociones,id',
            'codigo_promo' => 'nullable|string|max:40',
            'usar_puntos' => 'nullable|integer|min:0',
            'delivery_direccion' => 'nullable|string|max:255',
            'delivery_telefono' => 'nullable|string|max:30',
            'delivery_referencia' => 'nullable|string|max:255',
            'delivery_costo_envio' => 'nullable|numeric|min:0',
            'delivery_repartidor' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
        ]);

        $config = Restaurante::actual();

        $pedido = DB::transaction(function () use ($data, $config, $request) {
            // Subtotal y líneas por producto
            $subtotal = 0;
            $lineas = [];
            $detalle = [];
            foreach ($data['items'] as $item) {
                $prod = Producto::find($item['id']);
                $sub = (float) $prod->precio * $item['cantidad'];
                $subtotal += $sub;
                $lineas[$prod->id] = ($lineas[$prod->id] ?? 0) + $sub;
                $detalle[] = [$prod, $item['cantidad'], $sub];
            }

            // Promoción (por id o por código)
            $promo = null;
            if (! empty($data['promocion_id'])) {
                $promo = Promocion::find($data['promocion_id']);
            } elseif (! empty($data['codigo_promo'])) {
                $promo = Promocion::where('codigo', $data['codigo_promo'])->first();
            }
            $descPromo = $promo ? $promo->calcularDescuento($subtotal, $lineas) : 0;

            // Puntos a canjear
            $cliente = ! empty($data['cliente_id']) ? Cliente::find($data['cliente_id']) : null;
            $puntosUsados = 0;
            $descPuntos = 0;
            if ($cliente && ! empty($data['usar_puntos'])) {
                $puntosUsados = min((int) $data['usar_puntos'], (int) $cliente->puntos);
                $descPuntos = round($puntosUsados * Cliente::VALOR_PUNTO, 2);
            }

            $descManual = (float) ($data['descuento'] ?? 0);
            $descuento = min($subtotal, round($descManual + $descPromo + $descPuntos, 2));

            $base = max(0, $subtotal - $descuento);
            $impuesto = round($base * ((float) $config->igv / 100), 2);
            $pagado = (bool) ($data['pagar'] ?? false);
            $costoEnvio = $data['tipo'] === 'delivery' ? (float) ($data['delivery_costo_envio'] ?? 0) : 0;

            $pedido = Pedido::create([
                'codigo' => Pedido::generarCodigo(),
                'mesa_id' => $data['tipo'] === 'mesa' ? ($data['mesa_id'] ?? null) : null,
                'cliente_id' => $cliente?->id,
                'promocion_id' => $promo?->id,
                'puntos_usados' => $puntosUsados,
                'user_id' => $request->user()->id,
                'tipo' => $data['tipo'],
                'estado' => $pagado ? 'pagado' : 'pendiente',
                'descuento' => $descuento,
                'metodo_pago' => $pagado ? ($data['metodo_pago'] ?? 'efectivo') : null,
                'pagado_at' => $pagado ? now() : null,
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $base + $impuesto + $costoEnvio,
                'delivery_direccion' => $data['delivery_direccion'] ?? null,
                'delivery_telefono' => $data['delivery_telefono'] ?? null,
                'delivery_referencia' => $data['delivery_referencia'] ?? null,
                'delivery_costo_envio' => $costoEnvio,
                'delivery_repartidor' => $data['delivery_repartidor'] ?? null,
                'delivery_estado' => $data['tipo'] === 'delivery' ? 'pendiente' : null,
            ]);

            foreach ($detalle as [$prod, $cant, $sub]) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id, 'producto_id' => $prod->id, 'nombre_producto' => $prod->nombre,
                    'cantidad' => $cant, 'precio' => $prod->precio, 'subtotal' => $sub,
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

            // Canje de puntos (registra movimiento y descuenta del cliente)
            if ($cliente && $puntosUsados > 0) {
                $cliente->canjearPuntos($puntosUsados, $pedido);
            }

            // Acreditar puntos si el pedido se cobró
            if ($pagado && $cliente) {
                $ganados = $cliente->acreditarPuntos((float) $pedido->total, $pedido);
                $pedido->update(['puntos_ganados' => $ganados]);
            }

            if ($pedido->mesa_id && ! $pagado) {
                Mesa::where('id', $pedido->mesa_id)->update(['estado' => 'ocupada']);
            }

            return $pedido;
        });

        event(new PedidoCambio($pedido->fresh(['mesa', 'items']), 'creado'));

        return redirect()->route('pedidos.show', $pedido)->with('success', "Pedido {$pedido->codigo} registrado correctamente.");
    }
}
