<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAppOption
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $action = $this->permissions->actionFromMethod($request->method());

        if (! $this->permissions->userCan($user, $slug, $action)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para esta acción.',
                ], 403);
            }

            abort(403, 'No tiene permisos para esta acción.');
        }

        return $next($request);
    }
}
