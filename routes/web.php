<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
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
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SupplierASLController;
use App\Http\Controllers\ConflictOfInterestController;
use App\Http\Controllers\AnnualProcurementPlanController;
use App\Http\Controllers\CapaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Kenya School Procurement System - Routes
| Minimal configuration while service architecture is being aligned
|
*/


// Redirect home to login
Route::get('/', function () {
    return redirect()->route('login');
})->name('welcome');

// ============================================================================
// GUEST ROUTES (Authentication)
// ============================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Self-registration is disabled — accounts are created by administrators only.
    // Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    // Route::post('/register', [RegisteredUserController::class, 'store']);
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
    Route::post('purchase-orders/{purchaseOrder}/issue', [PurchaseOrderController::class, 'issue'])->name('purchase-orders.issue');
    Route::post('purchase-orders/{purchaseOrder}/acknowledge', [PurchaseOrderController::class, 'acknowledge'])->name('purchase-orders.acknowledge');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'downloadPDF'])->name('purchase-orders.pdf');
    Route::get('purchase-orders/{purchaseOrder}/email', [PurchaseOrderController::class, 'emailModal'])->name('purchase-orders.email-modal');
    Route::post('purchase-orders/{purchaseOrder}/email', [PurchaseOrderController::class, 'sendEmail'])->name('purchase-orders.email');
    Route::get('purchase-orders/{purchaseOrder}/items', [PurchaseOrderController::class, 'getItems'])->name('purchase-orders.items');

    // GRN / Goods Received
    Route::resource('grn', GRNController::class)->names('grn');
    Route::get('grn/{grn}/accept', [GRNController::class, 'showAcceptForm'])->name('grn.accept.form');
    Route::post('grn/{grn}/accept', [GRNController::class, 'accept'])->name('grn.accept');
    Route::post('grn/{grn}/reject-acceptance', [GRNController::class, 'rejectAcceptance'])->name('grn.reject-acceptance');
    Route::get('grn/{grn}/inspect', [GRNController::class, 'inspectForm'])->name('grn.inspect.form');
    Route::post('grn/{grn}/inspect', [GRNController::class, 'recordInspection'])->name('grn.inspect');
    Route::post('grn/{grn}/post-inventory', [GRNController::class, 'postToInventory'])->name('grn.post-inventory');
    Route::get('grn/{grn}/discrepancies', [GRNController::class, 'discrepancies'])->name('grn.discrepancies');
    Route::post('grn/{grn}/discrepancies', [GRNController::class, 'recordDiscrepancy'])->name('grn.discrepancies.store');

    // Inventory
    Route::resource('inventory', InventoryController::class)->names('inventory');

    // Suppliers
    Route::resource('suppliers', SupplierController::class)->names('suppliers');

    // Approved Supplier List (ASL)
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('asl', [SupplierASLController::class, 'index'])->name('asl.index');
        Route::get('{supplier}/asl/review', [SupplierASLController::class, 'review'])->name('asl.review');
        Route::post('{supplier}/asl/submit', [SupplierASLController::class, 'submit'])->name('asl.submit');
        Route::post('{supplier}/asl/approve', [SupplierASLController::class, 'approve'])->name('asl.approve');
        Route::post('{supplier}/asl/suspend', [SupplierASLController::class, 'suspend'])->name('asl.suspend');
        Route::post('{supplier}/asl/remove', [SupplierASLController::class, 'remove'])->name('asl.remove');
        Route::post('{supplier}/documents/{document}/verify', [SupplierASLController::class, 'verifyDocument'])->name('documents.verify');
        // Onboarding
        Route::get('{supplier}/onboarding', [SupplierASLController::class, 'onboardingChecklist'])->name('onboarding.checklist');
        Route::get('{supplier}/onboarding/upload', [SupplierASLController::class, 'showUploadForm'])->name('onboarding.upload');
        Route::post('{supplier}/onboarding/upload', [SupplierASLController::class, 'storeDocument'])->name('onboarding.upload.store');
    });

    // Conflict of Interest Declarations
    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('{process}/coi-declaration', [ConflictOfInterestController::class, 'create'])->name('coi.create');
        Route::post('{process}/coi-declaration', [ConflictOfInterestController::class, 'store'])->name('coi.store');
    });

    // Invoices
    Route::resource('invoices', InvoiceController::class)->names('invoices');

    // Payments
    Route::resource('payments', PaymentController::class)->names('payments');
    Route::post('payments/{payment}/submit', [PaymentController::class, 'submit'])->name('payments.submit');
    Route::get('payments/{payment}/approve', [PaymentController::class, 'approveForm'])->name('payments.approve.form');
    Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::post('payments/{payment}/process', [PaymentController::class, 'process'])->name('payments.process');
    Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirmPayment'])->name('payments.confirm');
    Route::get('payments/{payment}/wht-certificate', [PaymentController::class, 'downloadWHTCertificate'])->name('payments.wht-certificate');
    Route::get('payments/wht-list', [PaymentController::class, 'whtList'])->name('payments.wht-list');
    Route::post('payments/wht-bulk-download', [PaymentController::class, 'bulkDownloadWHT'])->name('payments.wht-bulk-download');
    Route::get('payments/reconciliation', [PaymentController::class, 'reconciliation'])->name('payments.reconciliation');
    Route::post('payments/reconciliation', [PaymentController::class, 'storeReconciliation'])->name('payments.reconciliation.store');

    // Reports
    Route::resource('reports', ReportController::class)->names('reports');

    // Budget Setup
    Route::get('budgets/setup', [BudgetController::class, 'setup'])->name('budgets.setup');
    Route::get('budgets/department-setup', [BudgetController::class, 'departmentSetup'])->name('budgets.department-setup');
    Route::post('budgets/store-department-budgets', [BudgetController::class, 'storeDepartmentBudgets'])->name('budgets.store-department-budgets');

    // Budget Dashboard
    Route::get('budgets/dashboard', [BudgetController::class, 'dashboard'])->name('budgets.dashboard');

    // Fiscal Years
    Route::post('fiscal-years', [FiscalYearController::class, 'store'])->name('fiscal-years.store');
    Route::patch('fiscal-years/{fiscalYear}/set-active', [FiscalYearController::class, 'setActive'])->name('fiscal-years.set-active');

    // Budget Approvals (must be before budgets resource)
    Route::get('budgets/pending', [BudgetController::class, 'pending'])->name('budgets.pending');
    Route::get('budgets/{budget}/approve', [BudgetController::class, 'showApproval'])->name('budgets.show-approval');
    Route::post('budgets/{budget}/submit', [BudgetController::class, 'submit'])->name('budgets.submit');
    Route::post('budgets/{budget}/approve', [BudgetController::class, 'approve'])->name('budgets.approve');
    Route::post('budgets/{budget}/reject', [BudgetController::class, 'reject'])->name('budgets.reject');

    // Budgets
    Route::resource('budgets', BudgetController::class)->names('budgets');

    // Departments
    Route::resource('departments', DepartmentController::class)->names('departments');

    // Users (Admin)
    Route::resource('admin/users', UserController::class)->names([
        'index' => 'admin.users.index',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Annual Procurement Plan
    Route::resource('annual-procurement-plans', AnnualProcurementPlanController::class)
        ->names('annual-procurement-plans');
    Route::post('annual-procurement-plans/{annualProcurementPlan}/submit', [AnnualProcurementPlanController::class, 'submit'])->name('annual-procurement-plans.submit');
    Route::post('annual-procurement-plans/{annualProcurementPlan}/approve', [AnnualProcurementPlanController::class, 'approve'])->name('annual-procurement-plans.approve');
    Route::post('annual-procurement-plans/{annualProcurementPlan}/reject', [AnnualProcurementPlanController::class, 'reject'])->name('annual-procurement-plans.reject');

    // CAPA / Quality
    Route::resource('capa', CapaController::class)->names('capa');
    Route::post('capa/{capa}/submit',   [CapaController::class, 'submit'])->name('capa.submit');
    Route::post('capa/{capa}/approve',  [CapaController::class, 'approve'])->name('capa.approve');
    Route::post('capa/{capa}/reject',   [CapaController::class, 'reject'])->name('capa.reject');
    Route::post('capa/{capa}/start',    [CapaController::class, 'startImplementation'])->name('capa.start');
    Route::post('capa/{capa}/verify',   [CapaController::class, 'verify'])->name('capa.verify');
    Route::post('capa/{capa}/close',    [CapaController::class, 'close'])->name('capa.close');
    Route::post('capa/{capa}/updates',  [CapaController::class, 'storeUpdate'])->name('capa.updates.store');

    // KPI Dashboard
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard'])->name('reports.dashboard');

    // PesaPal payment initiation
    Route::post('payments/{payment}/initiate', [PaymentController::class, 'initiatePesapal'])->name('payments.initiate');
});

// Fallback 404 handler
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
