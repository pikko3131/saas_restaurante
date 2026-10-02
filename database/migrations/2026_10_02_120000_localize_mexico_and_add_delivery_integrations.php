<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Columnas de integración delivery (Rappi / Uber Eats) en restaurantes
        Schema::table('restaurantes', function (Blueprint $table) {
            $table->boolean('rappi_activo')->default(false)->after('activo');
            $table->string('rappi_store_id')->nullable()->after('rappi_activo');
            $table->string('rappi_api_key')->nullable()->after('rappi_store_id');
            $table->string('rappi_webhook_secret')->nullable()->after('rappi_api_key');

            $table->boolean('ubereats_activo')->default(false)->after('rappi_webhook_secret');
            $table->string('ubereats_store_id')->nullable()->after('ubereats_activo');
            $table->string('ubereats_client_id')->nullable()->after('ubereats_store_id');
            $table->string('ubereats_client_secret')->nullable()->after('ubereats_client_id');
        });

        // 2. Columnas de plataforma delivery en pedidos
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('delivery_plataforma', 30)->default('directo')->after('delivery_estado');
            $table->string('delivery_externo_id')->nullable()->index()->after('delivery_plataforma');
            $table->json('delivery_datos_json')->nullable()->after('delivery_externo_id');
            $table->string('delivery_tracking_url', 500)->nullable()->after('delivery_datos_json');
        });

        // 3. Modificar columnas enum a VARCHAR para soportar métodos de pago mexicanos (OXXO, Mercado Pago, Clip, SPEI, etc.)
        try {
            DB::statement("ALTER TABLE pedidos MODIFY metodo_pago VARCHAR(40) NULL");
            DB::statement("ALTER TABLE caja_movimientos MODIFY metodo_pago VARCHAR(40) DEFAULT 'efectivo'");
        } catch (\Throwable $e) {
            // Si la base de datos es SQLite u otro motor sin MODIFY
        }

        // 4. Actualizar registros existentes peruanos a México
        try {
            DB::table('restaurantes')->where('moneda', 'S/')->update(['moneda' => '$']);
            DB::table('restaurantes')->where('igv', 18)->update(['igv' => 16]);
            DB::table('pedidos')->whereIn('metodo_pago', ['yape', 'plin'])->update(['metodo_pago' => 'transferencia']);
            DB::table('caja_movimientos')->whereIn('metodo_pago', ['yape', 'plin'])->update(['metodo_pago' => 'transferencia']);
        } catch (\Throwable $e) {
            // Silencioso si no aplica
        }
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_plataforma',
                'delivery_externo_id',
                'delivery_datos_json',
                'delivery_tracking_url',
            ]);
        });

        Schema::table('restaurantes', function (Blueprint $table) {
            $table->dropColumn([
                'rappi_activo',
                'rappi_store_id',
                'rappi_api_key',
                'rappi_webhook_secret',
                'ubereats_activo',
                'ubereats_store_id',
                'ubereats_client_id',
                'ubereats_client_secret',
            ]);
        });
    }
};
