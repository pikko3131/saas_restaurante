<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Restaurante;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->query('estado', 'todos');

        $query = Pedido::with(['cliente', 'user'])
            ->where('tipo', 'delivery')
            ->latest();

        if ($estado !== 'todos') {
            $query->where('delivery_estado', $estado);
        }

        $pedidos = $query->paginate(15)->withQueryString();

        // Contadores por estado
        $conteo = [
            'todos' => Pedido::where('tipo', 'delivery')->count(),
            'pendiente' => Pedido::where('tipo', 'delivery')->where('delivery_estado', 'pendiente')->count(),
            'en_camino' => Pedido::where('tipo', 'delivery')->where('delivery_estado', 'en_camino')->count(),
            'entregado' => Pedido::where('tipo', 'delivery')->where('delivery_estado', 'entregado')->count(),
        ];

        $config = Restaurante::actual();

        return view('modules.delivery.index', compact('pedidos', 'estado', 'conteo', 'config'));
    }

    public function cambiarEstado(Request $request, Pedido $pedido)
    {
        $data = $request->validate([
            'delivery_estado' => 'required|in:pendiente,en_camino,entregado,cancelado',
            'delivery_repartidor' => 'nullable|string|max:100',
        ]);

        $pedido->delivery_estado = $data['delivery_estado'];

        if (! empty($data['delivery_repartidor'])) {
            $pedido->delivery_repartidor = $data['delivery_repartidor'];
        }

        // Si se marca como entregado y no estaba pagado pero se confirma pago
        if ($data['delivery_estado'] === 'entregado') {
            if ($request->boolean('marcar_pagado') && $pedido->estado !== 'pagado') {
                $pedido->estado = 'pagado';
                $pedido->pagado_at = now();
                if ($pedido->cliente) {
                    $ganados = $pedido->cliente->acreditarPuntos((float) $pedido->total, $pedido);
                    $pedido->puntos_ganados = $ganados;
                }
            }
        }

        $pedido->save();

        return back()->with('success', "Estado del delivery {$pedido->codigo} actualizado.");
    }
}
