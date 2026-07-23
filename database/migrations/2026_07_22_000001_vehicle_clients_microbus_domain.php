<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        if (Schema::hasColumn('vehicles', 'plate_number') && ! Schema::hasColumn('vehicles', 'alias')) {
            DB::statement('ALTER TABLE vehicles RENAME COLUMN plate_number TO alias');
            DB::statement('ALTER TABLE vehicles ALTER COLUMN alias TYPE VARCHAR(40)');
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'vehicle_kind')) {
                $table->string('vehicle_kind', 20)->default('other')->after('alias');
            }
            if (! Schema::hasColumn('vehicles', 'brand')) {
                $table->string('brand', 60)->nullable()->after('vehicle_kind');
            }
            if (! Schema::hasColumn('vehicles', 'model')) {
                $table->string('model', 60)->nullable()->after('brand');
            }
            if (! Schema::hasColumn('vehicles', 'model_year')) {
                $table->unsignedSmallInteger('model_year')->nullable()->after('model');
            }
            if (! Schema::hasColumn('vehicles', 'color')) {
                $table->string('color', 40)->nullable()->after('model_year');
            }
            if (! Schema::hasColumn('vehicles', 'notes')) {
                $table->text('notes')->nullable()->after('color');
            }
        });

        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('phone', 30)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('address', 255)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('client_dependents')) {
            Schema::create('client_dependents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('relationship_label', 50)->nullable();
                $table->string('phone', 30)->nullable();
                $table->date('birth_date')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('microbus_routes')) {
            Schema::create('microbus_routes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('microbus_passengers')) {
            Schema::create('microbus_passengers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('microbus_route_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('client_dependent_id')->nullable()->constrained()->nullOnDelete();
                $table->string('display_name', 120)->nullable();
                $table->decimal('monthly_fee', 10, 2)->default(0);
                $table->text('pickup_notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('microbus_passenger_payments')) {
            Schema::create('microbus_passenger_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('microbus_passenger_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->decimal('amount_due', 10, 2)->default(0);
                $table->boolean('is_paid')->default(false);
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['microbus_passenger_id', 'period_year', 'period_month'], 'microbus_passenger_payments_unique_period');
            });
        }

        if (Schema::hasTable('trip_types')) {
            DB::table('trip_types')
                ->whereIn('code', ['ESCOLAR', 'INTERURBANO', 'INTERNACIONAL'])
                ->update(['is_active' => false]);

            DB::table('trip_types')->updateOrInsert(
                ['code' => 'MICROBUS_RUTA'],
                [
                    'name' => 'Microbús / ruta',
                    'allowed_modes' => 'daily,monthly',
                    'is_active' => true,
                ]
            );
        }

        $this->seedMenuAndPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('microbus_passenger_payments');
        Schema::dropIfExists('microbus_passengers');
        Schema::dropIfExists('microbus_routes');
        Schema::dropIfExists('client_dependents');
        Schema::dropIfExists('clients');

        if (Schema::hasColumn('vehicles', 'alias') && ! Schema::hasColumn('vehicles', 'plate_number')) {
            DB::statement('ALTER TABLE vehicles RENAME COLUMN alias TO plate_number');
            DB::statement('ALTER TABLE vehicles ALTER COLUMN plate_number TYPE VARCHAR(15)');
        }

        Schema::table('vehicles', function (Blueprint $table) {
            foreach (['vehicle_kind', 'brand', 'model', 'model_year', 'color', 'notes'] as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasTable('trip_types')) {
            DB::table('trip_types')
                ->whereIn('code', ['ESCOLAR', 'INTERURBANO', 'INTERNACIONAL'])
                ->update(['is_active' => true]);
        }

        if (Schema::hasTable('app_options')) {
            $slugs = ['clientes', 'microbus-rutas'];
            $optionIds = DB::table('app_options')->whereIn('slug', $slugs)->pluck('id');

            if ($optionIds->isNotEmpty() && Schema::hasTable('role_permissions')) {
                DB::table('role_permissions')->whereIn('app_option_id', $optionIds)->delete();
            }

            DB::table('app_options')->whereIn('slug', $slugs)->delete();
        }
    }

    private function seedMenuAndPermissions(): void
    {
        if (! Schema::hasTable('app_options')) {
            return;
        }

        $operacionesId = DB::table('app_options')->where('slug', 'operaciones')->value('id');

        if (! $operacionesId) {
            return;
        }

        $menuItems = [
            ['slug' => 'clientes', 'label' => 'Clientes', 'route_name' => 'clientes.index', 'icon' => 'fa-solid fa-address-book', 'sort_order' => 7],
            ['slug' => 'microbus-rutas', 'label' => 'Rutas microbús', 'route_name' => 'microbus-rutas.index', 'icon' => 'fa-solid fa-bus', 'sort_order' => 8],
        ];

        foreach ($menuItems as $item) {
            $exists = DB::table('app_options')->where('slug', $item['slug'])->exists();

            if (! $exists) {
                DB::table('app_options')->insert(array_merge($item, [
                    'parent_id' => $operacionesId,
                    'is_menu' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if (! Schema::hasTable('role_permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('slug', ['conductor', 'administrador'])->pluck('id', 'slug');

        foreach (['clientes', 'microbus-rutas'] as $slug) {
            $optionId = DB::table('app_options')->where('slug', $slug)->value('id');

            if (! $optionId) {
                continue;
            }

            foreach ($roleIds as $roleSlug => $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'app_option_id' => $optionId],
                    [
                        'can_view' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => $roleSlug === 'administrador',
                    ]
                );
            }
        }
    }
};
