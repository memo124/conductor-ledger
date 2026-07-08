<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    private const CACHE_TTL = 300;

    public function userCan(User $user, string $slug, string $action = 'view'): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $this->permissionsForUser($user);
        $option = $permissions->get($slug);

        if (! $option) {
            return false;
        }

        return match ($action) {
            'view' => (bool) $option['can_view'],
            'create' => (bool) $option['can_create'],
            'update' => (bool) $option['can_update'],
            'delete' => (bool) $option['can_delete'],
            default => false,
        };
    }

    public function permissionsForUser(User $user): \Illuminate\Support\Collection
    {
        return Cache::remember(
            $this->cacheKey($user->id),
            self::CACHE_TTL,
            function () use ($user) {
                $user->loadMissing('roles.permissions.appOption');

                return $user->roles
                    ->flatMap(fn ($role) => $role->permissions)
                    ->groupBy(fn ($permission) => $permission->appOption->slug)
                    ->map(function ($group) {
                        return [
                            'can_view' => $group->contains(fn ($p) => $p->can_view),
                            'can_create' => $group->contains(fn ($p) => $p->can_create),
                            'can_update' => $group->contains(fn ($p) => $p->can_update),
                            'can_delete' => $group->contains(fn ($p) => $p->can_delete),
                        ];
                    });
            }
        );
    }

    public function actionFromMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };
    }

    public function clearCacheForUser(int $userId): void
    {
        Cache::forget($this->cacheKey($userId));
    }

    public function clearCacheForRoleUsers(int $roleId): void
    {
        User::query()
            ->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId))
            ->pluck('id')
            ->each(fn ($id) => $this->clearCacheForUser($id));
    }

    private function cacheKey(int $userId): string
    {
        return "user_permissions:{$userId}";
    }
}
