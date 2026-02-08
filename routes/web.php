<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GRNController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Kenya School Procurement System - Routes
| Minimal configuration while service architecture is being aligned
|
*/

// Test route without middleware
Route::get('/test', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Laravel is working!',
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version()
    ]);
});

// Welcome page
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// ============================================================================
// GUEST ROUTES (Authentication)
// ============================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ============================================================================
// AUTHENTICATED ROUTES (require auth middleware)
// ============================================================================
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Requisitions
    Route::resource('requisitions', RequisitionController::class)->names('requisitions');

    // Procurement RFQ Routes
    Route::prefix('procurement')->name('procurement.')->group(function () {
        // RFQ Routes
        Route::get('rfq', [ProcurementController::class, 'indexRFQ'])->name('indexRFQ');
        Route::get('rfq/create', [ProcurementController::class, 'createRFQ'])->name('rfq.create');
        Route::post('rfq', [ProcurementController::class, 'storeRFQ'])->name('rfq.store');
        Route::get('rfq/{process}', [ProcurementController::class, 'showRFQ'])->name('rfq.show');
        Route::get('rfq/{process}/edit', [ProcurementController::class, 'editRFQ'])->name('rfq.edit');
        Route::put('rfq/{process}', [ProcurementController::class, 'updateRFQ'])->name('rfq.update');
        Route::post('rfq/{process}/publish', [ProcurementController::class, 'publishRFQ'])->name('rfq.publish');
        Route::post('rfq/{process}/close', [ProcurementController::class, 'closeRFQ'])->name('rfq.close');

        // RFP Routes
        Route::get('rfp', [ProcurementController::class, 'indexRFP'])->name('indexRFP');
        Route::get('rfp/create', [ProcurementController::class, 'createRFP'])->name('rfp.create');
        Route::post('rfp', [ProcurementController::class, 'storeRFP'])->name('rfp.store');
        Route::get('rfp/{process}', [ProcurementController::class, 'showRFP'])->name('rfp.show');
        Route::get('rfp/{process}/edit', [ProcurementController::class, 'editRFP'])->name('rfp.edit');
        Route::put('rfp/{process}', [ProcurementController::class, 'updateRFP'])->name('rfp.update');
        Route::post('rfp/{process}/publish', [ProcurementController::class, 'publishRFP'])->name('rfp.publish');
        Route::post('rfp/{process}/close', [ProcurementController::class, 'closeRFP'])->name('rfp.close');

        // Tender Routes
        Route::get('tender', [ProcurementController::class, 'indexTender'])->name('indexTender');
        Route::get('tender/create', [ProcurementController::class, 'createTender'])->name('tender.create');
        Route::post('tender', [ProcurementController::class, 'storeTender'])->name('tender.store');
        Route::get('tender/{process}', [ProcurementController::class, 'showTender'])->name('tender.show');
        Route::get('tender/{process}/edit', [ProcurementController::class, 'editTender'])->name('tender.edit');
        Route::put('tender/{process}', [ProcurementController::class, 'updateTender'])->name('tender.update');
        Route::post('tender/{process}/publish', [ProcurementController::class, 'publishTender'])->name('tender.publish');
        Route::post('tender/{process}/close', [ProcurementController::class, 'closeTender'])->name('tender.close');
        Route::get('tender/{process}/evaluate', [ProcurementController::class, 'evaluateTender'])->name('tender.evaluate');
        Route::post('tender/{process}/award', [ProcurementController::class, 'awardTender'])->name('tender.award');

        // Bids Routes
        Route::get('bids', [ProcurementController::class, 'indexBids'])->name('indexBids');
        Route::get('bids/{bid}', [ProcurementController::class, 'showBid'])->name('bids.show');
        Route::get('bids/{bid}/evaluate', [ProcurementController::class, 'evaluateBidForm'])->name('bids.evaluate');
        Route::post('bids/{bid}/evaluation', [ProcurementController::class, 'recordEvaluation'])->name('bids.evaluation.store');
    });

    // Procurement main resource (for index and any remaining CRUD operations)
    Route::resource('procurement', ProcurementController::class)->only(['index'])->names('procurement');

    // Purchase Orders
    Route::resource('purchase-orders', PurchaseOrderController::class)->names('purchase-orders');

    // GRN / Goods Received
    Route::resource('grn', GRNController::class)->names('grn');

    // Inventory
    Route::resource('inventory', InventoryController::class)->names('inventory');

    // Suppliers
    Route::resource('suppliers', SupplierController::class)->names('suppliers');

    // Invoices
    Route::resource('invoices', InvoiceController::class)->names('invoices');

    // Payments
    Route::resource('payments', PaymentController::class)->names('payments');

    // Reports
    Route::resource('reports', ReportController::class)->names('reports');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Fallback 404 handler
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
