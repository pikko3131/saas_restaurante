<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'users',
        'pedidos',
        'pedido_items',
        'productos',
        'categorias',
        'mesas',
        'clientes',
        'insumos',
        'promociones',
        'reservas',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tabla) {
            if (Schema::hasTable($tabla) && ! Schema::hasColumn($tabla, 'deleted_at')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'deleted_at')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
