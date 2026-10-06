<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('delivery_direccion')->nullable()->after('notas');
            $table->string('delivery_telefono', 30)->nullable()->after('delivery_direccion');
            $table->string('delivery_referencia')->nullable()->after('delivery_telefono');
            $table->decimal('delivery_costo_envio', 10, 2)->default(0)->after('delivery_referencia');
            $table->string('delivery_repartidor')->nullable()->after('delivery_costo_envio');
            $table->string('delivery_estado', 30)->default('pendiente')->nullable()->after('delivery_repartidor');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_direccion',
                'delivery_telefono',
                'delivery_referencia',
                'delivery_costo_envio',
                'delivery_repartidor',
                'delivery_estado',
            ]);
        });
    }
};
