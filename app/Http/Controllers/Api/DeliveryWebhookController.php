<?php

namespace App\Http\Controllers\Api;

use App\Events\PedidoCambio;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\InsumoMovimiento;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Restaurante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryWebhookController extends Controller
{
    /**
     * Webhook receptor de pedidos de Rappi.
     * Endpoint: POST /webhooks/rappi
     */
    public function rappi(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Webhook Rappi recibido:', ['payload' => $payload]);

        // 1. Identificar restaurante por store_id o slug
        $storeId = $request->header('X-Rappi-Store-Id')
            ?? $payload['store_id']
            ?? $payload['order']['store_id']
            ?? $request->query('store_id');

        $restaurante = null;
        if ($storeId) {
            $restaurante = Restaurante::where('rappi_store_id', $storeId)->first();
        }

        if (! $restaurante && $request->has('restaurante_id')) {
            $restaurante = Restaurante::find($request->input('restaurante_id'));
        }

        if (! $restaurante) {
            $restaurante = Restaurante::first();
        }

        if (! $restaurante) {
            return response()->json(['error' => 'Restaurante no encontrado'], 404);
        }

        // 2. Validación de firma HMAC si el restaurante tiene configurado secret
        if ($restaurante->rappi_webhook_secret) {
            $signature = $request->header('X-Rappi-Signature');
            $computed = hash_hmac('sha256', $request->getContent(), $restaurante->rappi_webhook_secret);
            if ($signature && ! hash_equals($computed, $signature)) {
                Log::warning('Firma Rappi inválida:', ['store' => $storeId]);
                return response()->json(['error' => 'Firma inválida'], 401);
            }
        }

        // 3. Normalizar datos del pedido
        $orderData = $payload['order'] ?? $payload;
        $orderId = (string) ($orderData['order_id'] ?? $orderData['id'] ?? ('RAP-'.time()));
        $eventType = $payload['event'] ?? $payload['type'] ?? 'order_created';

        // Manejo de cancelación remota desde Rappi
        if (in_array($eventType, ['order_cancelled', 'order_canceled', 'canceled'])) {
            $pedido = Pedido::withoutGlobalScopes()
                ->where('restaurante_id', $restaurante->id)
                ->where('delivery_externo_id', $orderId)
                ->first();

            if ($pedido && $pedido->estado !== 'cancelado') {
                $pedido->update(['estado' => 'cancelado', 'delivery_estado' => 'cancelado']);
                $pedido->restaurarInventario();
                event(new PedidoCambio($pedido->fresh(['items']), 'cancelado'));
            }

            return response()->json(['status' => 'cancelled', 'order_id' => $orderId]);
        }

        // Verificar si ya existe este pedido para evitar duplicados
        $existente = Pedido::withoutGlobalScopes()
            ->where('restaurante_id', $restaurante->id)
            ->where('delivery_externo_id', $orderId)
            ->first();

        if ($existente) {
            return response()->json(['status' => 'already_exists', 'codigo' => $existente->codigo], 200);
        }

        // Cliente y entrega
        $clienteInfo = $orderData['customer'] ?? [];
        $clienteNombre = trim(($clienteInfo['first_name'] ?? '').' '.($clienteInfo['last_name'] ?? '')) ?: 'Cliente Rappi';
        $clienteTel = $clienteInfo['phone_number'] ?? $clienteInfo['phone'] ?? null;

        $deliveryInfo = $orderData['delivery_information'] ?? $orderData['delivery_address'] ?? [];
        $direccion = is_array($deliveryInfo)
            ? ($deliveryInfo['street_name'] ?? $deliveryInfo['address'] ?? 'Dirección Rappi').' '.($deliveryInfo['street_number'] ?? '')
            : (string) $deliveryInfo;
        $referencia = is_array($deliveryInfo) ? ($deliveryInfo['complement'] ?? $deliveryInfo['reference'] ?? null) : null;

        $repartidorInfo = $orderData['courier'] ?? $orderData['driver'] ?? [];
        $repartidor = is_array($repartidorInfo) ? ($repartidorInfo['name'] ?? 'Repartidor Rappi') : null;

        $costoEnvio = (float) ($orderData['delivery_fee'] ?? $orderData['delivery_cost'] ?? 0);
        $itemsData = $orderData['items'] ?? $orderData['products'] ?? [];

        // 4. Crear el pedido
        $pedido = DB::transaction(function () use ($restaurante, $orderId, $clienteNombre, $clienteTel, $direccion, $referencia, $repartidor, $costoEnvio, $itemsData, $payload) {
            // Resolver o registrar cliente
            $cliente = Cliente::withoutGlobalScopes()
                ->where('restaurante_id', $restaurante->id)
                ->where('nombre', $clienteNombre)
                ->first();

            if (! $cliente && $clienteTel) {
                $cliente = Cliente::withoutGlobalScopes()
                    ->where('restaurante_id', $restaurante->id)
                    ->where('telefono', $clienteTel)
                    ->first();
            }

            if (! $cliente) {
                $cliente = Cliente::create([
                    'restaurante_id' => $restaurante->id,
                    'nombre' => $clienteNombre,
                    'telefono' => $clienteTel,
                    'direccion' => $direccion,
                    'puntos' => 0,
                ]);
            }

            $codigo = 'RAP-'.strtoupper(substr(uniqid(), -6));
            $subtotal = 0;
            $itemsToCreate = [];

            foreach ($itemsData as $it) {
                $nombreItem = $it['name'] ?? $it['title'] ?? 'Platillo';
                $cant = (int) ($it['quantity'] ?? $it['qty'] ?? 1);
                $precio = (float) ($it['price'] ?? $it['unit_price'] ?? 0);

                // Buscar producto en carta para asociar receta/stock
                $prod = Producto::withoutGlobalScopes()
                    ->where('restaurante_id', $restaurante->id)
                    ->where('nombre', 'LIKE', '%'.$nombreItem.'%')
                    ->first();

                if ($prod && $precio <= 0) {
                    $precio = (float) $prod->precio;
                }

                $lineSub = round($precio * $cant, 2);
                $subtotal += $lineSub;

                $itemsToCreate[] = [
                    'producto' => $prod,
                    'nombre' => $nombreItem,
                    'cantidad' => $cant,
                    'precio' => $precio,
                    'subtotal' => $lineSub,
                    'notas' => $it['notes'] ?? $it['comments'] ?? null,
                ];
            }

            $ivaRate = ((float) $restaurante->igv) / 100;
            $impuesto = round($subtotal * $ivaRate, 2);
            $total = $subtotal + $impuesto + $costoEnvio;

            $adminUser = $restaurante->usuarios()->where('role', 'admin')->first()
                ?? $restaurante->usuarios()->first();

            $pedido = Pedido::create([
                'restaurante_id' => $restaurante->id,
                'codigo' => $codigo,
                'mesa_id' => null,
                'cliente_id' => $cliente->id,
                'user_id' => $adminUser?->id,
                'tipo' => 'delivery',
                'estado' => 'preparando',
                'metodo_pago' => 'tarjeta', // Pagado en plataforma Rappi
                'pagado_at' => now(),
                'subtotal' => $subtotal,
                'descuento' => 0,
                'impuesto' => $impuesto,
                'total' => $total,
                'delivery_direccion' => $direccion,
                'delivery_telefono' => $clienteTel,
                'delivery_referencia' => $referencia,
                'delivery_costo_envio' => $costoEnvio,
                'delivery_repartidor' => $repartidor,
                'delivery_estado' => 'pendiente',
                'delivery_plataforma' => 'rappi',
                'delivery_externo_id' => $orderId,
                'delivery_datos_json' => $payload,
                'notas' => 'Pedido importado desde Rappi (#'.$orderId.')',
            ]);

            foreach ($itemsToCreate as $it) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $it['producto']?->id,
                    'nombre_producto' => $it['nombre'],
                    'cantidad' => $it['cantidad'],
                    'precio' => $it['precio'],
                    'subtotal' => $it['subtotal'],
                    'notas' => $it['notas'],
                ]);

                if ($it['producto']) {
                    $it['producto']->increment('vendidos', $it['cantidad']);
                    if ($it['producto']->controla_stock) {
                        $it['producto']->decrement('stock', $it['cantidad']);
                    }
                    foreach ($it['producto']->recetas as $r) {
                        if ($r->insumo) {
                            InsumoMovimiento::registrar(
                                $r->insumo,
                                'salida',
                                (float) $r->cantidad * $it['cantidad'],
                                'Venta Rappi',
                                $pedido->codigo,
                                $adminUser?->id
                            );
                        }
                    }
                }
            }

            return $pedido;
        });

        event(new PedidoCambio($pedido->fresh(['items', 'cliente']), 'creado'));

        return response()->json([
            'status' => 'success',
            'order_id' => $orderId,
            'local_id' => $pedido->id,
            'codigo' => $pedido->codigo,
        ], 201);
    }

    /**
     * Webhook receptor de pedidos de Uber Eats.
     * Endpoint: POST /webhooks/ubereats
     */
    public function ubereats(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Webhook Uber Eats recibido:', ['payload' => $payload]);

        $storeId = $request->header('X-Uber-Store-Id')
            ?? $payload['store_id']
            ?? $payload['eats_order']['store_id']
            ?? $request->query('store_id');

        $restaurante = null;
        if ($storeId) {
            $restaurante = Restaurante::where('ubereats_store_id', $storeId)->first();
        }

        if (! $restaurante && $request->has('restaurante_id')) {
            $restaurante = Restaurante::find($request->input('restaurante_id'));
        }

        if (! $restaurante) {
            $restaurante = Restaurante::first();
        }

        if (! $restaurante) {
            return response()->json(['error' => 'Restaurante no encontrado'], 404);
        }

        // Validación de firma HMAC si está configurada
        if ($restaurante->ubereats_client_secret) {
            $signature = $request->header('X-Uber-Signature');
            $computed = hash_hmac('sha256', $request->getContent(), $restaurante->ubereats_client_secret);
            if ($signature && ! hash_equals($computed, $signature)) {
                Log::warning('Firma Uber Eats inválida:', ['store' => $storeId]);
                return response()->json(['error' => 'Firma inválida'], 401);
            }
        }

        $orderData = $payload['eats_order'] ?? $payload['order'] ?? $payload;
        $orderId = (string) ($orderData['id'] ?? $orderData['order_id'] ?? ('EATS-'.time()));
        $eventType = $payload['event_type'] ?? $payload['type'] ?? 'orders.notification';

        if (str_contains(strtolower($eventType), 'cancel')) {
            $pedido = Pedido::withoutGlobalScopes()
                ->where('restaurante_id', $restaurante->id)
                ->where('delivery_externo_id', $orderId)
                ->first();

            if ($pedido && $pedido->estado !== 'cancelado') {
                $pedido->update(['estado' => 'cancelado', 'delivery_estado' => 'cancelado']);
                $pedido->restaurarInventario();
                event(new PedidoCambio($pedido->fresh(['items']), 'cancelado'));
            }

            return response()->json(['status' => 'cancelled', 'order_id' => $orderId]);
        }

        $existente = Pedido::withoutGlobalScopes()
            ->where('restaurante_id', $restaurante->id)
            ->where('delivery_externo_id', $orderId)
            ->first();

        if ($existente) {
            return response()->json(['status' => 'already_exists', 'codigo' => $existente->codigo], 200);
        }

        $eater = $orderData['eater'] ?? [];
        $clienteNombre = trim(($eater['first_name'] ?? '').' '.($eater['last_name'] ?? '')) ?: 'Cliente Uber Eats';
        $clienteTel = $eater['phone'] ?? $eater['phone_code'] ?? null;

        $deliveryInfo = $orderData['delivery'] ?? $orderData['destination'] ?? [];
        $direccion = is_array($deliveryInfo)
            ? ($deliveryInfo['street_address'] ?? $deliveryInfo['address'] ?? 'Dirección Uber Eats')
            : (string) $deliveryInfo;
        $referencia = is_array($deliveryInfo) ? ($deliveryInfo['unit_or_apartment'] ?? $deliveryInfo['notes'] ?? null) : null;

        $costoEnvio = 0; // En Uber Eats el delivery es gestionado por la app
        $cart = $orderData['cart'] ?? [];
        $itemsData = $cart['items'] ?? $orderData['items'] ?? [];

        $pedido = DB::transaction(function () use ($restaurante, $orderId, $clienteNombre, $clienteTel, $direccion, $referencia, $costoEnvio, $itemsData, $payload) {
            $cliente = Cliente::withoutGlobalScopes()
                ->where('restaurante_id', $restaurante->id)
                ->where('nombre', $clienteNombre)
                ->first();

            if (! $cliente) {
                $cliente = Cliente::create([
                    'restaurante_id' => $restaurante->id,
                    'nombre' => $clienteNombre,
                    'telefono' => $clienteTel,
                    'direccion' => $direccion,
                    'puntos' => 0,
                ]);
            }

            $codigo = 'EATS-'.strtoupper(substr(uniqid(), -6));
            $subtotal = 0;
            $itemsToCreate = [];

            foreach ($itemsData as $it) {
                $nombreItem = $it['title'] ?? $it['name'] ?? 'Platillo';
                $cant = (int) ($it['quantity'] ?? 1);
                $priceData = $it['price'] ?? [];
                $precio = is_array($priceData) ? (float) ($priceData['amount'] ?? 0) / 100 : (float) $priceData;

                $prod = Producto::withoutGlobalScopes()
                    ->where('restaurante_id', $restaurante->id)
                    ->where('nombre', 'LIKE', '%'.$nombreItem.'%')
                    ->first();

                if ($prod && $precio <= 0) {
                    $precio = (float) $prod->precio;
                }

                $lineSub = round($precio * $cant, 2);
                $subtotal += $lineSub;

                $itemsToCreate[] = [
                    'producto' => $prod,
                    'nombre' => $nombreItem,
                    'cantidad' => $cant,
                    'precio' => $precio,
                    'subtotal' => $lineSub,
                    'notas' => $it['special_instructions'] ?? null,
                ];
            }

            $ivaRate = ((float) $restaurante->igv) / 100;
            $impuesto = round($subtotal * $ivaRate, 2);
            $total = $subtotal + $impuesto + $costoEnvio;

            $adminUser = $restaurante->usuarios()->where('role', 'admin')->first()
                ?? $restaurante->usuarios()->first();

            $pedido = Pedido::create([
                'restaurante_id' => $restaurante->id,
                'codigo' => $codigo,
                'mesa_id' => null,
                'cliente_id' => $cliente->id,
                'user_id' => $adminUser?->id,
                'tipo' => 'delivery',
                'estado' => 'preparando',
                'metodo_pago' => 'tarjeta',
                'pagado_at' => now(),
                'subtotal' => $subtotal,
                'descuento' => 0,
                'impuesto' => $impuesto,
                'total' => $total,
                'delivery_direccion' => $direccion,
                'delivery_telefono' => $clienteTel,
                'delivery_referencia' => $referencia,
                'delivery_costo_envio' => $costoEnvio,
                'delivery_repartidor' => 'Repartidor Uber Eats',
                'delivery_estado' => 'pendiente',
                'delivery_plataforma' => 'ubereats',
                'delivery_externo_id' => $orderId,
                'delivery_datos_json' => $payload,
                'notas' => 'Pedido importado desde Uber Eats (#'.$orderId.')',
            ]);

            foreach ($itemsToCreate as $it) {
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $it['producto']?->id,
                    'nombre_producto' => $it['nombre'],
                    'cantidad' => $it['cantidad'],
                    'precio' => $it['precio'],
                    'subtotal' => $it['subtotal'],
                    'notas' => $it['notas'],
                ]);

                if ($it['producto']) {
                    $it['producto']->increment('vendidos', $it['cantidad']);
                    if ($it['producto']->controla_stock) {
                        $it['producto']->decrement('stock', $it['cantidad']);
                    }
                    foreach ($it['producto']->recetas as $r) {
                        if ($r->insumo) {
                            InsumoMovimiento::registrar(
                                $r->insumo,
                                'salida',
                                (float) $r->cantidad * $it['cantidad'],
                                'Venta Uber Eats',
                                $pedido->codigo,
                                $adminUser?->id
                            );
                        }
                    }
                }
            }

            return $pedido;
        });

        event(new PedidoCambio($pedido->fresh(['items', 'cliente']), 'creado'));

        return response()->json([
            'status' => 'success',
            'order_id' => $orderId,
            'local_id' => $pedido->id,
            'codigo' => $pedido->codigo,
        ], 201);
    }

    /**
     * Endpoint para simular pedido entrante de Rappi o Uber Eats desde la app.
     * POST /delivery/simular-plataforma
     */
    public function simular(Request $request): JsonResponse
    {
        $plataforma = $request->input('plataforma', 'rappi');
        $restaurante = Restaurante::actual() ?? Restaurante::first();

        if (! $restaurante) {
            return response()->json(['error' => 'Sin restaurante'], 400);
        }

        $productos = Producto::withoutGlobalScopes()
            ->where('restaurante_id', $restaurante->id)
            ->where('disponible', true)
            ->inRandomOrder()
            ->take(2)
            ->get();

        if ($productos->isEmpty()) {
            return response()->json(['error' => 'No hay productos disponibles en carta'], 400);
        }

        $calles = [
            'Av. Paseo de la Reforma 222, Cuauhtémoc, CDMX',
            'Calle Colima 145, Roma Norte, CDMX',
            'Av. Insurgentes Sur 1602, Crédito Constructor, CDMX',
            'Calle Orizaba 89, Roma Norte, CDMX',
            'Av. División del Norte 1200, Del Valle, CDMX',
        ];
        $nombres = ['Carlos Mendoza', 'Ana Sofía Garza', 'Miguel Ángel Morales', 'Daniela Treviño', 'Rodrigo Alarcón'];

        $calle = $calles[array_rand($calles)];
        $cliente = $nombres[array_rand($nombres)];
        $tel = '55'.rand(10000000, 99999999);
        $orderExtId = strtoupper($plataforma).'-'.rand(100000, 999999);

        if ($plataforma === 'rappi') {
            $items = [];
            foreach ($productos as $p) {
                $items[] = [
                    'name' => $p->nombre,
                    'quantity' => rand(1, 2),
                    'unit_price' => (float) $p->precio,
                    'notes' => 'Sin cebolla por favor',
                ];
            }

            $simRequest = new Request([], [
                'order' => [
                    'order_id' => $orderExtId,
                    'store_id' => $restaurante->rappi_store_id ?: 'TEST-STORE',
                    'customer' => [
                        'first_name' => explode(' ', $cliente)[0],
                        'last_name' => explode(' ', $cliente)[1] ?? '',
                        'phone_number' => $tel,
                    ],
                    'delivery_information' => [
                        'street_name' => $calle,
                        'complement' => 'Depto 302, timbre 5',
                    ],
                    'courier' => [
                        'name' => 'Repartidor Rappi (Simulado)',
                    ],
                    'delivery_fee' => 35.00,
                    'items' => $items,
                ],
                'restaurante_id' => $restaurante->id,
            ]);

            return $this->rappi($simRequest);
        } else {
            // Uber Eats
            $items = [];
            foreach ($productos as $p) {
                $items[] = [
                    'title' => $p->nombre,
                    'quantity' => rand(1, 2),
                    'price' => [
                        'amount' => ((float) $p->precio) * 100,
                    ],
                    'special_instructions' => 'Salsa aparte',
                ];
            }

            $simRequest = new Request([], [
                'eats_order' => [
                    'id' => $orderExtId,
                    'store_id' => $restaurante->ubereats_store_id ?: 'TEST-STORE',
                    'eater' => [
                        'first_name' => explode(' ', $cliente)[0],
                        'last_name' => explode(' ', $cliente)[1] ?? '',
                        'phone' => $tel,
                    ],
                    'destination' => [
                        'street_address' => $calle,
                        'unit_or_apartment' => 'Piso 4, Oficina B',
                    ],
                    'cart' => [
                        'items' => $items,
                    ],
                ],
                'restaurante_id' => $restaurante->id,
            ]);

            return $this->ubereats($simRequest);
        }
    }
}
