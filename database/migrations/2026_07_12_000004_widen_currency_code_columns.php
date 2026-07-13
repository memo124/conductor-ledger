<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('currencies')) {
            DB::statement('ALTER TABLE currencies ALTER COLUMN code TYPE VARCHAR(10)');
        }

        if (Schema::hasTable('exchange_rates')) {
            DB::statement('ALTER TABLE exchange_rates ALTER COLUMN base_currency TYPE VARCHAR(10)');
            DB::statement('ALTER TABLE exchange_rates ALTER COLUMN target_currency TYPE VARCHAR(10)');
        }

        if (Schema::hasColumn('users', 'currency_preference')) {
            DB::statement('ALTER TABLE users ALTER COLUMN currency_preference TYPE VARCHAR(10)');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('currencies')) {
            DB::statement('ALTER TABLE currencies ALTER COLUMN code TYPE VARCHAR(3)');
        }

        if (Schema::hasTable('exchange_rates')) {
            DB::statement('ALTER TABLE exchange_rates ALTER COLUMN base_currency TYPE VARCHAR(3)');
            DB::statement('ALTER TABLE exchange_rates ALTER COLUMN target_currency TYPE VARCHAR(3)');
        }

        if (Schema::hasColumn('users', 'currency_preference')) {
            DB::statement('ALTER TABLE users ALTER COLUMN currency_preference TYPE VARCHAR(3)');
        }
    }
};
