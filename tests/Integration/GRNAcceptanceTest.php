<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Modules\GRN\Models\GoodsReceivedNote;
use App\Modules\Finance\Services\InvoiceService;
use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use App\Core\Rules\GovernanceRules;
use App\Core\TaxEngine\TaxEngine;
use App\Core\CurrencyEngine\CurrencyEngine;
use App\Modules\Quality\Services\CapaService;
use Mockery;
use Exception;

class GRNAcceptanceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper to mock InvoiceService with its dependencies.
     */
    protected function getInvoiceService()
    {
        return app(InvoiceService::class);
    }

    /**
     * T-2.6: Verify that invoice creation is blocked for pending GRNs.
     */
    public function test_invoice_creation_is_blocked_for_pending_grn(): void
    {
        $grn = Mockery::mock(GoodsReceivedNote::class)->makePartial();
        $grn->shouldReceive('isApproved')->andReturn(true);
        $grn->shouldReceive('isAccepted')->andReturn(false);
        $grn->shouldReceive('getAttribute')->with('grn_number')->andReturn('GRN-001');
        $grn->shouldReceive('getAttribute')->with('acceptance_status')->andReturn('pending');
        
        // Use property access instead of just getAttribute for the exact way it might be accessed
        $grn->grn_number = 'GRN-001';
        $grn->acceptance_status = 'pending';

        $service = new InvoiceService(
            Mockery::mock(AuditService::class),
            Mockery::mock(WorkflowEngine::class),
            Mockery::mock(GovernanceRules::class),
            Mockery::mock(TaxEngine::class),
            Mockery::mock(CurrencyEngine::class),
            Mockery::mock(CapaService::class)
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('GRN #GRN-001 has not been accepted by the department');

        $service->createFromGRN($grn, []);
    }

    /**
     * T-2.6: Verify that invoice creation is blocked for rejected GRNs.
     */
    public function test_invoice_creation_is_blocked_for_rejected_grn(): void
    {
        $grn = Mockery::mock(GoodsReceivedNote::class)->makePartial();
        $grn->shouldReceive('isApproved')->andReturn(true);
        $grn->shouldReceive('isAccepted')->andReturn(false);
        $grn->shouldReceive('getAttribute')->with('grn_number')->andReturn('GRN-002');
        $grn->shouldReceive('getAttribute')->with('acceptance_status')->andReturn('rejected');

        $grn->grn_number = 'GRN-002';
        $grn->acceptance_status = 'rejected';

        $service = new InvoiceService(
            Mockery::mock(AuditService::class),
            Mockery::mock(WorkflowEngine::class),
            Mockery::mock(GovernanceRules::class),
            Mockery::mock(TaxEngine::class),
            Mockery::mock(CurrencyEngine::class),
            Mockery::mock(CapaService::class)
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('GRN #GRN-002 has not been accepted by the department');

        $service->createFromGRN($grn, []);
    }

    /**
     * T-2.6: Test the happy path and rejection path workflow statuses.
     */
    public function test_grn_workflow_status_transitions(): void
    {
        $grn = new GoodsReceivedNote();
        
        // Initial state
        $grn->status = 'received';
        $grn->acceptance_status = 'pending';
        
        $this->assertFalse($grn->isAccepted(), 'GRN should not be accepted when received');
        
        // Inspected
        $grn->status = 'inspected';
        $grn->acceptance_status = 'pending';
        
        $this->assertFalse($grn->isAccepted(), 'GRN should not be accepted when just inspected');
        
        // Approved (but not yet department accepted)
        $grn->status = 'approved';
        $grn->acceptance_status = 'pending';
        
        $this->assertTrue($grn->isPendingAcceptance(), 'Approved GRN should be pending acceptance');
        $this->assertTrue($grn->canBeAccepted(), 'Approved GRN can be accepted');
        $this->assertFalse($grn->isAccepted(), 'GRN should not be fully accepted yet');

        // Path 1 (Rejection): Department rejects
        $grnRejected = config('app.env') ? clone $grn : clone $grn;
        $grnRejected->acceptance_status = 'rejected';
        
        $this->assertFalse($grnRejected->isAccepted(), 'Rejected GRN is not accepted');
        $this->assertFalse($grnRejected->isPendingAcceptance(), 'Rejected GRN no longer pending');

        // Path 2 (Happy Path): partially_accepted -> invoiced
        $grnPartiallyAccepted = config('app.env') ? clone $grn : clone $grn;
        $grnPartiallyAccepted->acceptance_status = 'partially_accepted';
        
        $this->assertTrue($grnPartiallyAccepted->isAccepted(), 'Partially accepted GRN should be considered accepted for invoice creation');
        
        // Check accepted state
        $grnAccepted = config('app.env') ? clone $grn : clone $grn;
        $grnAccepted->acceptance_status = 'accepted';
        
        $this->assertTrue($grnAccepted->isAccepted(), 'Accepted GRN is accepted');
    }
}
