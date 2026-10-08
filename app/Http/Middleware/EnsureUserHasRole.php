<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowedRoles = [];

        foreach ($roles as $role) {
            $allowedRoles = [...$allowedRoles, ...array_map('trim', explode(',', $role))];
        }

        $allowedRoles = array_values(array_filter(array_map('strtolower', $allowedRoles)));

        if (! $user || ($allowedRoles !== [] && ! $user->hasAnyRole($allowedRoles))) {
            abort(403, 'Anda tidak memiliki akses untuk peran ini.');
        }

        return $next($request);
    }
}
