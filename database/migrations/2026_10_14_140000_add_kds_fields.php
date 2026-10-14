<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'prioridad')) $table->boolean('prioridad')->default(false);
        });
        Schema::table('pedido_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_items', 'listo')) $table->boolean('listo')->default(false);
            if (!Schema::hasColumn('pedido_items', 'listo_at')) $table->timestamp('listo_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('pedidos', fn (Blueprint $t) => $t->dropColumn('prioridad'));
        Schema::table('pedido_items', fn (Blueprint $t) => $t->dropColumn(['listo', 'listo_at']));
    }
};
