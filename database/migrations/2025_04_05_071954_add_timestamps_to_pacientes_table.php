<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->timestamps(); // Adiciona created_at e updated_at
        });

        // Preencher created_at com a data atual para registros existentes
        DB::statement('UPDATE pacientes SET created_at = NOW(), updated_at = NOW()');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
