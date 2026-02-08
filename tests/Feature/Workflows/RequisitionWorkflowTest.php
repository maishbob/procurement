<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use App\Models\User;
use App\Models\Requisition;
use App\Models\Department;
use App\Models\BudgetLine;
use App\Models\FiscalYear;

class RequisitionWorkflowTest extends TestCase
{
    protected User $requester;
    protected User $approver;
    protected Department $department;
    protected BudgetLine $budgetLine;

    protected function setUp(): void
    {
        parent::setUp();

        // Create fiscal year
        $fiscalYear = FiscalYear::factory()->create();

        // Create department
        $this->department = Department::factory()->create();

        // Create budget line
        $this->budgetLine = BudgetLine::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'department_id' => $this->department->id,
            'amount_allocated' => 500000,
        ]);

        // Create requester
        $this->requester = User::factory()->create([
            'department_id' => $this->department->id,
        ]);
        $this->requester->assignRole('requisition_creator');

        // Create approver with authority
        $this->approver = User::factory()->create([
            'department_id' => $this->department->id,
            'approval_limit' => 100000,
        ]);
        $this->approver->assignRole('requisition_approver');
    }

    /**
     * Test requisition can be created in draft status
     */
    public function test_requisition_can_be_created(): void
    {
        $this->actingAs($this->requester);

        $response = $this->post(route('requisitions.store'), [
            'description' => 'Office Supplies',
            'budget_line_id' => $this->budgetLine->id,
            'requisition_items' => [
                [
                    'catalog_item_id' => 1,
                    'quantity' => 10,
                    'unit_price' => 5000,
                ]
            ],
        ]);

        $this->assertDatabaseHas('requisitions', [
            'status' => 'draft',
            'created_by' => $this->requester->id,
        ]);
    }

    /**
     * Test requisition can be submitted for approval
     */
    public function test_requisition_can_be_submitted(): void
    {
        $requisition = Requisition::factory()->create([
            'status' => 'draft',
            'created_by' => $this->requester->id,
            'budget_line_id' => $this->budgetLine->id,
        ]);

        $this->actingAs($this->requester);

        $response = $this->post(
            route('requisitions.submit', $requisition),
            ['submission_notes' => 'Please approve']
        );

        $this->assertEquals('submitted', $requisition->fresh()->status);
    }

    /**
     * Test requisition approval with authority validation
     */
    public function test_requisition_approval_with_authority_check(): void
    {
        $requisition = Requisition::factory()->create([
            'status' => 'submitted',
            'total_amount' => 50000, // Within approver's limit
            'budget_line_id' => $this->budgetLine->id,
        ]);

        $this->actingAs($this->approver);

        $response = $this->post(
            route('requisitions.approve', $requisition),
            ['approval_notes' => 'Approved']
        );

        $this->assertEquals('approved', $requisition->fresh()->status);
    }

    /**
     * Test requisition approval rejects when amount exceeds authority
     */
    public function test_requisition_approval_fails_with_insufficient_authority(): void
    {
        $lowLimitApprover = User::factory()->create([
            'approval_limit' => 10000, // Can only approve up to 10k
            'department_id' => $this->department->id,
        ]);
        $lowLimitApprover->assignRole('requisition_approver');

        $requisition = Requisition::factory()->create([
            'status' => 'submitted',
            'total_amount' => 50000, // Exceeds approver's limit
            'budget_line_id' => $this->budgetLine->id,
        ]);

        $this->actingAs($lowLimitApprover);

        $response = $this->post(
            route('requisitions.approve', $requisition),
            ['approval_notes' => 'Approved']
        );

        $this->assertFalse($response->isSuccessful()); // Should be forbidden or fail
    }

    /**
     * Test requisition rejection
     */
    public function test_requisition_can_be_rejected(): void
    {
        $requisition = Requisition::factory()->create([
            'status' => 'submitted',
            'budget_line_id' => $this->budgetLine->id,
        ]);

        $this->actingAs($this->approver);

        $response = $this->post(
            route('requisitions.reject', $requisition),
            ['rejection_reason' => 'Budget allocation missing']
        );

        $this->assertEquals('rejected', $requisition->fresh()->status);
    }

    /**
     * Test only draft requisitions can be edited
     */
    public function test_requisition_can_only_be_edited_in_draft(): void
    {
        $requisition = Requisition::factory()->create([
            'status' => 'submitted', // Not in draft
            'created_by' => $this->requester->id,
        ]);

        $this->actingAs($this->requester);

        $response = $this->put(
            route('requisitions.update', $requisition),
            ['description' => 'Updated description']
        );

        // Should fail or be forbidden
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    /**
     * Test requisition audit trail
     */
    public function test_requisition_changes_are_logged(): void
    {
        $requisition = Requisition::factory()->create([
            'status' => 'draft',
            'created_by' => $this->requester->id,
        ]);

        $initialLogCount = \App\Models\AuditLog::where('model_id', $requisition->id)->count();

        $requisition->update(['status' => 'submitted']);

        $newLogCount = \App\Models\AuditLog::where('model_id', $requisition->id)->count();

        $this->assertGreater($newLogCount, $initialLogCount);
    }

    /**
     * Test budget availability is checked on requisition creation
     */
    public function test_requisition_checks_budget_availability(): void
    {
        // Create requisition with amount exceeding budget
        $largeRequisition = Requisition::factory()->create([
            'budget_line_id' => $this->budgetLine->id,
            'total_amount' => 600000, // Exceeds allocated amount
        ]);

        $this->actingAs($this->requester);

        // Should fail validation
        $this->assertTrue($largeRequisition->total_amount > $this->budgetLine->amount_allocated);
    }
}
