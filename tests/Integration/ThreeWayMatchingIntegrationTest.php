<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Core\Rules\GovernanceRules;
use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use App\Core\TaxEngine\TaxEngine;
use App\Core\CurrencyEngine\CurrencyEngine;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\Finance\Models\SupplierInvoice;
use App\Modules\Quality\Services\CapaService;
use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use App\Modules\GRN\Models\GoodsReceivedNote;
use Mockery;

/**
 * Three-Way Matching Integration Tests
 *
 * Covers the call chain:
 *   InvoiceService::performThreeWayMatch()
 *     → GovernanceRules::validateThreeWayMatch(array $po, array $grn, array $invoice)
 *
 * T-1.3 regression: Verifies the structured-array argument format introduced
 * in the T-1.2 fix. Before T-1.2, InvoiceService passed scalar values; the
 * fix changed the call to pass ['quantity' => ..., 'amount' => ...] arrays,
 * and reads ['matched'] instead of ['passed'] from the result.
 */
class ThreeWayMatchingIntegrationTest extends TestCase
{
    protected GovernanceRules $governanceRules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->governanceRules = app(GovernanceRules::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GovernanceRules::validateThreeWayMatch() — direct unit coverage
    // These tests prove the method works correctly with structured arrays
    // (the format InvoiceService now uses after the T-1.2 fix).
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * T-1.3: Exact amounts and quantities pass three-way match.
     */
    public function test_exact_amounts_pass_three_way_match(): void
    {
        $result = $this->governanceRules->validateThreeWayMatch(
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 50000.00]
        );

        $this->assertTrue($result['matched']);
        $this->assertEmpty($result['variances']);
        $this->assertArrayHasKey('tolerance_percent', $result);
    }

    /**
     * T-1.3: Invoice amount 1% above PO is within the 2% tolerance — passes.
     */
    public function test_one_percent_invoice_variance_passes(): void
    {
        $result = $this->governanceRules->validateThreeWayMatch(
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 50500.00]  // +1.0 %
        );

