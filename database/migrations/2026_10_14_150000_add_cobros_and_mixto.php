<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE pedidos MODIFY metodo_pago ENUM('efectivo','tarjeta','yape','plin','transferencia','mixto') NULL");
        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'pago_efectivo')) $table->decimal('pago_efectivo', 10, 2)->default(0);
            if (!Schema::hasColumn('pedidos', 'pago_tarjeta')) $table->decimal('pago_tarjeta', 10, 2)->default(0);
            if (!Schema::hasColumn('pedidos', 'abonado')) $table->decimal('abonado', 10, 2)->default(0);
        });
        if (!Schema::hasTable('cobros')) {
            Schema::create('cobros', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
                $table->foreignId('mesa_id')->nullable()->constrained('mesas')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('monto', 10, 2);
                $table->string('metodo_pago');
                $table->decimal('efectivo', 10, 2)->default(0);
                $table->decimal('tarjeta', 10, 2)->default(0);
                $table->timestamps();
            });
        }
    }
};
