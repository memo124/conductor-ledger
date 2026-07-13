<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_options')) {
            return;
        }

        $operacionesId = DB::table('app_options')->where('slug', 'operaciones')->value('id');

        if (! $operacionesId) {
            return;
        }

        $exists = DB::table('app_options')->where('slug', 'conversor')->exists();

        if (! $exists) {
            DB::table('app_options')->insert([
                'parent_id' => $operacionesId,
                'slug' => 'conversor',
                'label' => 'Conversor de monedas',
                'route_name' => 'conversor.index',
                'icon' => 'fa-solid fa-right-left',
                'sort_order' => 6,
                'is_menu' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $conversorId = DB::table('app_options')->where('slug', 'conversor')->value('id');

        if (! $conversorId || ! Schema::hasTable('role_permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('slug', ['conductor', 'administrador'])->pluck('id', 'slug');

        foreach ($roleIds as $slug => $roleId) {
            $canDelete = $slug === 'administrador';

            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'app_option_id' => $conversorId],
                [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => $canDelete,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_options')) {
            return;
        }

        $conversorId = DB::table('app_options')->where('slug', 'conversor')->value('id');

        if ($conversorId && Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('app_option_id', $conversorId)->delete();
        }

        DB::table('app_options')->where('slug', 'conversor')->delete();
    }
};
