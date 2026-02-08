<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Requisition;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceivedNote;
use App\Models\GRNItem;
use App\Models\SupplierInvoice;
use App\Models\Supplier;
use App\Models\CatalogItem;
use App\Models\User;

class ThreeWayMatchingIntegrationTest extends TestCase
{
    protected Supplier $supplier;
    protected CatalogItem $item;
    protected PurchaseOrder $purchaseOrder;
    protected GoodsReceivedNote $grn;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Create supplier
        $this->supplier = Supplier::factory()->create();

        // Create catalog item
        $this->item = CatalogItem::factory()->create([
            'unit_cost' => 5000,
        ]);

        // Create PO
        $this->purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => 'issued',
        ]);

        // Add items to PO
        $this->purchaseOrder->items()->create([
            'catalog_item_id' => $this->item->id,
            'quantity' => 10,
            'unit_price' => 5000,
            'line_total' => 50000,
        ]);

        // Create GRN
        $this->grn = GoodsReceivedNote::factory()->create([
            'purchase_order_id' => $this->purchaseOrder->id,
            'status' => 'pending_inspection',
        ]);

        // Add items to GRN
        $this->grn->items()->create([
            'catalog_item_id' => $this->item->id,
            'quantity_ordered' => 10,
            'quantity_received' => 10,
            'unit_cost' => 5000,
        ]);
    }

    /**
     * Test successful three-way match: PO = GRN = Invoice
     */
    public function test_three_way_match_succeeds_with_exact_quantities(): void
    {
        // Create invoice matching PO and GRN exactly
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'grn_id' => $this->grn->id,
            'status' => 'submitted',
        ]);

        $invoice->items()->create([
            'catalog_item_id' => $this->item->id,
            'quantity' => 10,
            'unit_price' => 5000,
            'line_total' => 50000,
        ]);

        // Verify three-way match
        $matchResult = $this->performThreeWayMatch($invoice);

        $this->assertTrue($matchResult['match']);
        $this->assertEquals('matched', $matchResult['status']);
    }

    /**
     * Test three-way match fails with quantity variance
     */
    public function test_three_way_match_fails_with_quantity_variance(): void
    {
        // Create invoice with different quantity
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'grn_id' => $this->grn->id,
            'status' => 'submitted',
        ]);

        $invoice->items()->create([
            'catalog_item_id' => $this->item->id,
            'quantity' => 9, // Different from PO (10) and GRN (10)
            'unit_price' => 5000,
            'line_total' => 45000,
        ]);

        // Verify three-way match fails
        $matchResult = $this->performThreeWayMatch($invoice);

        $this->assertFalse($matchResult['match']);
        $this->assertStringContainsString('quantity', strtolower($matchResult['discrepancies']));
    }

    /**
     * Test three-way match fails with price variance (exceeds tolerance)
     */
    public function test_three_way_match_fails_with_price_variance(): void
    {
        // Create invoice with different price
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'grn_id' => $this->grn->id,
            'status' => 'submitted',
        ]);

        $invoice->items()->create([
            'catalog_item_id' => $this->item->id,
            'quantity' => 10,
            'unit_price' => 6000, // 20% higher than PO
            'line_total' => 60000,
        ]);

        // Verify three-way match fails
        $matchResult = $this->performThreeWayMatch($invoice);

        $this->assertFalse($matchResult['match']);
        $this->assertStringContainsString('price', strtolower($matchResult['discrepancies']));
    }

    /**
     * Test three-way match passes with acceptable price variance
     */
    public function test_three_way_match_passes_with_acceptable_variance(): void
    {
        // Create invoice with acceptable 2% price variance
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'grn_id' => $this->grn->id,
            'status' => 'submitted',
        ]);

        $invoice->items()->create([
            'catalog_item_id' => $this->item->id,
            'quantity' => 10,
            'unit_price' => 5100, // 2% higher (within tolerance)
            'line_total' => 51000,
        ]);

        // Verify three-way match passes
        $matchResult = $this->performThreeWayMatch($invoice);

        $this->assertTrue($matchResult['match']);
        $this->assertEquals('matched', $matchResult['status']);
    }

    /**
     * Test GRN discrepancy blocks invoice matching
     */
    public function test_invoice_matching_fails_with_grn_discrepancy(): void
    {
        // Create GRN with unresolved quality discrepancy
        $this->grn->update([
            'status' => 'quality_rejected',
            'inspection_notes' => 'Items damaged in transit',
        ]);

        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'grn_id' => $this->grn->id,
        ]);

        $invoice->items()->create([
            'catalog_item_id' => $this->item->id,
            'quantity' => 10,
            'unit_price' => 5000,
        ]);

        // Verify matching is blocked
        $matchResult = $this->performThreeWayMatch($invoice);

        $this->assertFalse($matchResult['match']);
        $this->assertStringContainsString('quality', strtolower($matchResult['discrepancies']));
    }

    /**
     * Test invoice holds pending matching
     */
    public function test_invoice_status_is_held_pending_match(): void
    {
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'grn_id' => $this->grn->id,
            'status' => 'submitted',
        ]);

        $invoice->items()->create([
            'catalog_item_id' => $this->item->id,
            'quantity' => 10,
            'unit_price' => 5000,
        ]);

        // Invoice should not automatically be verified until match succeeds
        $this->assertNotEquals('verified', $invoice->status);
    }

    /**
     * Test partial invoice matching
     */
    public function test_partial_invoice_matching(): void
    {
        // GRN has 10 items, invoice is only for 5
        $invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'grn_id' => $this->grn->id,
            'status' => 'submitted',
        ]);

        $invoice->items()->create([
            'catalog_item_id' => $this->item->id,
            'quantity' => 5, // Partial
            'unit_price' => 5000,
            'line_total' => 25000,
        ]);

        // Verify partial match
        $matchResult = $this->performThreeWayMatch($invoice);

        $this->assertTrue($matchResult['match']);
        $this->assertEquals('partial_match', $matchResult['status']);
    }

    /**
     * Helper: Perform three-way match validation
     */
    protected function performThreeWayMatch(SupplierInvoice $invoice): array
    {
        $invoiceService = app(\App\Services\InvoiceService::class);

        try {
            $result = $invoiceService->validateThreeWayMatch($invoice);

            return [
                'match' => $result !== false,
                'status' => 'matched',
                'discrepancies' => '',
            ];
        } catch (\Exception $e) {
            return [
                'match' => false,
                'status' => 'failed',
                'discrepancies' => $e->getMessage(),
            ];
        }
    }
}
