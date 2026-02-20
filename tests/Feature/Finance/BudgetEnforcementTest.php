<?php

namespace Tests\Feature\Finance;

use App\Core\Audit\AuditService;
use App\Core\CurrencyEngine\CurrencyEngine;
use App\Core\Rules\GovernanceRules;
use App\Core\TaxEngine\TaxEngine;
use App\Core\Workflow\WorkflowEngine;
use App\Models\BudgetLine;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Services\PaymentService;
use App\Modules\Requisitions\Models\Requisition;
use App\Observers\RequisitionObserver;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Budget Enforcement Tests
 *
 * Coverage:
 *   A) GovernanceRules::validateBudgetAvailability() — real DB queries
 *   B) RequisitionObserver — budget commit / release logic
 *   C) PaymentService::updateBudgetSpent() — traversal chain
 *
 * Schema note: the authoritative budget_lines table is created by
 * 2014_10_12_000002_create_departments_and_governance_tables.php with
 * columns: budget_code (VARCHAR unique), fiscal_year (VARCHAR),
 * cost_center_id (FK NOT NULL), department_id (FK), category, description,
 * allocated_amount, committed_amount, spent_amount, available_amount.
 */
class BudgetEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Count Mockery expectations as PHPUnit assertions so the test runner
        // does not flag Mockery-only tests as "risky / no assertions".
        $this->addToAssertionCount(
            Mockery::getContainer()->mockery_getExpectationCount()
        );
        Mockery::close();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Insert a department row and return its id. */
    private function insertDepartment(string $code = 'TEST'): int
    {
        return DB::table('departments')->insertGetId([
            'name'       => "Dept {$code}",
            'code'       => $code,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Insert a cost_center row and return its id.
     *
     * The authoritative schema (2014_10_12_000002) gives cost_centers a
     * `department_id` FK (NOT NULL), so a dept must be supplied.
     *
     * Idempotent: returns the existing row's id when the code already exists
     * within the same test (allows multiple budget_line inserts to share one
     * cost_center without triggering a unique-constraint error).
     */
    private function insertCostCenter(int $deptId, string $code = 'CC-TEST'): int
    {
        $existing = DB::table('cost_centers')->where('code', $code)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return DB::table('cost_centers')->insertGetId([
            'name'          => "Cost Centre {$code}",
            'code'          => $code,
            'department_id' => $deptId,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Insert a budget_line row using the authoritative schema
     * (2014_10_12_000002) and return its id.
     *
     * `available_amount` is auto-derived from allocated − committed − spent
     * unless explicitly overridden.
     *
     * Accepts column overrides so individual tests can vary the amounts.
     */
    private function insertBudgetLine(int $deptId, array $overrides = []): int
    {
        $costCenterId = $this->insertCostCenter($deptId);

        $allocated = (float) ($overrides['allocated_amount'] ?? 100000);
        $committed = (float) ($overrides['committed_amount'] ?? 0);
        $spent     = (float) ($overrides['spent_amount']     ?? 0);
        $available = $overrides['available_amount'] ?? ($allocated - $committed - $spent);

        return DB::table('budget_lines')->insertGetId(array_merge([
            'budget_code'      => 'TEST-2026',
            'fiscal_year'      => '2026',
            'department_id'    => $deptId,
            'cost_center_id'   => $costCenterId,
            'category'         => 'operations',
            'description'      => 'Test budget line',
            'allocated_amount' => $allocated,
            'committed_amount' => $committed,
            'spent_amount'     => $spent,
            'available_amount' => $available,
            'currency'         => 'KES',
            'is_active'        => true,
            'created_at'       => now(),
            'updated_at'       => now(),
        ], $overrides));
    }

    /** Build an unsaved Requisition stub with preset original + pending status. */
    private function makeRequisitionStub(
        string $originalStatus,
        string $newStatus,
        ?int $budgetLineId,
        float $estimatedTotal = 50000.0
    ): Requisition {
        $req = new Requisition();
        $req->forceFill([
            'id'              => 1,
            'status'          => $originalStatus,
            'budget_line_id'  => $budgetLineId,
            'estimated_total' => number_format($estimatedTotal, 2, '.', ''),
        ]);
        $req->syncOriginal();      // lock original values
        $req->status = $newStatus; // simulate the incoming transition
        return $req;
    }

    /** Build a mocked AuditService that silently accepts any call. */
    private function mockAudit(): AuditService
    {
        return Mockery::mock(AuditService::class)->shouldIgnoreMissing();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Group A – GovernanceRules::validateBudgetAvailability()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * When allocated − committed − spent >= requested amount the result must
     * report available=true AND sufficient=true.
     */
    public function test_validate_budget_returns_sufficient_when_funds_available(): void
    {
        $deptId = $this->insertDepartment('FIN');
        $this->insertBudgetLine($deptId, [
            'budget_code'      => 'FIN-OPS-2026',
            'allocated_amount' => 100000,
            'committed_amount' => 20000,
            'spent_amount'     => 10000, // available = 70 000
        ]);

        $rules  = new GovernanceRules($this->mockAudit());
        $result = $rules->validateBudgetAvailability('FIN-OPS-2026', 50000.0, '2026');

        $this->assertTrue($result['available'], 'Budget line must be found');
        $this->assertTrue($result['sufficient'], 'KES 50k should fit in 70k available');
        $this->assertEqualsWithDelta(70000.0, $result['available_balance'], 0.01);
        $this->assertEquals(50000.0, $result['requested_amount']);
    }

    /**
     * When the requested amount exceeds the available balance the result must
     * report available=true but sufficient=false.
     */
    public function test_validate_budget_returns_insufficient_when_balance_below_requested(): void
    {
        $deptId = $this->insertDepartment('FIN');
        $this->insertBudgetLine($deptId, [
            'budget_code'      => 'FIN-OPS-2026',
            'allocated_amount' => 100000,
            'committed_amount' => 20000,
            'spent_amount'     => 10000, // available = 70 000
        ]);

        $rules  = new GovernanceRules($this->mockAudit());
        $result = $rules->validateBudgetAvailability('FIN-OPS-2026', 80000.0, '2026');

        $this->assertTrue($result['available'], 'Budget line must still be found');
        $this->assertFalse($result['sufficient'], 'KES 80k must NOT fit in 70k available');
        $this->assertEqualsWithDelta(70000.0, $result['available_balance'], 0.01);
        $this->assertEquals(80000.0, $result['requested_amount']);
    }

    /**
     * When no matching budget_code + fiscal_year row exists the result must
     * report available=false, sufficient=false, and include an error key.
     */
    public function test_validate_budget_returns_not_available_when_no_matching_line(): void
    {
        $rules  = new GovernanceRules($this->mockAudit());
        $result = $rules->validateBudgetAvailability('DOES-NOT-EXIST', 50000.0, '2026');

        $this->assertFalse($result['available']);
        $this->assertFalse($result['sufficient']);
        $this->assertEquals(0, $result['available_balance']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('DOES-NOT-EXIST', $result['error']);
    }

    /**
     * An inactive budget line must NOT be returned by validateBudgetAvailability().
     */
    public function test_validate_budget_ignores_inactive_lines(): void
    {
        $deptId = $this->insertDepartment('FIN');
        $this->insertBudgetLine($deptId, [
            'budget_code'      => 'FIN-INACTIVE-2026',
            'allocated_amount' => 500000,
            'is_active'        => false,
        ]);

        $rules  = new GovernanceRules($this->mockAudit());
        $result = $rules->validateBudgetAvailability('FIN-INACTIVE-2026', 1000.0, '2026');

        $this->assertFalse($result['available'], 'Inactive budget lines must be invisible');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Group B – RequisitionObserver
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * When a requisition transitions → budget_approved the observer must call
     * BudgetService::commitBudget() exactly once with the correct arguments.
     */
    public function test_observer_commits_budget_on_budget_approved_transition(): void
    {
        $deptId = $this->insertDepartment('PROC');
        $blId   = $this->insertBudgetLine($deptId, ['allocated_amount' => 100000]);

        $budgetService = Mockery::mock(BudgetService::class);
        $budgetService->shouldReceive('commitBudget')
            ->once()
            ->with(
                Mockery::on(fn ($bl) => $bl instanceof BudgetLine && $bl->id === $blId),
                50000.0,
                'Requisition',
                Mockery::any()
            );

        $requisition = $this->makeRequisitionStub('budget_review', 'budget_approved', $blId, 50000.0);

        (new RequisitionObserver($budgetService))->updating($requisition);
    }

    /**
     * When a requisition is rejected from a post-budget state (e.g. sourcing)
     * the observer must call BudgetService::releaseCommitment() exactly once.
     */
    public function test_observer_releases_commitment_on_rejection_from_post_budget_state(): void
    {
        $deptId = $this->insertDepartment('PROC');
        $blId   = $this->insertBudgetLine($deptId, [
            'allocated_amount' => 100000,
            'committed_amount' => 30000,
        ]);

        $budgetService = Mockery::mock(BudgetService::class);
        // releaseAmount = min(estimated_total=30000, committed_amount=30000) = 30000
        $budgetService->shouldReceive('releaseCommitment')
            ->once()
            ->with(
                Mockery::on(fn ($bl) => $bl instanceof BudgetLine && $bl->id === $blId),
                30000.0,
                Mockery::type('string')
            );

        $requisition = $this->makeRequisitionStub('sourcing', 'rejected', $blId, 30000.0);

        (new RequisitionObserver($budgetService))->updating($requisition);
    }

    /**
     * Cancellation from a post-budget state must also release the commitment.
     */
    public function test_observer_releases_commitment_on_cancellation_from_post_budget_state(): void
    {
        $deptId = $this->insertDepartment('PROC');
        $blId   = $this->insertBudgetLine($deptId, [
            'allocated_amount' => 100000,
            'committed_amount' => 20000,
        ]);

        $budgetService = Mockery::mock(BudgetService::class);
        $budgetService->shouldReceive('releaseCommitment')->once();

        $requisition = $this->makeRequisitionStub('procurement_queue', 'cancelled', $blId, 20000.0);

        (new RequisitionObserver($budgetService))->updating($requisition);
    }

    /**
     * When budget_line_id is null the observer must skip gracefully — neither
     * commitBudget nor releaseCommitment should be called.
     */
    public function test_observer_skips_gracefully_when_no_budget_line_id(): void
    {
        $budgetService = Mockery::mock(BudgetService::class);
        $budgetService->shouldNotReceive('commitBudget');
        $budgetService->shouldNotReceive('releaseCommitment');

        $requisition = $this->makeRequisitionStub('budget_review', 'budget_approved', null, 50000.0);

        (new RequisitionObserver($budgetService))->updating($requisition);
    }

    /**
     * When old and new status are identical the observer must be a no-op.
     */
    public function test_observer_is_noop_when_status_unchanged(): void
    {
        $budgetService = Mockery::mock(BudgetService::class);
        $budgetService->shouldNotReceive('commitBudget');
        $budgetService->shouldNotReceive('releaseCommitment');

        $requisition = $this->makeRequisitionStub('budget_review', 'budget_review', 1, 50000.0);

        (new RequisitionObserver($budgetService))->updating($requisition);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Group C – PaymentService::updateBudgetSpent()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * updateBudgetSpent() must call BudgetService::recordExpenditure() once
     * per invoice that can be traced back to a budget line, using the
     * pivot's amount_allocated as the expenditure amount.
     */
    public function test_update_budget_spent_calls_record_expenditure_per_linked_invoice(): void
    {
        // Create real budget lines so BudgetLine::find() succeeds
        $deptId = $this->insertDepartment('FIN');
        $blId1  = $this->insertBudgetLine($deptId, ['budget_code' => 'BL-2026-A']);
        $blId2  = $this->insertBudgetLine($deptId, ['budget_code' => 'BL-2026-B']);

        // Build a lightweight fake-object chain for each invoice:
        //   invoice.pivot.amount_allocated
        //   invoice.purchaseOrder.requisition_id
        //   invoice.purchaseOrder.requisition.budget_line_id
        $makeInvoiceStub = function (int $budgetLineId, float $allocated) {
            $pivot       = (object)['amount_allocated' => (string) $allocated];
            $requisition = (object)['budget_line_id' => $budgetLineId];
            $po          = (object)['requisition_id' => 99, 'requisition' => $requisition];
            return (object)['pivot' => $pivot, 'purchaseOrder' => $po];
        };

        $invoice1 = $makeInvoiceStub($blId1, 25000.0);
        $invoice2 = $makeInvoiceStub($blId2, 15000.0);

        // Mock the Payment — load() is a no-op, invoices returns our stubs
        $paymentMock = Mockery::mock(Payment::class);
        $paymentMock->shouldReceive('load')->once()->andReturnSelf();
        $paymentMock->shouldReceive('getAttribute')
            ->with('invoices')
            ->andReturn(collect([$invoice1, $invoice2]));
        $paymentMock->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(42);

        // Key assertion: recordExpenditure called once per invoice
        $budgetService = Mockery::mock(BudgetService::class);
        $budgetService->shouldReceive('recordExpenditure')
            ->once()
            ->with(
                Mockery::on(fn ($bl) => $bl instanceof BudgetLine && $bl->id === $blId1),
                25000.0,
                'Payment',
                42
            );
        $budgetService->shouldReceive('recordExpenditure')
            ->once()
            ->with(
                Mockery::on(fn ($bl) => $bl instanceof BudgetLine && $bl->id === $blId2),
                15000.0,
                'Payment',
                42
            );

        $paymentService = new PaymentService(
            Mockery::mock(AuditService::class)->shouldIgnoreMissing(),
            Mockery::mock(WorkflowEngine::class)->shouldIgnoreMissing(),
            Mockery::mock(GovernanceRules::class)->shouldIgnoreMissing(),
            Mockery::mock(TaxEngine::class)->shouldIgnoreMissing(),
            Mockery::mock(CurrencyEngine::class)->shouldIgnoreMissing(),
            $budgetService
        );

        // updateBudgetSpent() is protected — access via reflection
        $method = new \ReflectionMethod(PaymentService::class, 'updateBudgetSpent');
        $method->setAccessible(true);
        $method->invoke($paymentService, $paymentMock);
    }

    /**
     * updateBudgetSpent() must skip invoices that cannot be traced back to
     * a budget line (null budget_line_id) — no exception, no call.
     */
    public function test_update_budget_spent_skips_invoices_without_budget_line(): void
    {
        $invoice = (object)[
            'pivot'         => (object)['amount_allocated' => '20000.00'],
            'purchaseOrder' => (object)[
                'requisition_id' => 5,
                'requisition'    => (object)['budget_line_id' => null],
            ],
        ];

        $paymentMock = Mockery::mock(Payment::class);
        $paymentMock->shouldReceive('load')->once()->andReturnSelf();
        $paymentMock->shouldReceive('getAttribute')
            ->with('invoices')
            ->andReturn(collect([$invoice]));
        $paymentMock->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $budgetService = Mockery::mock(BudgetService::class);
        $budgetService->shouldNotReceive('recordExpenditure');

        $paymentService = new PaymentService(
            Mockery::mock(AuditService::class)->shouldIgnoreMissing(),
            Mockery::mock(WorkflowEngine::class)->shouldIgnoreMissing(),
            Mockery::mock(GovernanceRules::class)->shouldIgnoreMissing(),
            Mockery::mock(TaxEngine::class)->shouldIgnoreMissing(),
            Mockery::mock(CurrencyEngine::class)->shouldIgnoreMissing(),
            $budgetService
        );

        $method = new \ReflectionMethod(PaymentService::class, 'updateBudgetSpent');
        $method->setAccessible(true);
        $method->invoke($paymentService, $paymentMock);
    }
}
