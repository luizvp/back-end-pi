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
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes');
            $table->string('descricao', 255);
            $table->enum('tipo', ['consulta', 'produto', 'outro'])->default('consulta');
            $table->decimal('valor_consulta', 10, 2);
            $table->decimal('valor_pago', 10, 2)->default(0.00);
            $table->string('forma_pagamento', 50)->nullable();
            $table->enum('status_pagamento', ['pendente', 'pago', 'cancelado'])->default('pendente');
            $table->dateTime('data_pagamento')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
