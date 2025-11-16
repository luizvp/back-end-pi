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
        Schema::table('agendamentos', function (Blueprint $table) {
            // Campo para controlar se o status foi alterado manualmente
            $table->boolean('alterado_manualmente')->default(false)->after('compareceu');

            // Campos para melhor controle
            $table->timestamp('data_status_alterado')->nullable()->after('alterado_manualmente');
            $table->string('alterado_por')->nullable()->after('data_status_alterado'); // futuro controle de usuário

            // Melhoria no campo observacoes para permitir mais texto
            $table->text('observacoes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropColumn([
                'alterado_manualmente',
                'data_status_alterado',
                'alterado_por'
            ]);

            // Reverter observacoes para string se necessário
            $table->string('observacoes')->nullable()->change();
        });
    }
};
