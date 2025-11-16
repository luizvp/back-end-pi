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
        // =====================================================
        // CRIAR APENAS AS NOVAS TABELAS (pular alterações existentes)
        // =====================================================

        // Tabela para diagnósticos padronizados (CID-10 Fisioterapia)
        if (!Schema::hasTable('diagnosticos_padronizados')) {
            Schema::create('diagnosticos_padronizados', function (Blueprint $table) {
                $table->id();
                $table->string('codigo_cid', 10)->unique();
                $table->text('descricao');
                $table->string('categoria', 100)->nullable();
                $table->string('subcategoria', 100)->nullable();
                $table->timestamps();

                $table->index('codigo_cid');
                $table->index('categoria');
            });
        }

        // Tabela para controlar tratamentos completos
        if (!Schema::hasTable('tratamentos')) {
            Schema::create('tratamentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
                $table->foreignId('prontuario_id')->constrained('prontuarios')->onDelete('cascade');
                $table->date('data_inicio');
                $table->date('data_fim_prevista')->nullable();
                $table->date('data_alta_real')->nullable();
                $table->enum('status', ['ativo', 'concluido', 'interrompido', 'pausado'])->default('ativo');
                $table->text('motivo_alta')->nullable();
                $table->integer('total_sessoes_previstas')->nullable();
                $table->integer('total_sessoes_realizadas')->default(0);
                $table->text('objetivo_tratamento')->nullable();
                $table->text('observacoes_finais')->nullable();
                $table->timestamps();

                $table->index(['paciente_id', 'status']);
                $table->index('data_inicio');
                $table->index('status');
            });
        }

        // Tabela para sessões individuais de fisioterapia
        if (!Schema::hasTable('sessoes_fisioterapia')) {
            Schema::create('sessoes_fisioterapia', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->onDelete('set null');
                $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
                $table->foreignId('prontuario_id')->constrained('prontuarios')->onDelete('cascade');
                $table->foreignId('tratamento_id')->nullable()->constrained('tratamentos')->onDelete('set null');
                $table->date('data_sessao');
                $table->time('hora_inicio')->nullable();
                $table->time('hora_fim')->nullable();
                $table->integer('duracao_minutos')->nullable();
                $table->enum('status', ['agendada', 'realizada', 'cancelada', 'faltou'])->default('agendada');
                $table->string('tipo_sessao', 100)->nullable()->comment('avaliacao, tratamento, reavaliacao');
                $table->text('observacoes_sessao')->nullable();
                $table->text('evolucao_paciente')->nullable();
                $table->json('equipamentos_utilizados')->nullable();
                $table->text('exercicios_realizados')->nullable();
                $table->timestamps();

                $table->index(['paciente_id', 'data_sessao']);
                $table->index('status');
                $table->index('data_sessao');
            });
        }

        // Tabela para equipamentos da clínica
        if (!Schema::hasTable('equipamentos')) {
            Schema::create('equipamentos', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 255);
                $table->string('tipo', 100)->comment('aparelho, sensor, maca, etc');
                $table->string('marca', 100)->nullable();
                $table->string('modelo', 100)->nullable();
                $table->string('numero_serie', 100)->nullable();
                $table->enum('status', ['ativo', 'manutencao', 'inativo'])->default('ativo');
                $table->string('localizacao', 255)->nullable();
                $table->integer('tempo_uso_total')->default(0)->comment('em minutos');
                $table->date('ultima_manutencao')->nullable();
                $table->date('proxima_manutencao')->nullable();
                $table->text('observacoes')->nullable();
                $table->timestamps();

                $table->index(['tipo', 'status']);
            });
        }

        // Tabela para dados de IoT e sensores
        if (!Schema::hasTable('dados_iot')) {
            Schema::create('dados_iot', function (Blueprint $table) {
                $table->id();
                $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->onDelete('set null');
                $table->foreignId('sessao_id')->nullable()->constrained('sessoes_fisioterapia')->onDelete('set null');
                $table->foreignId('equipamento_id')->nullable()->constrained('equipamentos')->onDelete('set null');
                $table->string('tipo_sensor', 50)->comment('frequencia_cardiaca, pressao, movimento, etc');
                $table->dateTime('timestamp');
                $table->decimal('valor', 10, 4);
                $table->string('unidade_medida', 20)->nullable()->comment('bpm, mmHg, graus, etc');
                $table->string('contexto', 100)->nullable()->comment('repouso, durante_exercicio, pos_exercicio');
                $table->text('observacoes')->nullable();
                $table->timestamp('created_at')->default(now());

                $table->index(['paciente_id', 'timestamp']);
                $table->index(['tipo_sensor', 'timestamp']);
                $table->index('sessao_id');
            });
        }

        // Tabela para previsões de ML
        if (!Schema::hasTable('previsoes_ml')) {
            Schema::create('previsoes_ml', function (Blueprint $table) {
                $table->id();
                $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
                $table->enum('tipo_previsao', ['probabilidade_falta', 'demanda_periodo', 'sucesso_tratamento']);
                $table->decimal('valor_previsao', 5, 4)->comment('probabilidade entre 0 e 1');
                $table->decimal('confianca', 5, 4)->nullable();
                $table->date('data_previsao');
                $table->timestamp('data_calculo')->default(now());
                $table->string('modelo_utilizado', 100)->nullable();
                $table->json('parametros_entrada')->nullable();
                $table->string('acao_recomendada', 255)->nullable();
                $table->boolean('executada')->default(false);

                $table->index(['tipo_previsao', 'data_previsao']);
                $table->index(['paciente_id', 'tipo_previsao']);
            });
        }

        // Adicionar foreign key no prontuarios se não existir
        if (!Schema::hasColumn('prontuarios', 'diagnostico_cid_id')) {
            Schema::table('prontuarios', function (Blueprint $table) {
                $table->foreignId('diagnostico_cid_id')->nullable()->constrained('diagnosticos_padronizados')->onDelete('set null');
            });
        }

        // Adicionar foreign key no pagamentos se não existir
        if (!Schema::hasColumn('pagamentos', 'sessao_id')) {
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->foreignId('sessao_id')->nullable()->constrained('sessoes_fisioterapia')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover foreign keys primeiro
        if (Schema::hasColumn('pagamentos', 'sessao_id')) {
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->dropForeign(['sessao_id']);
                $table->dropColumn('sessao_id');
            });
        }

        if (Schema::hasColumn('prontuarios', 'diagnostico_cid_id')) {
            Schema::table('prontuarios', function (Blueprint $table) {
                $table->dropForeign(['diagnostico_cid_id']);
                $table->dropColumn('diagnostico_cid_id');
            });
        }

        // Remover novas tabelas
        Schema::dropIfExists('previsoes_ml');
        Schema::dropIfExists('dados_iot');
        Schema::dropIfExists('equipamentos');
        Schema::dropIfExists('sessoes_fisioterapia');
        Schema::dropIfExists('tratamentos');
        Schema::dropIfExists('diagnosticos_padronizados');
    }
};
