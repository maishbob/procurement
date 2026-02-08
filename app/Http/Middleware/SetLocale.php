<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetLocale Middleware
 * 
 * Determines and sets the current locale (language) for the application.
 * Respects user preferences, browser headers, and system defaults.
 * 
 * Supported locales: en (English), sw (Swahili)
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        // Set application locale
        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * Determine the appropriate locale for the request
     */
    private function determineLocale(Request $request): string
    {
        // Priority 1: Check if locale is specified in request (e.g., ?locale=sw)
        if ($request->has('locale')) {
            $locale = $request->query('locale');
            if ($this->isValidLocale($locale)) {
                // Save user preference
                if ($request->user()) {
                    $request->user()->update(['preferred_locale' => $locale]);
                    session(['locale' => $locale]);
                }
                return $locale;
            }
        }

        // Priority 2: Check user preference from database
        if ($request->user()) {
            $userLocale = $request->user()->preferred_locale;
            if ($userLocale && $this->isValidLocale($userLocale)) {
                return $userLocale;
            }
        }

        // Priority 3: Check session
        $sessionLocale = session('locale');
        if ($sessionLocale && $this->isValidLocale($sessionLocale)) {
            return $sessionLocale;
        }

        // Priority 4: Check browser Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            if ($locale && $this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // Priority 5: Use system default from config
        return config('app.locale', 'en');
    }

    /**
     * Check if locale is valid/supported
     */
    private function isValidLocale(string $locale): bool
    {
        $supported = config('app.supported_locales', ['en', 'sw']);
        return in_array($locale, $supported);
    }

    /**
     * Parse Accept-Language header to extract locale
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        // Parse: en-US,en;q=0.9,sw;q=0.8
        $languages = explode(',', $header);

        foreach ($languages as $language) {
            // Split by quality factor
            $parts = explode(';', $language);
            $locale = trim($parts[0]);

            // Extract language code (e.g., 'en' from 'en-US')
            $lang = explode('-', $locale)[0];

            if ($this->isValidLocale($lang)) {
                return $lang;
            }
        }

        return null;
    }
}
