<?php

namespace Tests\Feature\Procurement;

use App\Core\Audit\AuditService;
use App\Core\Rules\GovernanceRules;
use App\Core\Workflow\WorkflowEngine;
use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Suppliers\Services\SupplierService;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Cash Band Enforcement Tests
 *
 * Coverage:
 *   A) GovernanceRules::determineCashBand() — correct band per amount
 *   B) GovernanceRules::getRequiredSourcingMethod() — correct method per band
 *   C) GovernanceRules::getMinimumQuotes() — correct min quotes per band
 *   D) ProcurementService::createRFQ() — blocked for medium/high/strategic amounts
 *   E) ProcurementService::createRFP() — blocked for tender-band amounts
 *   F) ProcurementService::evaluateBids() — blocked when insufficient quotes received
 */
class CashBandEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create and authenticate a user for tests that require 'created_by'
        $user = \App\Models\User::factory()->create();
        $this->be($user);
    }

    private function makeGovernanceRules(): GovernanceRules
    {
        $audit = Mockery::mock(AuditService::class);
        return new GovernanceRules($audit);
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(
            Mockery::getContainer()->mockery_getExpectationCount()
        );
        Mockery::close();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // A) determineCashBand()
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function zero_amount_falls_in_micro_band(): void
    {
        $rules = $this->makeGovernanceRules();
        $band  = $rules->determineCashBand(0);
        $this->assertEquals('micro', $band['key']);
    }

    /** @test */
    public function amount_at_micro_ceiling_is_micro(): void
    {
        $rules = $this->makeGovernanceRules();
        $band  = $rules->determineCashBand(50000);
        $this->assertEquals('micro', $band['key']);
    }

    /** @test */
    public function amount_above_micro_ceiling_is_low(): void
    {
        $rules = $this->makeGovernanceRules();
        $band  = $rules->determineCashBand(50001);
        $this->assertEquals('low', $band['key']);
    }

    /** @test */
    public function amount_at_low_ceiling_is_low(): void
    {
        $rules = $this->makeGovernanceRules();
        $band  = $rules->determineCashBand(250000);
        $this->assertEquals('low', $band['key']);
    }

    /** @test */
    public function amount_in_medium_range_is_medium(): void
    {
        $rules = $this->makeGovernanceRules();
        $band  = $rules->determineCashBand(500000);
        $this->assertEquals('medium', $band['key']);
    }

    /** @test */
    public function amount_in_high_range_is_high(): void
    {
        $rules = $this->makeGovernanceRules();
        $band  = $rules->determineCashBand(2000000);
        $this->assertEquals('high', $band['key']);
    }

    /** @test */
    public function amount_above_high_ceiling_is_strategic(): void
    {
        $rules = $this->makeGovernanceRules();
        $band  = $rules->determineCashBand(10000000);
        $this->assertEquals('strategic', $band['key']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // B) getRequiredSourcingMethod()
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function micro_band_requires_spot_buy(): void
    {
        $rules = $this->makeGovernanceRules();
        $this->assertEquals('spot_buy', $rules->getRequiredSourcingMethod(25000));
    }

    /** @test */
    public function low_band_requires_rfq(): void
    {
        $rules = $this->makeGovernanceRules();
        $this->assertEquals('rfq', $rules->getRequiredSourcingMethod(100000));
    }

    /** @test */
    public function medium_band_requires_rfq_formal(): void
    {
        $rules = $this->makeGovernanceRules();
        $this->assertEquals('rfq_formal', $rules->getRequiredSourcingMethod(500000));
    }

    /** @test */
    public function high_band_requires_tender(): void
    {
        $rules = $this->makeGovernanceRules();
        $this->assertEquals('tender', $rules->getRequiredSourcingMethod(2000000));
    }

    /** @test */
    public function strategic_band_requires_tender(): void
    {
        $rules = $this->makeGovernanceRules();
        $this->assertEquals('tender', $rules->getRequiredSourcingMethod(10000000));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // C) getMinimumQuotes()
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function micro_band_requires_one_quote(): void
    {
        $rules = $this->makeGovernanceRules();
        $this->assertEquals(1, $rules->getMinimumQuotes(25000));
    }

    /** @test */
    public function low_band_requires_three_quotes(): void
    {
        $rules = $this->makeGovernanceRules();
        $this->assertEquals(3, $rules->getMinimumQuotes(100000));
    }

    /** @test */
    public function medium_band_requires_three_quotes(): void
    {
        $rules = $this->makeGovernanceRules();
        $this->assertEquals(3, $rules->getMinimumQuotes(500000));
    }

    /** @test */
    public function high_band_has_no_minimum_quotes_requirement(): void
    {
        // Tender — no quote minimum (open tendering replaces quote counting)
        $rules = $this->makeGovernanceRules();
        $this->assertEquals(0, $rules->getMinimumQuotes(2000000));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // D) Approver roles
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function micro_band_approvers_do_not_include_board(): void
    {
        $rules     = $this->makeGovernanceRules();
        $approvers = $rules->getRequiredApprovers(25000);
        $this->assertNotContains('board', $approvers);
    }

    /** @test */
    public function strategic_band_requires_board_approval(): void
    {
        $rules     = $this->makeGovernanceRules();
        $approvers = $rules->getRequiredApprovers(10000000);
        $this->assertContains('board', $approvers);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // E) ProcurementService — RFQ blocked for wrong bands
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function rfq_creation_blocked_for_medium_band_amount(): void
    {
        $service = app(ProcurementService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/rfq_formal|RFQ is not permitted/i');

        $service->createRFQ([
            'title'             => 'Medium-value purchase',
            'description'       => 'Medium-value purchase',
            'budget_allocation' => 500000, // medium band → rfq_formal required
        ]);
    }

    /** @test */
    public function rfq_creation_blocked_for_high_band_amount(): void
    {
        $service = app(ProcurementService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/tender|RFQ is not permitted/i');

        $service->createRFQ([
            'title'             => 'High-value purchase',
            'description'       => 'High-value purchase',
            'budget_allocation' => 2000000,
        ]);
    }

    /** @test */
    public function rfq_creation_allowed_for_micro_band_amount(): void
    {
        $service = app(ProcurementService::class);

        $process = $service->createRFQ([
            'title'             => 'Micro purchase',
            'description'       => 'Micro purchase',
            'budget_allocation' => 25000,
        ]);

        $this->assertNotNull($process->id);
        $this->assertEquals('rfq', $process->type);
    }

    /** @test */
    public function rfq_creation_allowed_for_low_band_amount(): void
    {
        $service = app(ProcurementService::class);

        $process = $service->createRFQ([
            'title'             => 'Low-value purchase',
            'description'       => 'Low-value purchase',
            'budget_allocation' => 100000,
        ]);

        $this->assertNotNull($process->id);
    }

    /** @test */
    public function rfp_creation_blocked_for_tender_band_amount(): void
    {
        $service = app(ProcurementService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/tender|RFP is not permitted/i');

        $service->createRFP([
            'title'             => 'High-value RFP attempt',
            'description'       => 'High-value RFP attempt',
            'budget_allocation' => 2000000, // high band → tender required
        ]);
    }
}
