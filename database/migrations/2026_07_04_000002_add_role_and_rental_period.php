<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('user')->after('is_active');
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'rental_period')) {
                $table->string('rental_period', 20)->default('daily')->after('rental_fee_daily');
            }
            if (! Schema::hasColumn('vehicles', 'quota_percentage')) {
                $table->decimal('quota_percentage', 5, 2)->default(0)->after('rental_period');
            }
            if (! Schema::hasColumn('vehicles', 'quota_reserve_amount')) {
                $table->decimal('quota_reserve_amount', 10, 2)->default(0)->after('quota_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['rental_period', 'quota_percentage', 'quota_reserve_amount']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
