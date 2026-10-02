<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Restaurante;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        [$desde, $hasta, $data] = $this->datos($request);

        return view('modules.reportes.index', array_merge($data, [
            'config' => Restaurante::actual(),
            'desde' => $desde,
            'hasta' => $hasta,
        ]));
    }

    /** Exporta la rentabilidad por plato a CSV (compatible con Excel). */
    public function exportarRentabilidad(Request $request): StreamedResponse
    {
        [, , $data] = $this->datos($request);
        $filas = $data['rentabilidad'];

        $nombre = 'rentabilidad_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($filas) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para acentos en Excel
            fputcsv($out, ['Producto', 'Unidades', 'Ingresos', 'Costo unitario', 'Costo total', 'Ganancia', 'Margen %']);
            foreach ($filas as $r) {
                fputcsv($out, [
                    $r->nombre, $r->unidades,
                    number_format($r->ingreso, 2, '.', ''),
                    number_format($r->costo_unit, 2, '.', ''),
                    number_format($r->costo_total, 2, '.', ''),
                    number_format($r->ganancia, 2, '.', ''),
                    $r->margen,
                ]);
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Exporta el resumen de ventas a CSV. */
    public function exportarVentas(Request $request): StreamedResponse
    {
        [$desde, $hasta, $data] = $this->datos($request);
        $pedidos = Pedido::with(['user', 'cliente', 'mesa'])
            ->where('estado', 'pagado')
            ->whereBetween('pagado_at', [$desde, $hasta])
            ->latest('pagado_at')
            ->get();

        $nombre = 'ventas_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($pedidos) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Código', 'Fecha y Hora', 'Tipo', 'Mesa / Destino', 'Atendió', 'Cliente', 'Método Pago', 'Subtotal', 'Descuento', 'Impuesto', 'Total']);
            foreach ($pedidos as $p) {
                fputcsv($out, [
                    $p->codigo,
                    $p->pagado_at?->format('d/m/Y H:i') ?? $p->created_at->format('d/m/Y H:i'),
                    strtoupper($p->tipo),
                    $p->mesa?->nombre ?? ($p->tipo === 'delivery' ? $p->delivery_direccion : 'Mostrador'),
                    $p->user?->name ?? '—',
                    $p->cliente?->nombre ?? 'Genérico',
                    strtoupper($p->metodo_pago ?? '—'),
                    number_format($p->subtotal, 2, '.', ''),
                    number_format($p->descuento, 2, '.', ''),
                    number_format($p->impuesto, 2, '.', ''),
                    number_format($p->total, 2, '.', ''),
                ]);
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Vista imprimible (PDF vía navegador). */
    public function imprimir(Request $request)
    {
        [$desde, $hasta, $data] = $this->datos($request);

        return view('modules.reportes.imprimir', array_merge($data, [
            'config' => Restaurante::actual(),
            'desde' => $desde,
            'hasta' => $hasta,
        ]));
    }

    /** Cálculos compartidos por las vistas y exportaciones de los 8 reportes. */
    private function datos(Request $request): array
    {
        $desde = $request->date('desde') ?? now()->startOfMonth();
        $hasta = $request->date('hasta') ?? now()->endOfDay();
        $restauranteId = auth()->user()->restaurante_id;

        $base = Pedido::where('estado', 'pagado')->whereBetween('pagado_at', [$desde, $hasta]);

        // 1. Resumen general
        $totalVentas = (clone $base)->sum('total');
        $numPedidos = (clone $base)->count();
        $ticketPromedio = $numPedidos ? $totalVentas / $numPedidos : 0;

        // 2. Ventas por día
        $porDia = (clone $base)
            ->select(DB::raw('DATE(pagado_at) as d'), DB::raw('SUM(total) as t'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')->orderBy('d')->get();

        // 3. Ventas por hora del día (horas pico)
        $porHora = (clone $base)
            ->select(DB::raw('HOUR(pagado_at) as h'), DB::raw('SUM(total) as t'), DB::raw('COUNT(*) as c'))
            ->groupBy('h')->orderBy('h')->get();

        // 4. Ventas por método de pago
        $porMetodo = (clone $base)
            ->select('metodo_pago', DB::raw('SUM(total) as t'), DB::raw('COUNT(*) as c'))
            ->groupBy('metodo_pago')
            ->orderByDesc('t')
            ->get();

        // 5. Ventas por tipo de pedido (Mesa vs Llevar vs Delivery)
        $porTipo = (clone $base)
            ->select('tipo', DB::raw('SUM(total) as t'), DB::raw('COUNT(*) as c'))
            ->groupBy('tipo')
            ->orderByDesc('t')
            ->get();

        // 6. Ventas por Colaborador (Meseros / Cajeros)
        $porMesero = (clone $base)
            ->whereNotNull('pedidos.user_id')
            ->join('users', 'users.id', '=', 'pedidos.user_id')
            ->select(
                'users.name as nombre',
                'users.role as rol',
                DB::raw('COUNT(pedidos.id) as pedidos_count'),
                DB::raw('SUM(pedidos.total) as total_ventas'),
                DB::raw('AVG(pedidos.total) as ticket_promedio')
            )
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('total_ventas')
            ->get();

        // 7. Top 10 productos vendidos
        $topProductos = PedidoItem::select('nombre_producto', DB::raw('SUM(cantidad) as q'), DB::raw('SUM(subtotal) as t'))
            ->whereHas('pedido', fn ($qq) => $qq->where('estado', 'pagado')->whereBetween('pagado_at', [$desde, $hasta]))
            ->groupBy('nombre_producto')->orderByDesc('t')->take(10)->get();

        // 8. Rentabilidad por plato
        $rentabilidad = PedidoItem::query()
            ->join('productos', 'productos.id', '=', 'pedido_items.producto_id')
            ->whereHas('pedido', fn ($qq) => $qq->where('estado', 'pagado')->whereBetween('pagado_at', [$desde, $hasta]))
            ->groupBy('productos.id', 'productos.nombre')
            ->select(
                'productos.nombre as nombre',
                DB::raw('SUM(pedido_items.cantidad) as unidades'),
                DB::raw('SUM(pedido_items.subtotal) as ingreso'),
                DB::raw('MAX(productos.costo) as costo_unit'),
                DB::raw('SUM(pedido_items.cantidad * productos.costo) as costo_total')
            )
            ->orderByDesc(DB::raw('SUM(pedido_items.subtotal) - SUM(pedido_items.cantidad * productos.costo)'))
            ->get()
            ->map(function ($r) {
                $r->ingreso = (float) $r->ingreso;
                $r->costo_total = (float) $r->costo_total;
                $r->costo_unit = (float) $r->costo_unit;
                $r->ganancia = round($r->ingreso - $r->costo_total, 2);
                $r->margen = $r->ingreso > 0 ? round($r->ganancia / $r->ingreso * 100, 1) : 0;
                return $r;
            });

        $ingresoTotalR = $rentabilidad->sum('ingreso');
        $costoTotalR = $rentabilidad->sum('costo_total');
        $gananciaTotalR = round($ingresoTotalR - $costoTotalR, 2);
        $margenTotalR = $ingresoTotalR > 0 ? round($gananciaTotalR / $ingresoTotalR * 100, 1) : 0;

        // 9. Reporte de Consumo de Inventario (Kardex salidas)
        $insumosConsumo = DB::table('insumo_movimientos')
            ->join('insumos', 'insumos.id', '=', 'insumo_movimientos.insumo_id')
            ->where('insumo_movimientos.restaurante_id', $restauranteId)
            ->where('insumo_movimientos.tipo', 'salida')
            ->whereBetween('insumo_movimientos.created_at', [$desde, $hasta])
            ->select(
                'insumos.nombre',
                'insumos.unidad',
                'insumos.stock',
                'insumos.stock_minimo',
                DB::raw('SUM(insumo_movimientos.cantidad) as consumido'),
                DB::raw('SUM(insumo_movimientos.cantidad * insumos.costo) as costo_consumo')
            )
            ->groupBy('insumos.id', 'insumos.nombre', 'insumos.unidad', 'insumos.stock', 'insumos.stock_minimo')
            ->orderByDesc('costo_consumo')
            ->take(15)
            ->get();

        // 10. Reporte de Clientes y Fidelización
        $topClientes = (clone $base)
            ->whereNotNull('cliente_id')
            ->join('clientes', 'clientes.id', '=', 'pedidos.cliente_id')
            ->select(
                'clientes.nombre',
                'clientes.telefono',
                'clientes.puntos',
                DB::raw('COUNT(pedidos.id) as pedidos_count'),
                DB::raw('SUM(pedidos.total) as total_consumido'),
                DB::raw('SUM(pedidos.puntos_ganados) as puntos_ganados'),
                DB::raw('SUM(pedidos.puntos_usados) as puntos_canjeados')
            )
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.telefono', 'clientes.puntos')
            ->orderByDesc('total_consumido')
            ->take(15)
            ->get();

        // 11. Reporte de Cierres de Caja / Arqueos en el período
        $cierresCaja = Caja::with('user')
            ->where('restaurante_id', $restauranteId)
            ->whereBetween('created_at', [$desde, $hasta])
            ->latest()
            ->get();

        return [$desde, $hasta, compact(
            'totalVentas', 'numPedidos', 'ticketPromedio', 'porDia', 'porHora', 'porMetodo', 'porTipo',
            'porMesero', 'topProductos', 'rentabilidad', 'ingresoTotalR', 'costoTotalR', 'gananciaTotalR',
            'margenTotalR', 'insumosConsumo', 'topClientes', 'cierresCaja'
        )];
    }
}
