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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->date('data_nascimento')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->enum('sexo', ['M', 'F', 'O'])->nullable();
            $table->string('cidade', 255)->nullable();
            $table->string('bairro', 255)->nullable();
            $table->string('profissao', 255)->nullable();
            $table->string('endereco_residencial', 255)->nullable();
            $table->string('endereco_comercial', 255)->nullable();
            $table->string('naturalidade', 255)->nullable();
            $table->string('estado_civil', 255)->nullable();
            $table->string('cpf', 11)->nullable()->unique('cpf_UNIQUE');
            $table->string('email', 255)->nullable();
            // No timestamps in the original schema
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
