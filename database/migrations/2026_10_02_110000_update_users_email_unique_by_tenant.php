<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Elimina la restricción global única de email
            $table->dropUnique('users_email_unique');
            // Agrega restricción única por restaurante (tenant)
            $table->unique(['restaurante_id', 'email'], 'users_restaurante_id_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_restaurante_id_email_unique');
            $table->unique('email', 'users_email_unique');
        });
    }
};
