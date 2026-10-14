<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'propina')) $table->decimal('propina', 10, 2)->default(0);
            if (!Schema::hasColumn('pedidos', 'recibido')) $table->decimal('recibido', 10, 2)->nullable();
            if (!Schema::hasColumn('pedidos', 'cambio')) $table->decimal('cambio', 10, 2)->nullable();
            if (!Schema::hasColumn('pedidos', 'descuento_motivo')) $table->string('descuento_motivo')->nullable();
        });
    }
    public function down(): void {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['propina', 'recibido', 'cambio', 'descuento_motivo']);
        });
    }
};
