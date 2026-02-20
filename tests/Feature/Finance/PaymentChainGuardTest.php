<?php

namespace Tests\Feature\Finance;

use App\Core\Audit\AuditService;
use App\Core\CurrencyEngine\CurrencyEngine;
use App\Core\Rules\GovernanceRules;
use App\Core\TaxEngine\TaxEngine;
use App\Core\Workflow\WorkflowEngine;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\SupplierInvoice;
use App\Modules\Finance\Services\PaymentService;
use App\Modules\GRN\Models\GoodsReceivedNote;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * Payment Chain Guard Tests — No-PO-No-Pay / No-GRN-No-Pay / No-Acceptance-No-Pay
 *
 * Coverage:
 *   A) validatePaymentChain() passes when the full chain exists
 *   B) Blocked when invoice has no Purchase Order
 *   C) Blocked when PO is in a disallowed status
 *   D) Blocked when invoice has no GRN
 *   E) Blocked when GRN acceptance status is pending or rejected
 */
class PaymentChainGuardTest extends TestCase
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

    private function makePaymentService(): PaymentService
    {
        $audit    = Mockery::mock(AuditService::class);
        $workflow = Mockery::mock(WorkflowEngine::class);
        $govRules = Mockery::mock(GovernanceRules::class);
        $tax      = Mockery::mock(TaxEngine::class);
        $currency = Mockery::mock(CurrencyEngine::class);
        $budget   = Mockery::mock(BudgetService::class);

        $audit->shouldReceive('log')->andReturn(null)->byDefault();

        return new PaymentService($audit, $workflow, $govRules, $tax, $currency, $budget);
    }

    /**
     * Build an in-memory SupplierInvoice stub with controlled relationships.
     */

    private function makeInvoice($purchaseOrder = null, $grn = null, $invoiceNumber = null): SupplierInvoice
    {
        $attributes = [];
        if ($invoiceNumber) {
            $attributes['invoice_number'] = $invoiceNumber;
        }
        // Explicitly set purchase_order_id to null if no PO provided
        $attributes['purchase_order_id'] = $purchaseOrder ? $purchaseOrder->id : null;
        // Explicitly set grn_id to null if no GRN provided
        $attributes['grn_id'] = $grn ? $grn->id : null;
        return \App\Modules\Finance\Models\SupplierInvoice::factory()->create($attributes);
    }

    private function makePo(string $status = 'approved')
    {
        return \App\Modules\PurchaseOrders\Models\PurchaseOrder::factory()->create(['status' => $status]);
    }

    private function makeGrn(string $acceptanceStatus = 'accepted')
    {
        return \App\Modules\GRN\Models\GoodsReceivedNote::factory()->create(['acceptance_status' => $acceptanceStatus]);
    }


    private function callValidateChain(PaymentService $service, Collection $invoices): void
    {
        // validatePaymentChain is protected — call via Reflection
        $method = new \ReflectionMethod(PaymentService::class, 'validatePaymentChain');
        $method->setAccessible(true);
        $method->invoke($service, $invoices);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // A) Full chain present — should pass
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function payment_chain_passes_when_po_approved_and_grn_accepted(): void
    {
        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice($this->makePo('approved'), $this->makeGrn('accepted'));

        $this->expectNotToPerformAssertions();
        $this->callValidateChain($service, collect([$invoice]));
    }

    /** @test */
    public function payment_chain_passes_when_grn_is_partially_accepted(): void
    {
        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice($this->makePo('issued'), $this->makeGrn('partially_accepted'));

        $this->expectNotToPerformAssertions();
        $this->callValidateChain($service, collect([$invoice]));
    }

    /** @test */
    public function payment_chain_passes_for_po_in_fully_received_status(): void
    {
        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice($this->makePo('fully_received'), $this->makeGrn('accepted'));

        $this->expectNotToPerformAssertions();
        $this->callValidateChain($service, collect([$invoice]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // B) No PO — blocked
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function payment_blocked_when_invoice_has_no_purchase_order(): void
    {
        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice(null, $this->makeGrn('accepted'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/has no linked Purchase Order \(No PO, No Pay\)/');

        $this->callValidateChain($service, collect([$invoice]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // C) PO in wrong status — blocked
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function payment_blocked_when_po_is_in_draft_status(): void
    {
        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice($this->makePo('draft'), $this->makeGrn('accepted'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/has not been approved/');

        $this->callValidateChain($service, collect([$invoice]));
    }

    /** @test */
    public function payment_blocked_when_po_is_cancelled(): void
    {
        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice($this->makePo('cancelled'), $this->makeGrn('accepted'));

        $this->expectException(\Exception::class);
        $this->callValidateChain($service, collect([$invoice]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // D) No GRN — blocked
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function payment_blocked_when_invoice_has_no_grn(): void
    {
        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice($this->makePo('approved'), null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/has no linked Goods Received Note \(No GRN, No Pay\)/');

        $this->callValidateChain($service, collect([$invoice]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // E) GRN not accepted — blocked
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function payment_blocked_when_grn_acceptance_is_pending(): void
    {
        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice($this->makePo('approved'), $this->makeGrn('pending'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/has not been accepted by the department/');

        $this->callValidateChain($service, collect([$invoice]));
    }

    /** @test */
    public function payment_blocked_when_grn_acceptance_is_rejected(): void
    {

        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice($this->makePo('approved'), $this->makeGrn('rejected'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/has not been accepted by the department/');

        $this->callValidateChain($service, collect([$invoice]));
    }

    /** @test */
    public function error_message_identifies_the_specific_invoice_that_failed(): void
    {
        $service = $this->makePaymentService();
        $invoice = $this->makeInvoice(null, null, 'INV-2025-999');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/INV-2025-999/');
        $this->callValidateChain($service, collect([$invoice]));
    }
}
