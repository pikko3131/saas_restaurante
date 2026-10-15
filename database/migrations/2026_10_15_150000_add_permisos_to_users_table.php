<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'permisos')) {
                $table->json('permisos')->nullable()->after('role');
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'permisos')) {
                $table->dropColumn('permisos');
            }
        });
    }
};
