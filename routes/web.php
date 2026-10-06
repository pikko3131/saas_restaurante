<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CartaPublicaController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Modules\CartaQrController;
use App\Http\Controllers\Modules\BuscarController;
use App\Http\Controllers\Modules\CategoriaController;
use App\Http\Controllers\Modules\ClienteController;
use App\Http\Controllers\Modules\CajaController;
use App\Http\Controllers\Modules\CocinaController;
use App\Http\Controllers\Modules\ConfiguracionController;
use App\Http\Controllers\Modules\DeliveryController;
use App\Http\Controllers\Modules\InsumoController;
use App\Http\Controllers\Modules\KardexController;
use App\Http\Controllers\Modules\MesaController;
use App\Http\Controllers\Modules\MeseroController;
use App\Http\Controllers\Modules\PedidoController;
use App\Http\Controllers\Modules\PosController;
use App\Http\Controllers\Modules\ProductoController;
use App\Http\Controllers\Modules\PromocionController;
use App\Http\Controllers\Modules\RecetaController;
use App\Http\Controllers\Modules\ReporteController;
use App\Http\Controllers\Modules\ReservaController;
use App\Http\Controllers\Modules\SuscripcionController;
use App\Http\Controllers\Modules\UsuarioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperDashboard;
use App\Http\Controllers\SuperAdmin\PlanController as SuperPlan;
use App\Http\Controllers\SuperAdmin\ProfileController as SuperProfile;
use App\Http\Controllers\SuperAdmin\RestauranteController as SuperRestaurante;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/carta/{restaurante:slug}', [CartaPublicaController::class, 'show'])->name('carta.publica');
Route::post('/carta/{restaurante:slug}/pedido', [CartaPublicaController::class, 'pedido'])->name('carta.pedido');
Route::post('/webhooks/rappi', [\App\Http\Controllers\Api\DeliveryWebhookController::class, 'rappi'])->name('webhooks.rappi');
Route::post('/webhooks/ubereats', [\App\Http\Controllers\Api\DeliveryWebhookController::class, 'ubereats'])->name('webhooks.ubereats');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/suscripcion', [SuscripcionController::class, 'index'])->name('suscripcion.index');
    Route::post('/suscripcion/pagar', [SuscripcionController::class, 'pagar'])->name('suscripcion.pagar');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('suscrito')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/buscar', BuscarController::class)->name('buscar');
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');

        Route::middleware('role:mesero,admin,cajero')->group(function () {
            Route::get('/mesero', [MeseroController::class, 'index'])->name('mesero.index');
            Route::get('/mesero/live', [MeseroController::class, 'live'])->name('mesero.live');
            Route::get('/mesero/carta', [MeseroController::class, 'carta'])->name('mesero.carta');
            Route::post('/mesero/pedir', [MeseroController::class, 'pedir'])->name('mesero.pedir');
            Route::post('/mesero/mesas/{mesa}/cuenta', [MeseroController::class, 'cuenta'])->name('mesero.cuenta');
            Route::get('/mesero/pedidos/{pedido}/ticket', [MeseroController::class, 'ticket'])->name('mesero.ticket');
            Route::post('/mesero/pedidos/{pedido}/llevar', [MeseroController::class, 'llevar'])->name('mesero.llevar');
        });

        Route::resource('mesas', MesaController::class);
        Route::resource('reservas', ReservaController::class);
        Route::patch('pedidos/{pedido}/estado', [PedidoController::class, 'cambiarEstado'])->name('pedidos.estado');
        Route::get('pedidos/{pedido}/ticket', [PedidoController::class, 'ticket'])->name('pedidos.ticket');
        Route::resource('pedidos', PedidoController::class)->only(['index', 'show', 'destroy']);
        Route::get('/delivery', [DeliveryController::class, 'index'])->name('delivery.index');
        Route::patch('/delivery/{pedido}/estado', [DeliveryController::class, 'cambiarEstado'])->name('delivery.estado');
        Route::post('/delivery/simular-plataforma', [\App\Http\Controllers\Api\DeliveryWebhookController::class, 'simular'])->name('delivery.simular');
        Route::resource('categorias', CategoriaController::class);
        Route::resource('productos', ProductoController::class);
        Route::get('/carta-qr', [CartaQrController::class, 'index'])->name('cartaqr.index');
        Route::get('/productos/{producto}/receta', [RecetaController::class, 'edit'])->name('recetas.edit');
        Route::put('/productos/{producto}/receta', [RecetaController::class, 'update'])->name('recetas.update');
        Route::resource('clientes', ClienteController::class);
        Route::resource('insumos', InsumoController::class);
        Route::get('/kardex', [KardexController::class, 'index'])->name('kardex.index');
        Route::resource('promociones', PromocionController::class)->except(['show']);
        Route::post('/kardex', [KardexController::class, 'store'])->name('kardex.store');
        Route::get('/cocina', [CocinaController::class, 'index'])->name('cocina.index');
        Route::get('/cocina/data', [CocinaController::class, 'data'])->name('cocina.data');
        Route::post('/cocina/{pedido}/avanzar', [CocinaController::class, 'avanzar'])->name('cocina.avanzar');
        Route::post('/cocina/{pedido}/cancelar', [CocinaController::class, 'cancelar'])->name('cocina.cancelar');
        Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
        Route::post('/caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
        Route::post('/caja/movimiento', [CajaController::class, 'movimiento'])->name('caja.movimiento');
        Route::post('/caja/cerrar', [CajaController::class, 'cerrar'])->name('caja.cerrar');
        Route::get('/caja/{caja}', [CajaController::class, 'show'])->name('caja.show');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/exportar', [ReporteController::class, 'exportarRentabilidad'])->name('reportes.exportar');
        Route::get('/reportes/exportar-ventas', [ReporteController::class, 'exportarVentas'])->name('reportes.exportar_ventas');
        Route::get('/reportes/imprimir', [ReporteController::class, 'imprimir'])->name('reportes.imprimir');
        Route::middleware('role:admin')->group(function () {
            Route::resource('usuarios', UsuarioController::class);
            Route::get('/configuracion', [ConfiguracionController::class, 'edit'])->name('configuracion.edit');
            Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
        });
    });
});

Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', [SuperDashboard::class, 'index'])->name('dashboard');
    Route::patch('restaurantes/{restaurante}/toggle', [SuperRestaurante::class, 'toggle'])->name('restaurantes.toggle');
    Route::post('restaurantes/{restaurante}/extender', [SuperRestaurante::class, 'extenderPrueba'])->name('restaurantes.extender');
    Route::post('restaurantes/{restaurante}/pago', [SuperRestaurante::class, 'registrarPago'])->name('restaurantes.pago');
    Route::get('/perfil', [SuperProfile::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [SuperProfile::class, 'update'])->name('profile.update');
    Route::put('/perfil/password', [SuperProfile::class, 'password'])->name('profile.password');
    Route::resource('restaurantes', SuperRestaurante::class)->only(['index', 'show', 'update', 'destroy']);
    Route::resource('planes', SuperPlan::class)->except(['show']);
});

require __DIR__.'/auth.php';
