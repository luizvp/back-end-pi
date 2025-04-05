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
        Schema::create('prontuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->nullable()->constrained('pacientes');
            $table->text('historia_clinica')->nullable();
            $table->text('queixa_principal')->nullable();
            $table->text('habitos_vida')->nullable();
            $table->text('hma')->nullable();
            $table->text('hmp')->nullable();
            $table->text('antecedentes_pessoais')->nullable();
            $table->text('antecedentes_familiares')->nullable();
            $table->text('tratamentos_realizados')->nullable();
            $table->boolean('deambulando')->nullable();
            $table->boolean('internado')->nullable();
            $table->boolean('deambulando_apoio')->nullable();
            $table->boolean('orientado')->nullable();
            $table->boolean('cadeira_rodas')->nullable();
            $table->text('exames_complementares')->nullable();
            $table->text('usa_medicamentos')->nullable();
            $table->text('realizou_cirurgia')->nullable();
            $table->text('inspecao_palpacao')->nullable();
            $table->text('semiotica')->nullable();
            $table->text('testes_especificos')->nullable();
            $table->text('avaliacao_dor')->nullable();
            $table->text('objetivos_tratamento')->nullable();
            $table->text('recursos_terapeuticos')->nullable();
            $table->text('plano_tratamento')->nullable();
            $table->text('diagnostico_clinico')->nullable();
            $table->text('diagnostico_fisioterapeutico')->nullable();
            $table->timestamp('data_criacao')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prontuarios');
    }
};
