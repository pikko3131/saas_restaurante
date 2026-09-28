<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Insumo;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Restaurante;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de demostración adicionales para poblar el dashboard.
 * Agrega ~10 registros por módulo y pedidos repartidos en distintas
 * fechas y horas (hoy, últimos 7 días y últimos 30 días).
 *
 * Ejecutar:  php artisan db:seed --class=DemoDataSeeder
 * Es idempotente: usar varias veces no duplica registros.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $rest = Restaurante::where('slug', 'mi-restaurante-vip')->first()
            ?? Restaurante::first();

        if (! $rest) {
            $this->command->warn('No hay restaurante. Corre primero: php artisan db:seed');
            return;
        }

        app()->instance('currentTenantId', $rest->id);
        app()->instance('currentTenant', $rest);

        try {
            $this->categorias();
            $this->productos();
            $this->mesas();
            $this->clientes();
            $this->insumos();
            $this->reservas();
            $this->pedidos($rest);
            $this->recetas();
            $this->caja($rest);
            $this->command->info('✅ DemoDataSeeder completado para: '.$rest->nombre);
        } finally {
            app()->forgetInstance('currentTenantId');
            app()->forgetInstance('currentTenant');
        }
    }

    private function categorias(): void
    {
        $items = [
            ['Pastas', '🍝', '#ea580c'], ['Ensaladas', '🥗', '#16a34a'], ['Pizzas', '🍕', '#dc2626'],
            ['Mariscos', '🦐', '#0ea5e9'], ['Vegetariano', '🥦', '#22c55e'], ['Sándwiches', '🥪', '#f59e0b'],
            ['Desayunos', '🍳', '#fbbf24'], ['Menú Infantil', '🧒', '#a855f7'], ['Tragos', '🍹', '#ec4899'],
            ['Cafés', '☕', '#92400e'],
        ];
        foreach ($items as $i => $c) {
            Categoria::firstOrCreate(['nombre' => $c[0]], [
                'icono' => $c[1], 'color' => $c[2], 'orden' => 10 + $i, 'activo' => true,
            ]);
        }
    }

    private function productos(): void
    {
        $items = [
            ['Pastas', 'Fettuccine Alfredo', 30, 11], ['Pastas', 'Lasaña de Carne', 34, 13],
            ['Ensaladas', 'Ensalada César', 24, 8], ['Pizzas', 'Pizza Margarita', 36, 12],
            ['Pizzas', 'Pizza Americana', 40, 14], ['Mariscos', 'Ceviche Mixto', 42, 16],
            ['Mariscos', 'Chicharrón de Pescado', 38, 14], ['Vegetariano', 'Risotto de Champiñones', 32, 10],
            ['Sándwiches', 'Sándwich de Lomo', 22, 8], ['Desayunos', 'Desayuno Americano', 26, 9],
            ['Tragos', 'Pisco Sour', 20, 6], ['Cafés', 'Capuccino', 10, 2.5],
        ];
        foreach ($items as $p) {
            $cat = Categoria::where('nombre', $p[0])->first();
            if (! $cat) continue;
            Producto::firstOrCreate(['nombre' => $p[1]], [
                'categoria_id' => $cat->id, 'precio' => $p[2], 'costo' => $p[3],
                'disponible' => true, 'vendidos' => rand(10, 90),
            ]);
        }
    }

    private function mesas(): void
    {
        $estados = ['libre', 'ocupada', 'reservada', 'cuenta', 'libre', 'ocupada'];
        $zonas = ['Salón principal', 'Terraza', 'Privados', 'Barra'];
        for ($i = 13; $i <= 22; $i++) {
            Mesa::firstOrCreate(['numero' => (string) $i], [
                'nombre' => 'Mesa '.$i,
                'capacidad' => [2, 4, 6, 8][array_rand([2, 4, 6, 8])],
                'zona' => $zonas[array_rand($zonas)],
                'estado' => $estados[array_rand($estados)],
            ]);
        }
    }

    private function clientes(): void
    {
        $nombres = [
            ['Lucía Fernández', '40112233'], ['Diego Castillo', '41223344'], ['Valeria Ríos', '42334455'],
            ['Andrés Mendoza', '43445566'], ['Camila Vargas', '44556677'], ['Renzo Salazar', '45667788'],
            ['Fiorella Chávez', '46778899'], ['Gonzalo Pérez', '47889900'], ['Inversiones Sur SAC', '20600112233'],
            ['Martín Aguirre', '48990011'],
        ];
        foreach ($nombres as $i => $c) {
            $cliente = Cliente::firstOrCreate(['nombre' => $c[0]], [
                'documento' => $c[1],
                'telefono' => '9'.str_pad((string) rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                'puntos' => rand(0, 600),
            ]);
            // Fechas de registro repartidas (algunos hoy → "Clientes nuevos")
            $fecha = $i < 3 ? now() : now()->subDays(rand(1, 25));
            $cliente->forceFill(['created_at' => $fecha])->saveQuietly();
        }
    }

    private function insumos(): void
    {
        $items = [
            ['Tomate', 'kg', 18, 5, 3.5], ['Cebolla', 'kg', 22, 6, 3.0], ['Queso mozzarella', 'kg', 9, 4, 22],
            ['Harina', 'kg', 35, 10, 3.2], ['Lechuga', 'und', 30, 8, 2.0], ['Camarón', 'kg', 7, 3, 45],
            ['Pescado fresco', 'kg', 12, 5, 26], ['Leche', 'lt', 25, 8, 4.5], ['Huevos', 'und', 120, 30, 0.5],
            ['Café en grano', 'kg', 6, 2, 38],
        ];
        foreach ($items as $x) {
            Insumo::firstOrCreate(['nombre' => $x[0]], [
                'unidad' => $x[1], 'stock' => $x[2], 'stock_minimo' => $x[3], 'costo' => $x[4],
            ]);
        }
    }

    private function reservas(): void
    {
        $nombres = ['Familia Quispe', 'Empresa Norte', 'Sra. Pacheco', 'Grupo Universitario', 'Sr. Linares',
            'Cumpleaños Ana', 'Reunión Ventas', 'Familia Rojas', 'Pareja Aniversario', 'Delegación Cusco'];
        foreach ($nombres as $i => $n) {
            Reserva::firstOrCreate(
                ['nombre_cliente' => $n, 'notas' => 'demo-seed'],
                [
                    'telefono' => '9'.str_pad((string) rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'mesa_id' => Mesa::inRandomOrder()->first()?->id,
                    'fecha' => now()->addDays(rand(-3, 10))->toDateString(),
                    'hora' => sprintf('%02d:%02d:00', rand(12, 21), [0, 30][array_rand([0, 30])]),
                    'personas' => rand(2, 10),
                    'estado' => ['pendiente', 'confirmada', 'cumplida'][array_rand(['pendiente', 'confirmada', 'cumplida'])],
                ]
            );
        }
    }

    private function pedidos(Restaurante $rest): void
    {
        $productos = Producto::all();
        if ($productos->isEmpty()) return;
        $mesas = Mesa::pluck('id')->all();
        $clientes = Cliente::pluck('id')->all();
        $igv = (float) $rest->igv;

        $crear = function (Carbon $fecha, string $estado, int $n) use ($productos, $mesas, $clientes, $igv) {
            $codigo = 'DEMO-'.$fecha->format('ymdHis').'-'.$n;
            if (Pedido::where('codigo', $codigo)->exists()) return;

            $pedido = Pedido::create([
                'codigo' => $codigo,
                'mesa_id' => $mesas ? $mesas[array_rand($mesas)] : null,
                'cliente_id' => (rand(0, 1) && $clientes) ? $clientes[array_rand($clientes)] : null,
                'user_id' => optional(\App\Models\User::where('role', 'admin')->first())->id,
                'tipo' => ['mesa', 'mesa', 'llevar', 'delivery'][array_rand(['mesa', 'mesa', 'llevar', 'delivery'])],
                'estado' => $estado,
                'metodo_pago' => $estado === 'pagado' ? ['efectivo', 'tarjeta', 'yape', 'plin'][array_rand(['efectivo', 'tarjeta', 'yape', 'plin'])] : null,
                'created_at' => $fecha, 'updated_at' => $fecha,
                'pagado_at' => $estado === 'pagado' ? $fecha : null,
            ]);
            $subtotal = 0;
            foreach ($productos->random(rand(1, 4)) as $prod) {
                $cant = rand(1, 3);
                $sub = $cant * (float) $prod->precio;
                $subtotal += $sub;
                PedidoItem::create([
                    'pedido_id' => $pedido->id, 'producto_id' => $prod->id, 'nombre_producto' => $prod->nombre,
                    'cantidad' => $cant, 'precio' => $prod->precio, 'subtotal' => $sub,
                ]);
            }
            $imp = round($subtotal * ($igv / 100), 2);
            $pedido->update(['subtotal' => $subtotal, 'impuesto' => $imp, 'total' => $subtotal + $imp]);
        };

        // 10 pedidos HOY a distintas horas (pagados) → curva de "Actividad por hora"
        $horasHoy = [8, 9, 11, 12, 13, 14, 16, 19, 20, 21];
        foreach ($horasHoy as $k => $h) {
            $crear(today()->setTime($h, rand(0, 59)), 'pagado', $k + 1);
        }

        // 10 pedidos repartidos en los últimos 7 días (pagados) → tendencia semanal
        for ($i = 1; $i <= 10; $i++) {
            $f = today()->subDays(rand(1, 6))->setTime(rand(11, 22), rand(0, 59));
            $crear($f, 'pagado', 100 + $i);
        }

        // 10 pedidos repartidos en los últimos 30 días (pagados) → meta mensual / histórico
        for ($i = 1; $i <= 10; $i++) {
            $f = today()->subDays(rand(7, 29))->setTime(rand(11, 22), rand(0, 59));
            $crear($f, 'pagado', 200 + $i);
        }

        // 6 pedidos ACTIVOS de hoy → tarjeta "Pedidos activos" y tablero KDS
        $activos = ['pendiente', 'pendiente', 'preparando', 'preparando', 'servido', 'servido'];
        foreach ($activos as $k => $e) {
            $crear(today()->setTime(rand(11, 22), rand(0, 59)), $e, 300 + $k);
        }
    }

    private function recetas(): void
    {
        if (! Schema::hasTable('recetas')) {
            $this->command->warn('Tabla "recetas" no existe aún. Corre: php artisan migrate');
            return;
        }
        $mapa = [
            'Pizza Margarita'   => [['Harina', 0.25], ['Queso mozzarella', 0.15], ['Tomate', 0.1]],
            'Lasaña de Carne'   => [['Carne de res', 0.2], ['Queso mozzarella', 0.1], ['Tomate', 0.1]],
            'Ceviche Mixto'     => [['Pescado fresco', 0.2], ['Cebolla', 0.05], ['Camarón', 0.08]],
            'Fettuccine Alfredo'=> [['Harina', 0.2], ['Leche', 0.1], ['Queso mozzarella', 0.05]],
            'Ensalada César'    => [['Lechuga', 0.5], ['Queso mozzarella', 0.03]],
            'Capuccino'         => [['Café en grano', 0.02], ['Leche', 0.15]],
            'Pizza Americana'   => [['Harina', 0.25], ['Queso mozzarella', 0.15], ['Tomate', 0.1]],
            'Desayuno Americano'=> [['Huevos', 2], ['Leche', 0.1]],
        ];
        foreach ($mapa as $nombreProd => $ingredientes) {
            $prod = Producto::where('nombre', $nombreProd)->first();
            if (! $prod) continue;
            foreach ($ingredientes as $ing) {
                $insumo = Insumo::where('nombre', $ing[0])->first();
                if (! $insumo) continue;
                \App\Models\Receta::firstOrCreate(
                    ['producto_id' => $prod->id, 'insumo_id' => $insumo->id],
                    ['cantidad' => $ing[1]]
                );
            }
            // Fijar costo real del producto según su receta
            $prod->load('recetas.insumo');
            $prod->update(['costo' => $prod->costoReceta()]);
        }
    }

    private function caja(Restaurante $rest): void
    {
        if (! Schema::hasTable('cajas')) {
            $this->command->warn('Tabla "cajas" no existe aún. Corre: php artisan migrate');
            return;
        }
        $admin = \App\Models\User::where('role', 'admin')->first();

        // 3 cajas cerradas en días anteriores
        for ($d = 3; $d >= 1; $d--) {
            $apertura = today()->subDays($d)->setTime(8, 0);
            $cierre = today()->subDays($d)->setTime(23, 0);
            $codigo = 'cajademo-'.$apertura->format('Ymd');
            if (\App\Models\Caja::where('notas_apertura', $codigo)->exists()) continue;

            $caja = \App\Models\Caja::create([
                'user_id' => $admin?->id, 'cerrada_por' => $admin?->id, 'estado' => 'cerrada',
                'monto_inicial' => 200, 'notas_apertura' => $codigo,
                'abierta_at' => $apertura, 'cerrada_at' => $cierre,
                'created_at' => $apertura, 'updated_at' => $cierre,
            ]);
            $caja->movimientos()->create(['user_id' => $admin?->id, 'tipo' => 'ingreso', 'concepto' => 'Cambio adicional', 'monto' => 100, 'metodo_pago' => 'efectivo', 'created_at' => $apertura, 'updated_at' => $apertura]);
            $caja->movimientos()->create(['user_id' => $admin?->id, 'tipo' => 'egreso', 'concepto' => 'Compra de insumos', 'monto' => 80, 'metodo_pago' => 'efectivo', 'created_at' => $cierre, 'updated_at' => $cierre]);
            $resumen = $caja->resumen();
            $caja->update([
                'efectivo_esperado' => $resumen['efectivo_esperado'],
                'monto_contado' => $resumen['efectivo_esperado'] + (($d % 2) ? -5 : 10),
                'diferencia' => (($d % 2) ? -5 : 10),
            ]);
        }

        // 1 caja abierta hoy (si no hay ya una abierta)
        if (! \App\Models\Caja::where('estado', 'abierta')->exists()) {
            $caja = \App\Models\Caja::create([
                'user_id' => $admin?->id, 'estado' => 'abierta', 'monto_inicial' => 250,
                'notas_apertura' => 'Apertura del día', 'abierta_at' => today()->setTime(8, 0),
            ]);
            $caja->movimientos()->create(['user_id' => $admin?->id, 'tipo' => 'ingreso', 'concepto' => 'Propinas en efectivo', 'monto' => 45, 'metodo_pago' => 'efectivo']);
            $caja->movimientos()->create(['user_id' => $admin?->id, 'tipo' => 'egreso', 'concepto' => 'Delivery de bebidas', 'monto' => 60, 'metodo_pago' => 'efectivo']);
        }
    }
}
