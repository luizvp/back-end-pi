<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration drops all existing tables in the correct order
     * to respect foreign key constraints.
     */
    public function up(): void
    {
        // Disable foreign key checks to avoid constraint issues
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Drop tables in reverse order of dependencies
        Schema::dropIfExists('pagamentos');
        Schema::dropIfExists('evolucao_prontuario');
        Schema::dropIfExists('agendamentos');
        Schema::dropIfExists('prontuarios');
        Schema::dropIfExists('pacientes');
        Schema::dropIfExists('usuarios');

        // Also drop any Laravel system tables that might exist
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('personal_access_tokens');
        // Do NOT drop the migrations table as Laravel needs it
        // Schema::dropIfExists('migrations');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('failed_jobs');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     *
     * Since this migration is meant to clean the database,
     * there's nothing to reverse.
     */
    public function down(): void
    {
        // No need to do anything in down() as we're just dropping tables
    }
};
