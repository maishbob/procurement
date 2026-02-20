<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BudgetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Department Budgets
Route::middleware('auth:sanctum')->get('/departments/{department}/budgets', [BudgetController::class, 'getDepartmentBudgets']);

// PesaPal IPN callback — no auth, no CSRF (API middleware group excludes both)
Route::post('/pesapal/callback', [\App\Http\Controllers\PesapalWebhookController::class, 'callback'])
    ->name('pesapal.callback');
