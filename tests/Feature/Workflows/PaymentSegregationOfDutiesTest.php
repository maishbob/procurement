<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\SupplierInvoice;

class PaymentSegregationOfDutiesTest extends TestCase
{
    protected User $creator;
    protected User $approver;
    protected User $processor;
    protected User $wrongApprover;
    protected Supplier $supplier;
    protected SupplierInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        // Create supplier
        $this->supplier = Supplier::factory()->create();

        // Create invoice
        $this->invoice = SupplierInvoice::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => 'verified',
        ]);

        // Create payment roles with different users
        $this->creator = User::factory()->create(['approval_limit' => 100000]);
        $this->creator->assignRole('finance_officer');

        $this->approver = User::factory()->create(['approval_limit' => 500000]);
        $this->approver->assignRole('finance_manager');

        $this->processor = User::factory()->create();
        $this->processor->assignRole('treasurer');

        // Wrong approver - same person as creator
        $this->wrongApprover = $this->creator;
    }

    /**
     * Test payment can be created by finance officer
     */
    public function test_payment_created_by_finance_officer(): void
    {
        $this->actingAs($this->creator);

        $response = $this->post(route('payments.store'), [
            'supplier_id' => $this->supplier->id,
            'invoice_ids' => [$this->invoice->id],
            'amount' => 50000,
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertDatabaseHas('payments', [
            'status' => 'draft',
            'created_by' => $this->creator->id,
        ]);
    }

    /**
     * Test payment submission by creator
     */
    public function test_payment_submitted_by_creator(): void
    {
        $payment = Payment::factory()->create([
            'status' => 'draft',
            'created_by' => $this->creator->id,
            'supplier_id' => $this->supplier->id,
        ]);

        $this->actingAs($this->creator);

        $response = $this->post(route('payments.submit', $payment), [
            'submission_notes' => 'Ready for approval',
        ]);

        $this->assertEquals('submitted', $payment->fresh()->status);
    }

    /**
     * Test payment approval by different user (segregation of duties)
     */
    public function test_payment_approved_by_different_user(): void
    {
        $payment = Payment::factory()->create([
            'status' => 'submitted',
            'created_by' => $this->creator->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 50000,
        ]);

        // Approver is different from creator
        $this->assertNotEquals($this->creator->id, $this->approver->id);

        $this->actingAs($this->approver);

        $response = $this->post(route('payments.approve', $payment), [
            'approval_notes' => 'Approved',
        ]);

        $this->assertEquals('approved', $payment->fresh()->status);
    }

    /**
     * CRITICAL: Test payment approval FAILS when approver is creator
     */
    public function test_payment_approval_fails_when_approver_is_creator(): void
    {
        $payment = Payment::factory()->create([
            'status' => 'submitted',
            'created_by' => $this->creator->id,
            'supplier_id' => $this->supplier->id,
        ]);

        $this->actingAs($this->creator); // Creator trying to approve own payment

        $response = $this->post(route('payments.approve', $payment), [
            'approval_notes' => 'Approved by self',
        ]);

        // Should be forbidden or fail
        $this->assertTrue($response->isForbidden() || $response->isRedirect());

        // Payment should NOT be approved
        $this->assertNotEquals('approved', $payment->fresh()->status);
    }

    /**
     * Test payment processing by different user (triple segregation)
     */
    public function test_payment_processed_by_third_user(): void
    {
        $payment = Payment::factory()->create([
            'status' => 'approved',
            'created_by' => $this->creator->id,
            'approved_by' => $this->approver->id,
            'supplier_id' => $this->supplier->id,
        ]);

        // Processor is different from both creator and approver
        $this->assertNotEquals($this->creator->id, $this->processor->id);
        $this->assertNotEquals($this->approver->id, $this->processor->id);

        $this->actingAs($this->processor);

        $response = $this->post(route('payments.process', $payment), [
            'reference_number' => 'TXN/001/2026',
            'processing_notes' => 'Processed',
        ]);

        $this->assertEquals('processed', $payment->fresh()->status);
    }

    /**
     * CRITICAL: Test payment CANNOT be processed by approver
     */
    public function test_payment_processing_fails_when_processor_is_approver(): void
    {
        $payment = Payment::factory()->create([
            'status' => 'approved',
            'created_by' => $this->creator->id,
            'approved_by' => $this->approver->id,
            'supplier_id' => $this->supplier->id,
        ]);

        $this->actingAs($this->approver); // Approver trying to process

        $response = $this->post(route('payments.process', $payment), [
            'reference_number' => 'TXN/002/2026',
            'processing_notes' => 'Processed',
        ]);

        // Should be forbidden
        $this->assertTrue($response->isForbidden() || $response->isRedirect());

        // Payment should NOT be processed
        $this->assertNotEquals('processed', $payment->fresh()->status);
    }

    /**
     * Test approval authority limit is enforced
     */
    public function test_approver_cannot_approve_over_limit(): void
    {
        $lowLimitApprover = User::factory()->create(['approval_limit' => 10000]);
        $lowLimitApprover->assignRole('finance_manager');

        $payment = Payment::factory()->create([
            'status' => 'submitted',
            'created_by' => $this->creator->id,
            'supplier_id' => $this->supplier->id,
            'amount' => 50000, // Exceeds limit
        ]);

        $this->actingAs($lowLimitApprover);

        $response = $this->post(route('payments.approve', $payment), [
            'approval_notes' => 'Approved',
        ]);

        // Should fail
        $this->assertFalse($response->isSuccessful());
    }

    /**
     * Test payment rejection
     */
    public function test_payment_can_be_rejected(): void
    {
        $payment = Payment::factory()->create([
            'status' => 'submitted',
            'supplier_id' => $this->supplier->id,
        ]);

        $this->actingAs($this->approver);

        $response = $this->post(route('payments.reject', $payment), [
            'rejection_reason' => 'Missing documentation',
        ]);

        $this->assertEquals('rejected', $payment->fresh()->status);
    }

    /**
     * Test complete segregation of duties flow audit trail
     */
    public function test_segregation_flow_is_audited(): void
    {
        $payment = Payment::factory()->create([
            'status' => 'draft',
            'created_by' => $this->creator->id,
            'supplier_id' => $this->supplier->id,
        ]);

        // Submit
        $this->actingAs($this->creator);
        $payment->update(['status' => 'submitted']);

        // Approve
        $this->actingAs($this->approver);
        $payment->update(['status' => 'approved', 'approved_by' => $this->approver->id]);

        // Process
        $this->actingAs($this->processor);
        $payment->update(['status' => 'processed']);

        // Check audit logs
        $logs = \App\Models\AuditLog::where('model_id', $payment->id)->get();

        $this->assertGreaterThan(2, $logs->count()); // Multiple state changes logged

        // Verify different users in the flow
        $userIds = $logs->pluck('user_id')->unique();
        $this->assertGreaterThanOrEqual(3, $userIds->count()); // At least 3 different users
    }
}
