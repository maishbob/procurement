<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class UpgradeController extends Controller
{
    /**
     * Run system upgrades (migrations and cache clearing)
     */
    public function upgrade(Request $request)
    {
        // Require a secure token to prevent unauthorized access
        $token = $request->query('token');
        if ($token !== config('app.upgrade_token', 'secure-upgrade-123!')) {
            abort(403, 'Unauthorized upgrade attempt.');
        }

        try {
            $output = [];

            // 1. Clear Caches
            Artisan::call('optimize:clear');
            $output[] = Artisan::output();

            // 2. Run Migrations
            Artisan::call('migrate', ['--force' => true]);
            $output[] = Artisan::output();

            // 3. Re-cache for production
            Artisan::call('config:cache');
            $output[] = Artisan::output();
            
            Artisan::call('route:cache');
            $output[] = Artisan::output();
            
            Artisan::call('view:cache');
            $output[] = Artisan::output();

            Log::info('System upgrade completed successfully via web route.', ['output' => $output]);

            return response()->json([
                'success' => true,
                'message' => 'System upgrade completed successfully.',
                'details' => $output
            ]);

        } catch (\Exception $e) {
            Log::error('System upgrade failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'System upgrade failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
