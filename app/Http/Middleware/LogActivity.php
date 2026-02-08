<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LogActivity Middleware
 * 
 * Records all user activity in audit_logs table for compliance and debugging.
 * Captures: route, method, parameters, IP, user-agent, execution time, status code.
 * 
 * Applied globally to all authenticated routes.
 */
class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip logging for non-write operations and static assets
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Record start time for execution duration
        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        // Calculate execution duration
        $duration = round((microtime(true) - $startTime) * 1000, 2); // milliseconds

        // Log activity asynchronously to avoid performance impact
        // Dispatch job to write audit log without blocking response
        if ($request->user()) {
            \App\Jobs\LogActivityJob::dispatch(
                user_id: $request->user()->id,
                action: $this->getActionName($request),
                model_type: $this->getModelType($request),
                model_id: $this->getModelId($request),
                route: $request->path(),
                method: $request->method(),
                ip_address: $request->ip(),
                user_agent: $request->userAgent(),
                status_code: $response->status(),
                duration_ms: $duration,
                request_data: $this->sanitizeData($request->except(['password', 'password_confirmation'])),
            );
        }

        return $response;
    }

    /**
     * Extract action name from route or HTTP method
     */
    private function getActionName(Request $request): string
    {
        $routeName = $request->route()?->getName();

        if ($routeName) {
            return $routeName;
        }

        // Fallback to method-based action
        return match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };
    }

    /**
     * Extract model type from route
     */
    private function getModelType(Request $request): ?string
    {
        $segments = explode('/', trim($request->path(), '/'));

        if (count($segments) > 0) {
            // First segment is usually the resource type
            $resource = str_singular(ucwords(str_replace('-', ' ', $segments[0])));
            return "App\\Models\\{$resource}";
        }

        return null;
    }

    /**
     * Extract model ID from route parameters
     */
    private function getModelId(Request $request): ?int
    {
        // Try to get from route parameters (first numeric parameter)
        foreach ($request->route()?->parameters() ?? [] as $value) {
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        // Try to get from request data
        if ($request->has('id')) {
            return (int) $request->input('id');
        }

        return null;
    }

    /**
     * Sanitize request data to remove sensitive information
     */
    private function sanitizeData(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'key', 'credit_card', 'cvv'];

        foreach ($data as $key => &$value) {
            if (in_array(strtolower($key), $sensitive)) {
                $value = '[REDACTED]';
            } elseif (is_array($value)) {
                $value = $this->sanitizeData($value);
            }
        }

        return $data;
    }
}
