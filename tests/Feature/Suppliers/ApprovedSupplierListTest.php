<?php

namespace Tests\Feature\Suppliers;

use App\Core\Audit\AuditService;
use App\Models\User;
use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Suppliers\Models\SupplierDocument;
use App\Modules\Suppliers\Services\SupplierService;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Approved Supplier List (ASL) Tests
 *
 * Coverage:
 *   A) SupplierService ASL status transitions
 *   B) Onboarding completeness calculation
 *   C) ASL enforcement in ProcurementService (unapproved supplier blocked from RFQ)
 */
class ApprovedSupplierListTest extends TestCase
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

    private function makeSupplier(string $aslStatus = 'not_applied', string $onboarding = 'incomplete'): Supplier
    {
        $s = new Supplier();
        $s->supplier_code      = 'SUP' . uniqid();
        $s->name              = 'Test Supplier';
        $s->type              = 'company';
        $s->status            = 'active';
        $s->asl_status        = $aslStatus;
        $s->onboarding_status = $onboarding;
        // Required for display_name accessor
        $s->business_name     = 'Test Supplier Ltd';
        return $s;
    }

    private function makeSupplierService(): SupplierService
    {
        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('log')->andReturn(1)->byDefault();
        return new SupplierService($audit);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // A) ASL status transitions
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function supplier_can_be_submitted_for_asl_review(): void
    {
        $service  = $this->makeSupplierService();
        $supplier = $this->makeSupplier('not_applied');
        $supplier->save();

        $service->submitForASLReview($supplier);

        $this->assertEquals('pending_review', $supplier->fresh()->asl_status);
    }

    /** @test */
    public function supplier_with_removed_status_can_be_resubmitted(): void
    {
        $service  = $this->makeSupplierService();
        $supplier = $this->makeSupplier('removed');
        $supplier->save();

        $service->submitForASLReview($supplier);

        $this->assertEquals('pending_review', $supplier->fresh()->asl_status);
    }

    /** @test */
    public function already_approved_supplier_cannot_be_submitted_for_review(): void
    {
        $service  = $this->makeSupplierService();
        $supplier = $this->makeSupplier('approved');
        $supplier->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/cannot be submitted/');

        $service->submitForASLReview($supplier);
    }

    /** @test */
    public function asl_approval_sets_correct_fields(): void
    {
        $service  = $this->makeSupplierService();
        $approver = User::factory()->create();
        $supplier = $this->makeSupplier('pending_review');
        $supplier->save();

        // Create all required documents for the supplier
        foreach (SupplierService::REQUIRED_DOCUMENTS as $docType) {
            $supplier->documents()->create([
                'document_type' => $docType,
                'file_path'     => "docs/{$docType}.pdf",
                'file_name'     => "{$docType}.pdf",
                'expiry_date'   => now()->addYear(),
                'is_required'   => true,
                'verified'      => true,
            ]);
        }

        $service->approveForASL($supplier, $approver, ['stationery', 'it_equipment']);

        $fresh = $supplier->fresh();
        $this->assertEquals('approved', $fresh->asl_status);
        $this->assertEquals('approved', $fresh->onboarding_status);
        $this->assertEquals($approver->id, $fresh->asl_approved_by);
        $this->assertNotNull($fresh->asl_approved_at);
        $this->assertTrue($fresh->asl_review_due_at->isAfter(now()->addMonths(11)));
    }

    /** @test */
    public function asl_approval_blocked_when_documents_missing(): void
    {
        $service  = $this->makeSupplierService();
        $approver = User::factory()->create();
        $supplier = $this->makeSupplier('pending_review');
        $supplier->save();
        // No documents uploaded

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/onboarding incomplete/');

        $service->approveForASL($supplier, $approver);
    }

    /** @test */
    public function approved_supplier_can_be_suspended(): void
    {
        $service  = $this->makeSupplierService();
        $supplier = $this->makeSupplier('approved');
        $supplier->save();

        $service->suspendFromASL($supplier, 'Non-compliance with contract terms');

        $this->assertEquals('suspended', $supplier->fresh()->asl_status);
    }

    /** @test */
    public function non_approved_supplier_cannot_be_suspended(): void
    {
        $service  = $this->makeSupplierService();
        $supplier = $this->makeSupplier('pending_review');
        $supplier->save();

        $this->expectException(\Exception::class);
        $service->suspendFromASL($supplier, 'Some reason here');
    }

    /** @test */
    public function supplier_can_be_removed_from_asl(): void
    {
        $service  = $this->makeSupplierService();
        $supplier = $this->makeSupplier('approved');
        $supplier->save();

        $service->removeFromASL($supplier, 'Repeated contract violations');

        $this->assertEquals('removed', $supplier->fresh()->asl_status);
    }

    /** @test */
    public function is_approved_supplier_returns_true_only_for_approved_status(): void
    {
        $service = $this->makeSupplierService();

        $approved    = $this->makeSupplier('approved');
        $pending     = $this->makeSupplier('pending_review');
        $notApplied  = $this->makeSupplier('not_applied');

        $this->assertTrue($service->isApprovedSupplier($approved));
        $this->assertFalse($service->isApprovedSupplier($pending));
        $this->assertFalse($service->isApprovedSupplier($notApplied));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // B) Onboarding completeness
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function onboarding_completeness_returns_zero_when_no_documents(): void
    {
        $service  = $this->makeSupplierService();
        $supplier = $this->makeSupplier();
        $supplier->save();

        $result = $service->calculateOnboardingCompleteness($supplier);

        $this->assertFalse($result['complete']);
        $this->assertEquals(0, $result['percentage']);
        $this->assertCount(4, $result['missing']); // all 4 required
        $this->assertEmpty($result['expired']);
    }

    /** @test */
    public function onboarding_completeness_returns_100_when_all_documents_present(): void
    {
        $service  = $this->makeSupplierService();
        $supplier = $this->makeSupplier();
        $supplier->save();

        foreach (SupplierService::REQUIRED_DOCUMENTS as $docType) {
            $supplier->documents()->create([
                'document_type' => $docType,
                'file_path'     => "docs/{$docType}.pdf",
                'file_name'     => "{$docType}.pdf",
                'expiry_date'   => now()->addYear(),
                'is_required'   => true,
                'verified'      => false,
            ]);
        }

        $result = $service->calculateOnboardingCompleteness($supplier);

        $this->assertTrue($result['complete']);
        $this->assertEquals(100, $result['percentage']);
        $this->assertEmpty($result['missing']);
        $this->assertEmpty($result['expired']);
    }

    /** @test */
    public function expired_document_is_reported_in_expired_list(): void
    {
        $service  = $this->makeSupplierService();
        $supplier = $this->makeSupplier();
        $supplier->save();

        // Upload all docs but make tax compliance expired
        foreach (SupplierService::REQUIRED_DOCUMENTS as $docType) {
            $supplier->documents()->create([
                'document_type' => $docType,
                'file_path'     => "docs/{$docType}.pdf",
                'file_name'     => "{$docType}.pdf",
                'expiry_date'   => $docType === 'tax_compliance_certificate'
                    ? now()->subDay()   // expired
                    : now()->addYear(), // valid
                'is_required'   => true,
                'verified'      => false,
            ]);
        }

        $result = $service->calculateOnboardingCompleteness($supplier);

        $this->assertFalse($result['complete']);
        $this->assertContains('tax_compliance_certificate', $result['expired']);
        $this->assertEmpty($result['missing']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // C) ASL enforcement in ProcurementService
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function unapproved_supplier_cannot_be_added_to_rfq(): void
    {
        $supplier = $this->makeSupplier('not_applied');
        $supplier->save();

        $service = app(ProcurementService::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/not on the Approved Supplier List/');

        $service->createRFQ([
            'title'              => 'Test RFQ',
            'description'        => 'Test RFQ',
            'budget_allocation'  => 30000,
            'supplier_ids'       => [$supplier->id],
        ]);
    }

    /** @test */
    public function approved_supplier_can_be_added_to_rfq(): void
    {
        $supplier = $this->makeSupplier('approved');
        $supplier->save();

        $service = app(ProcurementService::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        // Should not throw — ProcurementProcess will be created
        $process = $service->createRFQ([
            'title'            => 'Test RFQ',
            'description'      => 'Test RFQ',
            'budget_allocation' => 30000,
            'supplier_ids'     => [$supplier->id],
        ]);

        $this->assertNotNull($process->id);
    }

    /** @test */
    public function suspended_supplier_is_blocked_from_rfq(): void
    {
        $supplier = $this->makeSupplier('suspended');
        $supplier->save();

        $service = app(ProcurementService::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/not on the Approved Supplier List/');

        $service->createRFQ([
            'title'            => 'Test RFQ',
            'description'      => 'Test RFQ',
            'budget_allocation' => 30000,
            'supplier_ids'     => [$supplier->id],
        ]);
    }
}
