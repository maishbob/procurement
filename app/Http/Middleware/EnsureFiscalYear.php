<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureFiscalYear Middleware
 * 
 * Determines and sets the current fiscal year context in the session.
 * All financial reports, budgets, and queries use this fiscal year by default.
 * 
 * Fiscal year is configurable in admin settings.
 * Defaults based on system configuration (e.g., Jan-Dec or Jun-May).
 */
class EnsureFiscalYear
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get fiscal year from session or compute current fiscal year
        $fiscalYear = session('current_fiscal_year');

        if (!$fiscalYear) {
            $fiscalYear = $this->computeFiscalYear();
            session(['current_fiscal_year' => $fiscalYear]);
        }

        // Store in request for easy access in controllers
        $request->merge([
            'fiscal_year' => $fiscalYear,
        ]);

        // Share with views
        \View::share('currentFiscalYear', $fiscalYear);

        return $next($request);
    }

    /**
     * Compute current fiscal year based on system settings
     */
    private function computeFiscalYear(): string
    {
        $settings = \App\Models\SystemSetting::where('key', 'fiscal_year_start_month')->first();
        $startMonth = (int) ($settings?->value ?? 1); // Default: January (1)

        $now = now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // If we're before the fiscal year start month, we're in the previous fiscal year
        if ($currentMonth < $startMonth) {
            $startYear = $currentYear - 1;
        } else {
            $startYear = $currentYear;
        }

        $endYear = $startYear + 1;

        return "{$startYear}/{$endYear}";
    }
}
