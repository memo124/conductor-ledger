<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trips')) {
            return;
        }

        if (! Schema::hasColumn('trips', 'client_id')) {
            DB::statement('ALTER TABLE trips ADD COLUMN client_id BIGINT NULL REFERENCES clients(id) ON DELETE SET NULL');
        }

        if (! Schema::hasColumn('trips', 'client_dependent_id')) {
            DB::statement('ALTER TABLE trips ADD COLUMN client_dependent_id BIGINT NULL REFERENCES client_dependents(id) ON DELETE SET NULL');
        }

        if (! Schema::hasColumn('trips', 'client_display_name')) {
            DB::statement('ALTER TABLE trips ADD COLUMN client_display_name VARCHAR(120) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('trips')) {
            return;
        }

        if (Schema::hasColumn('trips', 'client_display_name')) {
            DB::statement('ALTER TABLE trips DROP COLUMN client_display_name');
        }

        if (Schema::hasColumn('trips', 'client_dependent_id')) {
            DB::statement('ALTER TABLE trips DROP COLUMN client_dependent_id');
        }

        if (Schema::hasColumn('trips', 'client_id')) {
            DB::statement('ALTER TABLE trips DROP COLUMN client_id');
        }
    }
};
