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
        // Check if the columns exist before trying to add them
        if (!Schema::hasColumn('pagamentos', 'created_at')) {
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasColumn('pagamentos', 'updated_at')) {
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });
        }

        // Check if the old columns exist before trying to copy data and drop them
        if (Schema::hasColumn('pagamentos', 'criado_em') && Schema::hasColumn('pagamentos', 'created_at')) {
            // Copy data from old columns to new ones
            DB::statement('UPDATE pagamentos SET created_at = criado_em, updated_at = atualizado_em');

            // Drop old timestamp columns
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->dropColumn('criado_em');
                $table->dropColumn('atualizado_em');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the old columns don't exist before trying to add them
        if (!Schema::hasColumn('pagamentos', 'criado_em')) {
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->timestamp('criado_em')->nullable();
            });
        }

        if (!Schema::hasColumn('pagamentos', 'atualizado_em')) {
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->timestamp('atualizado_em')->nullable();
            });
        }

        // Check if both columns exist before copying data
        if (Schema::hasColumn('pagamentos', 'created_at') && Schema::hasColumn('pagamentos', 'criado_em')) {
            // Copy data back from standard columns to old ones
            DB::statement('UPDATE pagamentos SET criado_em = created_at, atualizado_em = updated_at');

            // Drop standard timestamp columns
            Schema::table('pagamentos', function (Blueprint $table) {
                $table->dropColumn('created_at');
                $table->dropColumn('updated_at');
            });
        }
    }
};
