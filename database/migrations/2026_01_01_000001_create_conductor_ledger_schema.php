<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE vehicle_ownership_types (
                id SERIAL PRIMARY KEY,
                name VARCHAR(50) UNIQUE NOT NULL
            )
        ");

        DB::statement("
            CREATE TABLE expense_categories (
                id SERIAL PRIMARY KEY,
                name VARCHAR(50) UNIQUE NOT NULL
            )
        ");

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('ownership_type_id');
            $table->foreign('ownership_type_id')->references('id')->on('vehicle_ownership_types');
            $table->string('plate_number', 15);
            $table->decimal('rental_fee_daily', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('login_ip', 45);
            $table->string('last_known_ip', 45);
            $table->text('user_agent');
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->timestamps();
        });

        Schema::create('yearly_counters', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('anio');
            $table->integer('current_trip_number')->default(0);
            $table->integer('current_expense_number')->default(0);
            $table->timestamps();
            $table->primary(['user_id', 'anio']);
        });

        DB::statement("
            CREATE TABLE trips (
                uuid UUID NOT NULL,
                user_id BIGINT NOT NULL,
                vehicle_id BIGINT NOT NULL,
                anio INT NOT NULL,
                trip_number INT NOT NULL,
                fecha DATE NOT NULL,
                dia_semana VARCHAR(15) NOT NULL,
                indrive DECIMAL(10, 2) DEFAULT 0.00,
                otros_viajes DECIMAL(10, 2) DEFAULT 0.00,
                propina DECIMAL(10, 2) DEFAULT 0.00,
                alquiler DECIMAL(10, 2) DEFAULT 0.00,
                created_at TIMESTAMP WITHOUT TIME ZONE NULL,
                updated_at TIMESTAMP WITHOUT TIME ZONE NULL,
                PRIMARY KEY (user_id, anio, trip_number, uuid, fecha)
            ) PARTITION BY RANGE (fecha)
        ");

        DB::statement("
            CREATE TABLE expenses (
                uuid UUID NOT NULL,
                user_id BIGINT NOT NULL,
                vehicle_id BIGINT NULL,
                category_id INT REFERENCES expense_categories(id),
                anio INT NOT NULL,
                expense_number INT NOT NULL,
                fecha DATE NOT NULL,
                monto DECIMAL(10, 2) NOT NULL,
                descripcion TEXT NULL,
                created_at TIMESTAMP WITHOUT TIME ZONE NULL,
                updated_at TIMESTAMP WITHOUT TIME ZONE NULL,
                PRIMARY KEY (user_id, anio, expense_number, uuid, fecha)
            ) PARTITION BY RANGE (fecha)
        ");

        $currentYear = (int) date('Y');
        $nextYear = $currentYear + 1;

        DB::statement("CREATE TABLE trips_{$currentYear} PARTITION OF trips FOR VALUES FROM ('{$currentYear}-01-01') TO ('{$nextYear}-01-01')");
        DB::statement("CREATE TABLE expenses_{$currentYear} PARTITION OF expenses FOR VALUES FROM ('{$currentYear}-01-01') TO ('{$nextYear}-01-01')");

        Schema::create('monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('anio');
            $table->integer('mes');
            $table->decimal('total_indrive', 12, 2)->default(0);
            $table->decimal('total_otros_viajes', 12, 2)->default(0);
            $table->decimal('total_propinas', 12, 2)->default(0);
            $table->decimal('total_alquiler', 12, 2)->default(0);
            $table->decimal('total_gastos', 12, 2)->default(0);
            $table->decimal('ganancia_neta', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'anio', 'mes']);
        });

        DB::statement('CREATE INDEX idx_trips_search ON trips (user_id, fecha, vehicle_id)');
        DB::statement('CREATE INDEX idx_expenses_search ON expenses (user_id, fecha, vehicle_id)');

        (new \Database\Seeders\VehicleOwnershipTypeSeeder)->run();
        (new \Database\Seeders\ExpenseCategorySeeder)->run();
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS monthly_summaries CASCADE');
        DB::statement('DROP TABLE IF EXISTS expenses CASCADE');
        DB::statement('DROP TABLE IF EXISTS trips CASCADE');
        Schema::dropIfExists('yearly_counters');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('vehicles');
        DB::statement('DROP TABLE IF EXISTS expense_categories CASCADE');
        DB::statement('DROP TABLE IF EXISTS vehicle_ownership_types CASCADE');
    }
};
