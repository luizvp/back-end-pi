<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Types\Type;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            // Rename criado_em to created_at
            $table->renameColumn('criado_em', 'created_at');

            // Rename atualizado_em to updated_at
            $table->renameColumn('atualizado_em', 'updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            // Rename back to original column names
            $table->renameColumn('created_at', 'criado_em');
            $table->renameColumn('updated_at', 'atualizado_em');
        });
    }
};
