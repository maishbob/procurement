<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckDepartment Middleware
 * 
 * Ensures users can only access resources belonging to their assigned department
 * unless they have a "view_all" permission (admin/finance roles).
 * 
 * Applied to requisitions, budget views, and department-specific resources.
 */
class CheckDepartment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If no authenticated user, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Admins and users with view_all permission bypass department checks
        if ($user->hasRole(['admin', 'super_admin']) || $user->hasPermission('view_all_departments')) {
            return $next($request);
        }

        // Store user's department in request for use in queries/policies
        $request->merge([
            'user_department_id' => $user->department_id,
        ]);

        return $next($request);
    }
}
