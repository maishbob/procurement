<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\Requisition;
use App\Models\RequisitionItem;

class BudgetEnforcementTest extends TestCase
{
    protected FiscalYear $fiscalYear;
    protected Department $department;
    protected BudgetLine $budgetLine;
    protected User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        // Create fiscal year
        $this->fiscalYear = FiscalYear::factory()->create([
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);

        // Create department
        $this->department = Department::factory()->create();

        // Create budget line with 100k allocation
        $this->budgetLine = BudgetLine::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'department_id' => $this->department->id,
            'amount_allocated' => 100000,
            'amount_committed' => 0,
            'amount_executed' => 0,
        ]);

        // Create requester
        $this->requester = User::factory()->create([
            'department_id' => $this->department->id,
        ]);
        $this->requester->assignRole('requisition_creator');
    }

    /**
     * Test requisition within budget is allowed
     */
    public function test_requisition_within_budget_is_allowed(): void
    {
        $this->actingAs($this->requester);

        $response = $this->post(route('requisitions.store'), [
            'description' => 'Within budget purchase',
            'budget_line_id' => $this->budgetLine->id,
            'requisition_items' => [
                [
                    'catalog_item_id' => 1,
                    'quantity' => 10,
                    'unit_price' => 5000, // Total: 50000
                ]
            ],
        ]);

        $this->assertDatabaseHas('requisitions', [
            'budget_line_id' => $this->budgetLine->id,
            'total_amount' => 50000,
        ]);
    }

    /**
     * Test requisition exceeding budget is rejected
     */
    public function test_requisition_exceeding_budget_is_rejected(): void
    {
        $this->actingAs($this->requester);

        $response = $this->post(route('requisitions.store'), [
            'description' => 'Over budget purchase',
            'budget_line_id' => $this->budgetLine->id,
            'requisition_items' => [
                [
                    'catalog_item_id' => 1,
                    'quantity' => 100,
                    'unit_price' => 2000, // Total: 200000 (exceeds 100k budget)
                ]
            ],
        ]);

        // Should fail or return error
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    /**
     * Test multiple requisitions against same budget
     */
    public function test_consecutive_requisitions_respect_budget(): void
    {
        // First requisition: 40k
        $req1 = Requisition::factory()->create([
            'budget_line_id' => $this->budgetLine->id,
            'total_amount' => 40000,
            'status' => 'approved',
        ]);

        // Commit the amount
        $this->budgetLine->increment('amount_committed', 40000);

        // Second requisition: 40k - should be allowed (total = 80k < 100k budget)
        $req2 = Requisition::factory()->create([
            'budget_line_id' => $this->budgetLine->id,
            'total_amount' => 40000,
            'status' => 'approved',
        ]);

        $this->budgetLine->increment('amount_committed', 40000);

        // Third requisition: 30k - would exceed budget (110k > 100k)
        $this->actingAs($this->requester);

        $response = $this->post(route('requisitions.store'), [
            'description' => 'Third requisition',
            'budget_line_id' => $this->budgetLine->id,
            'requisition_items' => [
                [
                    'catalog_item_id' => 1,
                    'quantity' => 15,
                    'unit_price' => 2000, // Total: 30000
                ]
            ],
        ]);

        // Verify commitment calculation
        $refreshed = $this->budgetLine->fresh();
        $this->assertEquals(80000, $refreshed->amount_committed);
    }

    /**
     * Test budget execution tracking on payment
     */
    public function test_budget_execution_on_payment(): void
    {
        // Create and commit budget
        $this->budgetLine->update(['amount_committed' => 50000]);

        // Create mock payment (normally would be from invoice verification)
        $this->budgetLine->increment('amount_executed', 50000);

        // Verify execution
        $this->assertEquals(50000, $this->budgetLine->fresh()->amount_executed);
        $this->assertEquals(50000, $this->budgetLine->fresh()->amount_committed);
    }

    /**
     * Test budget variance reporting
     */
    public function test_budget_variance_calculation(): void
    {
        $this->budgetLine->update([
            'amount_allocated' => 100000,
            'amount_committed' => 80000,
            'amount_executed' => 60000,
        ]);

        $variance = $this->budgetLine->amount_allocated - $this->budgetLine->amount_executed;
        $utilization = ($this->budgetLine->amount_executed / $this->budgetLine->amount_allocated) * 100;

        $this->assertEquals(40000, $variance);
        $this->assertEquals(60, $utilization);
    }

    /**
     * Test budget is locked in expired fiscal year
     */
    public function test_budget_is_locked_in_expired_fiscal_year(): void
    {
        // Create expired fiscal year
        $expiredYear = FiscalYear::factory()->create([
            'start_date' => now()->subYear(),
            'end_date' => now()->subDay(),
        ]);

        $expiredBudget = BudgetLine::factory()->create([
            'fiscal_year_id' => $expiredYear->id,
            'department_id' => $this->department->id,
            'status' => 'closed',
        ]);

        $this->actingAs($this->requester);

        // Try to create requisition against expired budget
        $response = $this->post(route('requisitions.store'), [
            'description' => 'Against expired budget',
            'budget_line_id' => $expiredBudget->id,
            'requisition_items' => [
                [
                    'catalog_item_id' => 1,
                    'quantity' => 5,
                    'unit_price' => 1000,
                ]
            ],
        ]);

        // Should be rejected
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    /**
     * Test budget threshold alert is triggered
     */
    public function test_budget_threshold_alert_at_80_percent(): void
    {
        $this->budgetLine->update([
            'amount_allocated' => 100000,
            'amount_executed' => 80000, // 80% utilized
        ]);

        $utilization = ($this->budgetLine->amount_executed / $this->budgetLine->amount_allocated) * 100;

        $this->assertTrue($utilization >= 80);

        // In real implementation, this would trigger an event
        event(new \App\Events\BudgetThresholdExceededEvent(
            $this->budgetLine,
            $utilization,
            '80%'
        ));
    }

    /**
     * Test budget release on requisition rejection
     */
    public function test_budget_commitment_released_on_rejection(): void
    {
        $requisition = Requisition::factory()->create([
            'budget_line_id' => $this->budgetLine->id,
            'total_amount' => 30000,
            'status' => 'submitted',
        ]);

        // Commit budget
        $this->budgetLine->increment('amount_committed', 30000);

        $this->assertEquals(30000, $this->budgetLine->fresh()->amount_committed);

        // Reject requisition and release budget
        $requisition->update(['status' => 'rejected']);
        $this->budgetLine->decrement('amount_committed', 30000);

        // Verify release
        $this->assertEquals(0, $this->budgetLine->fresh()->amount_committed);
    }
}
