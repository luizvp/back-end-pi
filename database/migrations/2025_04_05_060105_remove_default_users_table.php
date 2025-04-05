<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration is now a no-op since we're dropping all tables
     * in the drop_existing_tables migration.
     */
    public function up(): void
    {
        // No-op - tables are dropped in drop_existing_tables migration
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
