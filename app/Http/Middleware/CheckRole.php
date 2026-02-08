<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 * 
 * Verifies user has required roles to access specific routes.
 * Can be applied with route parameter: middleware('role:admin,finance_manager').
 * 
 * Provides granular role-based access control beyond basic auth.
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // If no roles specified, allow access (role check not required)
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user has any of the specified roles
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // User doesn't have required role
        // Log unauthorized attempt
        \Log::warning('Unauthorized role access attempt', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'required_roles' => $roles,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'route' => $request->path(),
            'ip' => $request->ip(),
        ]);

        // Return 403 Forbidden response
        return response()->view('errors.403', [
            'message' => 'You do not have permission to access this resource.',
        ], 403);
    }
}
