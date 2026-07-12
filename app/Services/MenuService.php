<?php

namespace App\Services;

use App\Models\AppOption;
use App\Models\User;
use Illuminate\Support\Collection;

class MenuService
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function menuForUser(User $user): Collection
    {
        $options = AppOption::query()
            ->where('is_active', true)
            ->where('is_menu', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('parent_id');

        return $this->buildTree($options, null, $user);
    }

    private function buildTree(Collection $grouped, ?int $parentId, User $user): Collection
    {
        return ($grouped->get($parentId) ?? collect())
            ->filter(function (AppOption $option) use ($user) {
                if ($option->route_name) {
                    return $this->permissions->userCan($user, $option->slug, 'view');
                }

                return true;
            })
            ->map(function (AppOption $option) use ($grouped, $user) {
                $children = $this->buildTree($grouped, $option->id, $user);

                if (! $option->route_name && $children->isEmpty()) {
                    return null;
                }

                return (object) [
                    'slug' => $option->slug,
                    'label' => $option->label,
                    'route_name' => $option->route_name,
                    'icon' => $option->icon,
                    'is_divider' => ! $option->route_name && $children->isNotEmpty(),
                    'children' => $children,
                ];
            })
            ->filter()
            ->values();
    }
}
