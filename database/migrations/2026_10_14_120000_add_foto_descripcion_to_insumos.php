<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('insumos', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('nombre');
            $table->text('descripcion')->nullable()->after('imagen');
        });
    }
    public function down(): void {
        Schema::table('insumos', function (Blueprint $table) {
            $table->dropColumn(['imagen', 'descripcion']);
        });
    }
};