        $this->assertTrue($result['matched']);
        $this->assertEmpty($result['variances']);
    }

    /**
     * T-1.3: Invoice amount exactly 2% above PO sits at the boundary — passes.
     * (The check is strict-greater-than, so 2.0% exactly does not trigger a variance.)
     */
    public function test_exactly_two_percent_variance_passes(): void
    {
        $result = $this->governanceRules->validateThreeWayMatch(
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 51000.00]  // exactly +2.0 %
        );

        $this->assertTrue($result['matched']);
    }

    /**
     * T-1.3: Invoice amount 3% above PO exceeds tolerance — fails.
     * Regression: before T-1.2 the call used scalar args and would
     * have thrown a TypeError, never reaching this check.
     */
    public function test_three_percent_invoice_variance_fails(): void
    {
        $result = $this->governanceRules->validateThreeWayMatch(
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 51500.00]  // +3.0 %
        );

        $this->assertFalse($result['matched']);
        $this->assertNotEmpty($result['variances']);

        $amountVariance = collect($result['variances'])->firstWhere('field', 'amount');
        $this->assertNotNull($amountVariance, 'Expected an amount-field variance entry');
        $this->assertEquals(50000.00, $amountVariance['po_value']);
        $this->assertEquals(51500.00, $amountVariance['invoice_value']);
        $this->assertEquals(3.0, $amountVariance['variance_percent']);
    }

    /**
     * T-1.3: Large over-charge (20%) is clearly blocked.
     */
    public function test_twenty_percent_invoice_variance_fails(): void
    {
        $result = $this->governanceRules->validateThreeWayMatch(
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 60000.00]  // +20 %
        );

        $this->assertFalse($result['matched']);
    }

    /**
     * T-1.3: GRN quantity shortfall (40%) exceeds tolerance — detected as
     * a quantity variance between PO and GRN.
     */
    public function test_grn_quantity_shortfall_fails(): void
    {
        // GRN received 6 of 10 ordered (40 % shortfall)
        $result = $this->governanceRules->validateThreeWayMatch(
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 6,  'amount' => 30000.00],
            ['quantity' => 10, 'amount' => 50000.00]
        );

        $this->assertFalse($result['matched']);

        $qtyVariance = collect($result['variances'])->firstWhere('field', 'quantity');
        $this->assertNotNull($qtyVariance, 'Expected a quantity-field variance entry');
        $this->assertEquals(10, $qtyVariance['po_value']);
        $this->assertEquals(6,  $qtyVariance['grn_value']);
    }

    /**
     * T-1.3: Result always contains the three required keys.
     */
    public function test_result_always_contains_required_keys(): void
    {
        $result = $this->governanceRules->validateThreeWayMatch(
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 50000.00],
            ['quantity' => 10, 'amount' => 50000.00]
        );

        $this->assertArrayHasKey('matched',          $result);
        $this->assertArrayHasKey('variances',        $result);
        $this->assertArrayHasKey('tolerance_percent', $result);
        $this->assertEquals(2, $result['tolerance_percent']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Call-chain regression: InvoiceService → GovernanceRules
    //
    // T-1.2 fixed the argument format passed from InvoiceService to
    // GovernanceRules::validateThreeWayMatch(). These tests verify the
    // contract at the boundary: structured arrays must be passed, and the
    // 'matched' key (not the old 'passed' key) must be read from the result.
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * T-1.3 regression: GovernanceRules::validateThreeWayMatch() must be called
     * with three structured array arguments — each containing 'quantity' and
     * 'amount' keys — never with scalar values.
     *
     * Simulates the call chain via a GovernanceRules spy injected into an
     * InvoiceService instance, with the Eloquent relationship graph replaced
     * by lightweight anonymous-class objects so the test requires no database.
     */
    public function test_invoice_service_calls_governance_rules_with_structured_arrays(): void
    {
        $capturedPo      = null;
        $capturedGrn     = null;
        $capturedInvoice = null;

        // ── Spy on GovernanceRules to capture arguments ────────────────────
        /** @var GovernanceRules&\Mockery\MockInterface $spy */
        $spy = Mockery::mock(GovernanceRules::class)->makePartial();
        $spy->shouldReceive('validateThreeWayMatch')
            ->once()
            ->andReturnUsing(
                function (array $po, array $grn, array $invoice) use (
                    &$capturedPo, &$capturedGrn, &$capturedInvoice
                ) {
                    $capturedPo      = $po;
                    $capturedGrn     = $grn;
                    $capturedInvoice = $invoice;
                    return ['matched' => true, 'variances' => [], 'tolerance_percent' => 2];
                }
            );

        // ── Build minimal object graph (no DB required) ────────────────────
        $poItemObj = (object)[
            'id'         => 1,
            'quantity'   => 10,
            'unit_price' => 5000.0,
        ];

        $grnItemObj = (object)[
            'purchase_order_item_id' => 1,
            'quantity_accepted'      => 10,
        ];

        $invoiceItemObj = (object)[
            'id'                     => 1,
            'purchase_order_item_id' => 1,
            'quantity'               => 10,
            'unit_price'             => 5000.0,
            'line_number'            => 1,
        ];

        $poItems      = collect([$poItemObj]);
        $grnItems     = collect([$grnItemObj]);
        $invoiceItems = collect([$invoiceItemObj]);

        /** @var PurchaseOrder&\Mockery\MockInterface $poMock */
        $poMock = Mockery::mock(PurchaseOrder::class);
        $poMock->shouldReceive('getAttribute')->with('total_amount')->andReturn(50000.0);
        $poMock->shouldReceive('getAttribute')->with('items')->andReturn($poItems);

        /** @var GoodsReceivedNote&\Mockery\MockInterface $grnMock */
        $grnMock = Mockery::mock(GoodsReceivedNote::class);
        $grnMock->shouldReceive('getAttribute')->with('total_quantity_accepted')->andReturn(10);
        $grnMock->shouldReceive('getAttribute')->with('items')->andReturn($grnItems);

        /** @var SupplierInvoice&\Mockery\MockInterface $invoiceMock */
        $invoiceMock = Mockery::mock(SupplierInvoice::class);
        $invoiceMock->shouldReceive('getAttribute')->with('purchaseOrder')->andReturn($poMock);
        $invoiceMock->shouldReceive('getAttribute')->with('grn')->andReturn($grnMock);
        $invoiceMock->shouldReceive('getAttribute')->with('items')->andReturn($invoiceItems);
        $invoiceMock->shouldReceive('getAttribute')->with('total_amount')->andReturn(50000.0);
        $invoiceMock->shouldReceive('getAttribute')->with('id')->andReturn(99);
        $invoiceMock->shouldReceive('update')->andReturn(true);

        // Mock AuditService so logCompliance() does not attempt DB writes
        $auditMock = Mockery::mock(AuditService::class);
        $auditMock->shouldReceive('logCompliance')->andReturn(1);

        // ── Build InvoiceService with the spy injected ─────────────────────
        $service = new InvoiceService(
            $auditMock,
            app(WorkflowEngine::class),
            $spy,
            app(TaxEngine::class),
            app(CurrencyEngine::class),
            Mockery::mock(CapaService::class)->shouldIgnoreMissing()
        );

        // Authenticate so Auth::id() is non-null during update().
        // Use an unsaved model to avoid a database dependency in this unit test.
        $user = new \App\Models\User(['name' => 'Test', 'email' => 'test@test.com']);
        $user->id = 1;
        $this->actingAs($user);

        $service->performThreeWayMatch($invoiceMock);

        // ── Assertions: GovernanceRules received structured arrays ─────────
        $this->assertNotNull($capturedPo,      'GovernanceRules was not called with a PO array');
        $this->assertNotNull($capturedGrn,     'GovernanceRules was not called with a GRN array');
        $this->assertNotNull($capturedInvoice, 'GovernanceRules was not called with an Invoice array');

        foreach (['po' => $capturedPo, 'grn' => $capturedGrn, 'invoice' => $capturedInvoice] as $name => $arg) {
            $this->assertIsArray($arg,                     "{$name} arg must be an array, not a scalar");
            $this->assertArrayHasKey('quantity', $arg, "{$name} array must contain 'quantity' key");
            $this->assertArrayHasKey('amount',   $arg, "{$name} array must contain 'amount' key");
            $this->assertIsNumeric($arg['quantity'],       "{$name}['quantity'] must be numeric");
            $this->assertIsNumeric($arg['amount'],         "{$name}['amount'] must be numeric");
        }
    }

    /**
     * T-1.3 regression: InvoiceService stores 'passed'/'failed' in the
     * three_way_match_status field by reading the 'matched' key from
     * GovernanceRules result (not the old 'passed' key).
     */
    public function test_invoice_service_reads_matched_key_from_governance_result(): void
    {
        // GovernanceRules returns 'matched' — InvoiceService must read this key
        $spy = Mockery::mock(GovernanceRules::class)->makePartial();
        $spy->shouldReceive('validateThreeWayMatch')
            ->andReturn(['matched' => false, 'variances' => [
                ['field' => 'amount', 'po_value' => 50000, 'invoice_value' => 60000,
                 'variance' => 10000, 'variance_percent' => 20.0, 'grn_value' => null],
            ], 'tolerance_percent' => 2]);

        $capturedUpdate = null;

        $invoiceMock = Mockery::mock(SupplierInvoice::class);
        $invoiceMock->shouldReceive('getAttribute')->with('purchaseOrder')->andReturn(
            tap(Mockery::mock(PurchaseOrder::class), function ($m) {
                $m->shouldReceive('getAttribute')->with('total_amount')->andReturn(50000.0);
                $m->shouldReceive('getAttribute')->with('items')->andReturn(collect([]));
            })
        );
        $invoiceMock->shouldReceive('getAttribute')->with('grn')->andReturn(
            tap(Mockery::mock(GoodsReceivedNote::class), function ($m) {
                $m->shouldReceive('getAttribute')->with('total_quantity_accepted')->andReturn(10);
                $m->shouldReceive('getAttribute')->with('items')->andReturn(collect([]));
            })
        );
        $invoiceMock->shouldReceive('getAttribute')->with('items')->andReturn(collect([]));
        $invoiceMock->shouldReceive('getAttribute')->with('total_amount')->andReturn(60000.0);
        $invoiceMock->shouldReceive('getAttribute')->with('id')->andReturn(99);
        $invoiceMock->shouldReceive('getAttribute')->with('invoice_number')->andReturn('INV-TEST-001');
        $invoiceMock->shouldReceive('update')
            ->once()
            ->andReturnUsing(function (array $data) use (&$capturedUpdate) {
                $capturedUpdate = $data;
                return true;
            });

        $auditMock = Mockery::mock(AuditService::class);
        $auditMock->shouldReceive('logCompliance')->andReturn(1);

        $service = new InvoiceService(
            $auditMock,
            app(WorkflowEngine::class),
            $spy,
            app(TaxEngine::class),
            app(CurrencyEngine::class),
            Mockery::mock(CapaService::class)->shouldIgnoreMissing()
        );

        $user = new \App\Models\User(['name' => 'Test', 'email' => 'test@test.com']);
        $user->id = 1;
        $this->actingAs($user);

        $result = $service->performThreeWayMatch($invoiceMock);

        $this->assertFalse($result['passed']);
        $this->assertNotNull($capturedUpdate);
        $this->assertEquals('failed', $capturedUpdate['three_way_match_status'],
            "InvoiceService must write 'failed' when GovernanceRules returns matched=false");
        $this->assertFalse($capturedUpdate['three_way_match_passed']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
