<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\Cobro;
use App\Models\CajaMovimiento;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\InsumoMovimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    public function index()
    {
        $caja = Caja::abiertaActual();
        $caja?->load(['movimientos.usuario', 'usuario']);
        $resumen = $caja ? $caja->resumen() : null;
        $historial = Caja::with('usuario', 'cerradaPor')
            ->where('estado', 'cerrada')
            ->latest('cerrada_at')
            ->limit(10)
            ->get();
        $porCobrar = Mesa::with(['pedidos' => fn ($q) => $q->whereNotIn('estado', ['pagado', 'cancelado'])->with('items')])
            ->where('estado', 'cuenta')
            ->orderByRaw('CAST(numero AS UNSIGNED)')
            ->get();
        $porCobrar = $porCobrar->filter(fn ($mesa) => (float) $mesa->pedidos->sum('total') > 0)->values();

        return view('modules.caja.index', compact('caja', 'resumen', 'historial', 'porCobrar'));
    }

    public function cobrar(Request $request, Mesa $mesa)
    {
        if (! Caja::abiertaActual()) {
            return back()->with('error', 'Abre la caja del día antes de cobrar.');
        }
        $data = $request->validate([
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia,mixto',
            'pago_efectivo' => 'nullable|numeric|min:0',
            'pago_tarjeta' => 'nullable|numeric|min:0',
            'cobrar_ahora' => 'nullable|numeric|min:0',
            'descuento_tipo' => 'nullable|in:monto,porcentaje',
            'descuento' => 'nullable|numeric|min:0',
            'descuento_motivo' => 'nullable|string|max:255',
            'propina' => 'nullable|numeric|min:0',
            'recibido' => 'nullable|numeric|min:0',
        ]);
        $pedidos = Pedido::with('items.producto.recetas.insumo')
            ->where('mesa_id', $mesa->id)
            ->whereNotIn('estado', ['pagado', 'cancelado'])
            ->get();
        $subtotal = (float) $pedidos->sum('total');
        if ($pedidos->isEmpty() || $subtotal <= 0) {
            return back()->with('error', 'Esa mesa no tiene cuenta por cobrar.');
        }
        $descIn = (float) ($data['descuento'] ?? 0);
        $tipo = $data['descuento_tipo'] ?? 'monto';
        $descuento = $tipo === 'porcentaje'
            ? round($subtotal * min($descIn, 100) / 100, 2)
            : min($descIn, $subtotal);
        if ($descuento > 0 && blank($data['descuento_motivo'] ?? null)) {
            return back()->with('error', 'Escribe el motivo del descuento.')->withInput();
        }
        $propina = (float) ($data['propina'] ?? 0);
        $totalAbonado = (float) $pedidos->sum('abonado');
        $aPagar = round($subtotal - $descuento + $propina - $totalAbonado, 2);
        $recibido = (float) ($data['recibido'] ?? 0);
        $cambio = 0.0;
        $efe = (float) ($data['pago_efectivo'] ?? 0);
        $tar = (float) ($data['pago_tarjeta'] ?? 0);
        if ($data['metodo_pago'] === 'mixto') {
            if (abs(($efe + $tar) - $aPagar) > 0.05) {
                return back()->with('error', 'Efectivo + tarjeta tiene que sumar el total.')->withInput();
            }
        } else {
            $efe = $data['metodo_pago'] === 'efectivo' ? $aPagar : 0;
            $tar = $data['metodo_pago'] === 'tarjeta' ? $aPagar : 0;
        }
        $ahora = (float) ($data['cobrar_ahora'] ?? 0);
        if ($ahora > 0 && $ahora + 0.001 < $aPagar) {
            $primero = $pedidos->first();
            Cobro::create([
                'mesa_id' => $mesa->id,
                'user_id' => auth()->id(),
                'monto' => $ahora,
                'metodo_pago' => $data['metodo_pago'],
                'efectivo' => $data['metodo_pago'] === 'efectivo' ? $ahora : ($data['metodo_pago'] === 'mixto' ? min($efe, $ahora) : 0),
                'tarjeta' => $data['metodo_pago'] === 'tarjeta' ? $ahora : ($data['metodo_pago'] === 'mixto' ? max(0, $ahora - min($efe, $ahora)) : 0),
            ]);
            $primero->increment('abonado', $ahora);
            $falta = max(0, $aPagar - $ahora);
            return back()->with('success', 'Abono de $'.number_format($ahora, 2).' registrado. Falta $'.number_format($falta, 2).'.');
        }
        if ($data['metodo_pago'] === 'efectivo') {
            if ($recibido + 0.001 < $aPagar) {
                return back()->with('error', 'El efectivo recibido no alcanza.')->withInput();
            }
            $cambio = round($recibido - $aPagar, 2);
        }
        DB::transaction(function () use ($pedidos, $mesa, $data, $descuento, $propina, $recibido, $cambio) {
            $primero = true;
            foreach ($pedidos as $p) {
                $p->update([
                    'estado' => 'pagado',
                    'metodo_pago' => $data['metodo_pago'],
                    'pago_efectivo' => $primero ? $efe : 0,
                    'pago_tarjeta' => $primero ? $tar : 0,
                    'pagado_at' => now(),
                    'descuento' => $primero ? $descuento : (float) $p->descuento,
                    'propina' => $primero ? $propina : 0,
                    'recibido' => $primero ? ($data['metodo_pago'] === 'efectivo' ? $recibido : null) : null,
                    'cambio' => $primero ? ($data['metodo_pago'] === 'efectivo' ? $cambio : null) : null,
                    'descuento_motivo' => $primero ? ($data['descuento_motivo'] ?? null) : null,
                ]);
                $primero = false;
            }
            $mesa->update(['estado' => 'libre']);
        });
        session(['ultimo_recibo' => [
            'mesa' => $mesa->numero,
            'ids' => $pedidos->pluck('id')->all(),
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'propina' => $propina,
            'total' => $aPagar,
            'metodo' => $data['metodo_pago'],
            'recibido' => $recibido,
            'cambio' => $cambio,
            'motivo' => $data['descuento_motivo'] ?? null,
        ]]);
        return redirect()->route('caja.recibo');
    }

    /** Cobro de cuenta dividida en partes iguales entre N comensales. */
    public function cobrarParte(Request $request, Mesa $mesa)
    {
        if (! Caja::abiertaActual()) {
            return back()->with('error', 'Abre la caja del día antes de cobrar.');
        }

        $data = $request->validate([
            'personas'    => 'required|integer|min:2|max:30',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
            'monto'       => 'required|numeric|min:0.01',
            'recibido'    => 'nullable|numeric|min:0',
        ]);

        $pedidos = Pedido::with('items')
            ->where('mesa_id', $mesa->id)
            ->whereNotIn('estado', ['pagado', 'cancelado'])
            ->get();

        $subtotal = (float) $pedidos->sum('total');
        $totalAbonado = (float) $pedidos->sum('abonado');
        $saldoPendiente = round($subtotal - $totalAbonado, 2);

        if ($saldoPendiente <= 0 || $pedidos->isEmpty()) {
            return back()->with('error', 'Esta mesa no tiene saldo por pagar.');
        }

        $montoCobrar = min($saldoPendiente, (float) $data['monto']);
        $recibido = (float) ($data['recibido'] ?? $montoCobrar);
        $cambio = $data['metodo_pago'] === 'efectivo' ? max(0, $recibido - $montoCobrar) : 0;

        $primero = $pedidos->first();

        Cobro::create([
            'mesa_id'     => $mesa->id,
            'user_id'     => auth()->id(),
            'monto'       => $montoCobrar,
            'metodo_pago' => $data['metodo_pago'],
            'efectivo'    => $data['metodo_pago'] === 'efectivo' ? $montoCobrar : 0,
            'tarjeta'     => $data['metodo_pago'] === 'tarjeta' ? $montoCobrar : 0,
        ]);

        $primero->increment('abonado', $montoCobrar);
        $nuevoSaldo = round($saldoPendiente - $montoCobrar, 2);

        if ($nuevoSaldo <= 0.05) {
            foreach ($pedidos as $p) {
                $p->update([
                    'estado'      => 'pagado',
                    'metodo_pago' => $data['metodo_pago'],
                    'pagado_at'   => now(),
                ]);
            }
            $mesa->update(['estado' => 'libre']);
        }

        session(['ultimo_recibo' => [
            'mesa'      => $mesa->numero,
            'ids'       => $pedidos->pluck('id')->all(),
            'subtotal'  => $montoCobrar,
            'descuento' => 0,
            'propina'   => 0,
            'total'     => $montoCobrar,
            'metodo'    => $data['metodo_pago'],
            'recibido'  => $recibido,
            'cambio'    => $cambio,
            'motivo'    => "Pago en partes (1 de {$data['personas']})",
        ]]);

        return redirect()->route('caja.recibo')->with('success', "Parte de \${$montoCobrar} cobrada. Saldo restante: \${$nuevoSaldo}");
    }

    /** Cobro de cuenta dividida seleccionando platillos específicos. */
    public function cobrarItems(Request $request, Mesa $mesa)
    {
        if (! Caja::abiertaActual()) {
            return back()->with('error', 'Abre la caja del día antes de cobrar.');
        }

        $data = $request->validate([
            'items'           => 'required|array|min:1',
            'items.*.id'      => 'required|integer',
            'items.*.cantidad'=> 'required|integer|min:1',
            'metodo_pago'     => 'required|in:efectivo,tarjeta,transferencia,mixto',
            'pago_efectivo'   => 'nullable|numeric|min:0',
            'pago_tarjeta'    => 'nullable|numeric|min:0',
            'propina'         => 'nullable|numeric|min:0',
            'descuento'       => 'nullable|numeric|min:0',
            'recibido'        => 'nullable|numeric|min:0',
        ]);

        $pedidos = Pedido::with('items')
            ->where('mesa_id', $mesa->id)
            ->whereNotIn('estado', ['pagado', 'cancelado'])
            ->get();

        if ($pedidos->isEmpty()) {
            return back()->with('error', 'Esta mesa no tiene pedidos pendientes.');
        }

        $restaurante = \App\Models\Restaurante::actual();
        $nuevoPedido = DB::transaction(function () use ($data, $mesa, $pedidos, $restaurante) {
            $nuevo = Pedido::create([
                'restaurante_id' => $restaurante->id ?? auth()->user()->restaurante_id,
                'codigo'      => Pedido::generarCodigo(),
                'mesa_id'     => $mesa->id,
                'user_id'     => auth()->id(),
                'tipo'        => 'mesa',
                'estado'      => 'pagado',
                'metodo_pago' => $data['metodo_pago'],
                'pagado_at'   => now(),
                'subtotal'    => 0,
                'impuesto'    => 0,
                'total'       => 0,
                'notas'       => 'Cuenta separada por platillos (Mesa ' . $mesa->numero . ')',
            ]);

            $subtotalCobrado = 0;

            foreach ($data['items'] as $itemInput) {
                $cantACobrar = (int) $itemInput['cantidad'];
                if ($cantACobrar <= 0) continue;

                foreach ($pedidos as $p) {
                    $item = $p->items->firstWhere('id', $itemInput['id']);
                    if (! $item) continue;

                    $cant = min($cantACobrar, (int) $item->cantidad);
                    if ($cant <= 0) continue;

                    $sub = (float) $item->precio * $cant;
                    $subtotalCobrado += $sub;

                    \App\Models\PedidoItem::create([
                        'pedido_id'       => $nuevo->id,
                        'producto_id'     => $item->producto_id,
                        'nombre_producto' => $item->nombre_producto,
                        'cantidad'        => $cant,
                        'precio'          => $item->precio,
                        'subtotal'        => $sub,
                        'notas'           => $item->notas,
                        'listo'           => true,
                    ]);

                    if ($item->cantidad <= $cant) {
                        $item->delete();
                    } else {
                        $item->decrement('cantidad', $cant);
                        $item->update(['subtotal' => (float) $item->precio * (int) $item->cantidad]);
                    }

                    $cantACobrar -= $cant;
                    if ($cantACobrar <= 0) break;
                }
            }

            if ($subtotalCobrado <= 0) {
                throw new \Exception('No se seleccionó ningún platillo válido.');
            }

            $desc = (float) ($data['descuento'] ?? 0);
            $prop = (float) ($data['propina'] ?? 0);
            $ivaRate = ((float) ($restaurante->igv ?? 0)) / 100;
            $impuesto = round($subtotalCobrado * $ivaRate, 2);
            $totalCobrado = max(0, round($subtotalCobrado - $desc + $impuesto + $prop, 2));

            $nuevo->update([
                'subtotal'      => $subtotalCobrado,
                'descuento'     => $desc,
                'impuesto'      => $impuesto,
                'propina'       => $prop,
                'total'         => $totalCobrado,
                'pago_efectivo' => $data['metodo_pago'] === 'efectivo' ? $totalCobrado : ($data['pago_efectivo'] ?? 0),
                'pago_tarjeta'  => $data['metodo_pago'] === 'tarjeta' ? $totalCobrado : ($data['pago_tarjeta'] ?? 0),
                'recibido'      => $data['metodo_pago'] === 'efectivo' ? ($data['recibido'] ?? $totalCobrado) : null,
                'cambio'        => $data['metodo_pago'] === 'efectivo' ? max(0, (float)($data['recibido'] ?? $totalCobrado) - $totalCobrado) : null,
            ]);

            $quedanItems = false;
            foreach ($pedidos as $p) {
                $itemsRestantes = $p->items()->get();
                if ($itemsRestantes->isEmpty()) {
                    $p->delete();
                } else {
                    $quedanItems = true;
                    $nuevoSub = (float) $itemsRestantes->sum('subtotal');
                    $nuevoImp = round($nuevoSub * $ivaRate, 2);
                    $p->update([
                        'subtotal' => $nuevoSub,
                        'impuesto' => $nuevoImp,
                        'total'    => $nuevoSub + $nuevoImp,
                    ]);
                }
            }

            if (! $quedanItems) {
                $mesa->update(['estado' => 'libre']);
            } else {
                $mesa->update(['estado' => 'cuenta']);
            }

            return $nuevo;
        });

        session(['ultimo_recibo' => [
            'mesa'      => $mesa->numero,
            'ids'       => [$nuevoPedido->id],
            'subtotal'  => (float) $nuevoPedido->subtotal,
            'descuento' => (float) $nuevoPedido->descuento,
            'propina'   => (float) $nuevoPedido->propina,
            'total'     => (float) $nuevoPedido->total,
            'metodo'    => $data['metodo_pago'],
            'recibido'  => (float) $nuevoPedido->recibido,
            'cambio'    => (float) $nuevoPedido->cambio,
            'motivo'    => 'Platillos individuales',
        ]]);

        return redirect()->route('caja.recibo');
    }

    public function recibo()
    {
        $r = session('ultimo_recibo');
        if (! $r) {
            return redirect()->route('caja.index')->with('error', 'No hay un ticket reciente.');
        }
        $pedidos = Pedido::with('items', 'mesa')->whereIn('id', $r['ids'])->get();
        $rest = \App\Models\Restaurante::actual();
        return view('modules.caja.recibo', compact('r', 'pedidos', 'rest'));
    }

    public function anular(Pedido $pedido)
    {
        abort_unless(auth()->user()->role === 'admin', 403);
        if ($pedido->estado !== 'pagado') {
            return back()->with('error', 'Solo se anula un cobro ya pagado.');
        }
        DB::transaction(function () use ($pedido) {
            $pedido->load('items.producto.recetas.insumo');
            foreach ($pedido->items as $item) {
                foreach ($item->producto->recetas ?? [] as $r) {
                    if ($r->insumo) {
                        InsumoMovimiento::registrar($r->insumo, 'entrada', (float) $r->cantidad * $item->cantidad, 'Anulación de cobro', $pedido->codigo, auth()->id());
                    }
                }
            }
            $pedido->update([
                'estado' => 'servido',
                'pagado_at' => null,
                'metodo_pago' => null,
                'propina' => 0,
                'recibido' => null,
                'cambio' => null,
            ]);
            if ($pedido->mesa) {
                $pedido->mesa->update(['estado' => 'cuenta']);
            }
        });
        return redirect()->route('caja.index')->with('success', 'Cobro anulado. La mesa volvió a cuenta.');
    }

    public function abrir(Request $request)
    {
        if (Caja::abiertaActual()) {
            return back()->with('error', 'Ya existe una caja abierta. Ciérrala antes de abrir otra.');
        }
        $data = $request->validate([
            'monto_inicial'  => 'required|numeric|min:0',
            'notas_apertura' => 'nullable|string|max:500',
        ]);
        Caja::create([
            'user_id'        => auth()->id(),
            'estado'         => 'abierta',
            'monto_inicial'  => $data['monto_inicial'],
            'notas_apertura' => $data['notas_apertura'] ?? null,
            'abierta_at'     => now(),
        ]);
        return redirect()->route('caja.index')->with('success', 'Caja abierta correctamente.');
    }

    public function movimiento(Request $request)
    {
        $caja = Caja::abiertaActual();
        if (! $caja) {
            return back()->with('error', 'No hay una caja abierta.');
        }
        $data = $request->validate([
            'tipo'        => 'required|in:ingreso,egreso',
            'concepto'    => 'required|string|max:255',
            'monto'       => 'required|numeric|min:0.01',
            'metodo_pago' => 'nullable|in:efectivo,tarjeta,transferencia,oxxo,mercadopago,clip,codi',
        ]);
        $caja->movimientos()->create([
            'user_id'     => auth()->id(),
            'tipo'        => $data['tipo'],
            'concepto'    => $data['concepto'],
            'monto'       => $data['monto'],
            'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
        ]);
        return back()->with('success', 'Movimiento registrado.');
    }

    public function cerrar(Request $request)
    {
        $caja = Caja::abiertaActual();
        if (! $caja) {
            return back()->with('error', 'No hay una caja abierta.');
        }
        $data = $request->validate([
            'monto_contado' => 'required|numeric|min:0',
            'notas_cierre'  => 'nullable|string|max:500',
        ]);
        $resumen  = $caja->resumen();
        $esperado = $resumen['efectivo_esperado'];
        $caja->update([
            'estado'            => 'cerrada',
            'cerrada_por'       => auth()->id(),
            'efectivo_esperado' => $esperado,
            'monto_contado'     => $data['monto_contado'],
            'diferencia'        => round($data['monto_contado'] - $esperado, 2),
            'notas_cierre'      => $data['notas_cierre'] ?? null,
            'cerrada_at'        => now(),
        ]);
        return redirect()->route('caja.show', $caja)->with('success', 'Caja cerrada. Aquí está el arqueo.');
    }

    public function show(Caja $caja)
    {
        $caja->load(['movimientos.usuario', 'usuario', 'cerradaPor']);
        $resumen = $caja->resumen();
        return view('modules.caja.show', compact('caja', 'resumen'));
    }
}
