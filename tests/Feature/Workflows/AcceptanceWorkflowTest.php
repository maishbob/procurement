<?php

namespace Tests\Feature\Workflows;

use App\Core\Audit\AuditService;
use App\Core\CurrencyEngine\CurrencyEngine;
use App\Core\Rules\GovernanceRules;
use App\Core\TaxEngine\TaxEngine;
use App\Core\Workflow\WorkflowEngine;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\GRN\Models\GoodsReceivedNote;
use App\Modules\Quality\Services\CapaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * GRN Acceptance Workflow Tests
 *
 * Coverage:
 *   A) Model helper methods — canBeAccepted(), isAccepted(), isPendingAcceptance()
 *   B) WorkflowEngine transitions — approved → accepted, approved → acceptance_rejected
 *   C) InvoiceService::createFromGRN() — blocked when pending, allowed when accepted
 */
class AcceptanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->addToAssertionCount(
            Mockery::getContainer()->mockery_getExpectationCount()
        );
        Mockery::close();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Build an unsaved GoodsReceivedNote model with controlled attributes. */
    private function makeGrn(string $status, string $acceptanceStatus = 'pending'): GoodsReceivedNote
    {
        $grn = new GoodsReceivedNote();
        $grn->status            = $status;
        $grn->acceptance_status = $acceptanceStatus;
        return $grn;
    }

    /** Build a WorkflowEngine with a mocked AuditService (no DB writes). */
    private function makeWorkflowEngine(): WorkflowEngine
    {
        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('logStateTransition')->andReturn(null);
        return new WorkflowEngine($audit);
    }

    /** Build an InvoiceService with all dependencies mocked. */
    private function makeInvoiceService(): InvoiceService
    {
        return new InvoiceService(
            Mockery::mock(AuditService::class)->shouldIgnoreMissing(),
            Mockery::mock(WorkflowEngine::class)->shouldIgnoreMissing(),
            Mockery::mock(GovernanceRules::class)->shouldIgnoreMissing(),
            Mockery::mock(TaxEngine::class)->shouldIgnoreMissing(),
            Mockery::mock(CurrencyEngine::class)->shouldIgnoreMissing(),
            Mockery::mock(CapaService::class)->shouldIgnoreMissing(),
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // A) Model helper methods
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function grn_in_approved_state_with_pending_acceptance_can_be_accepted(): void
    {
        $grn = $this->makeGrn('approved', 'pending');

        $this->assertTrue($grn->canBeAccepted());
        $this->assertTrue($grn->isPendingAcceptance());
        $this->assertFalse($grn->isAccepted());
    }

    /** @test */
    public function grn_with_accepted_status_is_considered_accepted(): void
    {
        $grn = $this->makeGrn('approved', 'accepted');

        $this->assertTrue($grn->isAccepted());
        $this->assertFalse($grn->canBeAccepted());
        $this->assertFalse($grn->isPendingAcceptance());
    }

    /** @test */
    public function grn_with_partially_accepted_status_is_also_accepted(): void
    {
        $grn = $this->makeGrn('approved', 'partially_accepted');

        $this->assertTrue($grn->isAccepted());
        $this->assertFalse($grn->canBeAccepted());
    }

    /** @test */
    public function grn_not_in_approved_state_cannot_be_accepted(): void
    {
        foreach (['draft', 'submitted', 'inspection_pending', 'inspection_passed', 'rejected'] as $status) {
            $grn = $this->makeGrn($status, 'pending');
            $this->assertFalse($grn->canBeAccepted(), "GRN with status '{$status}' should not be acceptable");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // B) WorkflowEngine transitions
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function workflow_engine_allows_approved_to_accepted_transition(): void
    {
        $engine = $this->makeWorkflowEngine();

        $this->assertTrue($engine->canTransition('grn', 'approved', 'accepted'));
    }

    /** @test */
    public function workflow_engine_allows_approved_to_acceptance_rejected_transition(): void
    {
        $engine = $this->makeWorkflowEngine();

        $this->assertTrue($engine->canTransition('grn', 'approved', 'acceptance_rejected'));
    }

    /** @test */
    public function workflow_engine_allows_accepted_to_completed_transition(): void
    {
        $engine = $this->makeWorkflowEngine();

        $this->assertTrue($engine->canTransition('grn', 'accepted', 'completed'));
    }

    /** @test */
    public function workflow_engine_blocks_direct_pending_to_accepted_transition(): void
    {
        $engine = $this->makeWorkflowEngine();

        // Only 'approved' GRNs can be accepted; earlier states must not shortcut to 'accepted'
        $this->assertFalse($engine->canTransition('grn', 'inspection_passed', 'accepted'));
        $this->assertFalse($engine->canTransition('grn', 'submitted', 'accepted'));
        $this->assertFalse($engine->canTransition('grn', 'draft', 'accepted'));
    }

    /** @test */
    public function workflow_engine_acceptance_rejected_is_terminal(): void
    {
        $engine = $this->makeWorkflowEngine();

        $this->assertTrue($engine->isTerminalState('grn', 'acceptance_rejected'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // C) InvoiceService::createFromGRN() acceptance guard
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function invoice_creation_blocked_when_grn_acceptance_status_is_pending(): void
    {
        $grn = Mockery::mock(GoodsReceivedNote::class)->makePartial();
        $grn->status            = 'approved';
        $grn->acceptance_status = 'pending';
        $grn->grn_number        = 'GRN-001';
        $grn->shouldReceive('isApproved')->andReturn(true);
        $grn->shouldReceive('isAccepted')->andReturn(false);

        $service = $this->makeInvoiceService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/has not been accepted by the department/i');

        $service->createFromGRN($grn, []);
    }

    /** @test */
    public function invoice_creation_blocked_when_grn_acceptance_status_is_rejected(): void
    {
        $grn = Mockery::mock(GoodsReceivedNote::class)->makePartial();
        $grn->status            = 'approved';
        $grn->acceptance_status = 'rejected';
        $grn->grn_number        = 'GRN-002';
        $grn->shouldReceive('isApproved')->andReturn(true);
        $grn->shouldReceive('isAccepted')->andReturn(false);

        $service = $this->makeInvoiceService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/has not been accepted by the department/i');

        $service->createFromGRN($grn, []);
    }

    /** @test */
    public function invoice_creation_blocked_when_grn_not_approved_regardless_of_acceptance(): void
    {
        $grn = Mockery::mock(GoodsReceivedNote::class)->makePartial();
        $grn->status            = 'inspection_passed';
        $grn->acceptance_status = 'accepted'; // acceptance set but status wrong
        $grn->grn_number        = 'GRN-003';
        $grn->shouldReceive('isApproved')->andReturn(false);
        // isAccepted() should not even be reached
        $grn->shouldReceive('isAccepted')->never();

        $service = $this->makeInvoiceService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/approved GRN/i');

        $service->createFromGRN($grn, []);
    }

    /** @test */
    public function invoice_creation_proceeds_past_guard_when_grn_is_accepted(): void
    {
        // Mock the GRN to pass both guards; the transaction body will fail
        // because there's no real DB/PO data — but the guards themselves must pass.
        $grn = Mockery::mock(GoodsReceivedNote::class)->makePartial();
        $grn->status            = 'approved';
        $grn->acceptance_status = 'accepted';
        $grn->grn_number        = 'GRN-004';
        $grn->shouldReceive('isApproved')->andReturn(true);
        $grn->shouldReceive('isAccepted')->andReturn(true);

        $service = $this->makeInvoiceService();

        // The acceptance guard passes; the exception (if any) comes from DB logic, not the guard
        try {
            $service->createFromGRN($grn, []);
        } catch (\Exception $e) {
            $this->assertStringNotContainsStringIgnoringCase(
                'has not been accepted by the department',
                $e->getMessage(),
                'Acceptance guard should not throw when GRN is accepted'
            );
            $this->assertStringNotContainsStringIgnoringCase(
                'approved GRN',
                $e->getMessage(),
                'Approval guard should not throw when GRN is approved'
            );
        }
    }

    /** @test */
    public function invoice_creation_proceeds_past_guard_when_grn_is_partially_accepted(): void
    {
        $grn = Mockery::mock(GoodsReceivedNote::class)->makePartial();
        $grn->status            = 'approved';
        $grn->acceptance_status = 'partially_accepted';
        $grn->grn_number        = 'GRN-005';
        $grn->shouldReceive('isApproved')->andReturn(true);
        $grn->shouldReceive('isAccepted')->andReturn(true); // partially_accepted → isAccepted() === true

        $service = $this->makeInvoiceService();

        try {
            $service->createFromGRN($grn, []);
        } catch (\Exception $e) {
            $this->assertStringNotContainsStringIgnoringCase(
                'has not been accepted by the department',
                $e->getMessage(),
                'Acceptance guard should not throw for partially accepted GRN'
            );
        }
    }
}
