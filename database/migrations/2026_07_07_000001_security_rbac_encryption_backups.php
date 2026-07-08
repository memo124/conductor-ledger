<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('app_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('app_options')->nullOnDelete();
            $table->string('slug', 100)->unique();
            $table->string('label', 150);
            $table->string('route_name', 150)->nullable();
            $table->string('icon', 80)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_menu')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_option_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->primary(['role_id', 'app_option_id']);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->text('encrypted_dek')->nullable()->after('remember_token');
            $table->text('admin_wrapped_dek')->nullable()->after('encrypted_dek');
            $table->string('dek_salt', 64)->nullable()->after('admin_wrapped_dek');
            $table->json('kdf_params')->nullable()->after('dek_salt');
        });

        Schema::create('security_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('backup_download_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('checksum', 64);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->text('encrypted_payload')->nullable();
            $table->unsignedSmallInteger('encryption_version')->default(0);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->text('encrypted_payload')->nullable();
            $table->unsignedSmallInteger('encryption_version')->default(0);
        });

        Schema::table('monthly_summaries', function (Blueprint $table) {
            $table->text('encrypted_payload')->nullable()->after('ganancia_neta');
            $table->unsignedSmallInteger('encryption_version')->default(0)->after('encrypted_payload');
        });

        (new \Database\Seeders\RoleSeeder)->run();
        (new \Database\Seeders\AppOptionSeeder)->run();
        (new \Database\Seeders\RolePermissionSeeder)->run();
        $this->syncExistingUserRoles();
    }

    private function syncExistingUserRoles(): void
    {
        $conductorRole = \App\Models\Role::query()->where('slug', 'conductor')->value('id');
        $adminRole = \App\Models\Role::query()->where('slug', 'administrador')->value('id');

        if (! $conductorRole || ! $adminRole) {
            return;
        }

        \App\Models\User::query()->each(function (\App\Models\User $user) use ($conductorRole, $adminRole) {
            $roleId = $user->role === 'admin' ? $adminRole : $conductorRole;
            $user->roles()->syncWithoutDetaching([$roleId]);
        });
    }

    public function down(): void
    {
        Schema::table('monthly_summaries', function (Blueprint $table) {
            $table->dropColumn(['encrypted_payload', 'encryption_version']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['encrypted_payload', 'encryption_version']);
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['encrypted_payload', 'encryption_version']);
        });

        Schema::dropIfExists('backup_download_tokens');
        Schema::dropIfExists('security_audit_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'encrypted_dek', 'admin_wrapped_dek', 'dek_salt', 'kdf_params']);
        });

        Schema::dropIfExists('user_role');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('app_options');
        Schema::dropIfExists('roles');
    }
};
