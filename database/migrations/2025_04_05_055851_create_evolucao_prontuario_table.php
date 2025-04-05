<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evolucao_prontuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_prontuario')->nullable()->constrained('prontuarios');
            $table->date('data_atendimento')->nullable();
            $table->text('descricao_evolucao')->nullable();
            // No timestamps in the original schema
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolucao_prontuario');
    }
};
