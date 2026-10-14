<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Caja;
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

        return view('modules.caja.index', compact('caja', 'resumen', 'historial', 'porCobrar'));
    }

    public function cobrar(Request $request, Mesa $mesa)
    {
        $data = $request->validate([
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
        ]);
        $pedidos = Pedido::where('mesa_id', $mesa->id)->whereNotIn('estado', ['pagado', 'cancelado'])->get();
        if ($pedidos->isEmpty()) {
            return back()->with('error', 'Esa mesa no tiene cuenta abierta.');
        }
        DB::transaction(function () use ($pedidos, $mesa, $data) {
            foreach ($pedidos as $p) {
                $p->update([
                    'estado' => 'pagado',
                    'metodo_pago' => $data['metodo_pago'],
                    'pagado_at' => now(),
                ]);
                $p->load('items.producto.recetas.insumo');
                foreach ($p->items as $item) {
                    foreach ($item->producto->recetas ?? [] as $r) {
                        if ($r->insumo) {
                            InsumoMovimiento::registrar($r->insumo, 'salida', (float) $r->cantidad * $item->cantidad, 'Cobro', $p->codigo, auth()->id());
                        }
                    }
                }
            }
            $mesa->update(['estado' => 'libre']);
        });
        $total = number_format((float) $pedidos->sum('total'), 2);
        return back()->with('success', "Mesa {$mesa->numero} cobrada. Total \${$total}.");
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
