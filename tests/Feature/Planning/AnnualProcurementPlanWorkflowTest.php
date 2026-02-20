<?php

namespace Tests\Feature\Planning;

use App\Models\User;
use App\Modules\Planning\Models\AnnualProcurementPlan;
use App\Modules\Planning\Services\AnnualProcurementPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AnnualProcurementPlanWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('email', 'admin@procurement.local')->first();
        // Ensure user has all permissions for APP workflow
        $this->user->givePermissionTo([
            'manage_annual_procurement_plans',
            'review_annual_procurement_plans',
            'approve_annual_procurement_plans',
        ]);
        $this->service = app(AnnualProcurementPlanService::class);
    }

    public function test_admin_can_create_annual_procurement_plan()
    {
        $response = $this->actingAs($this->user)->post(route('annual-procurement-plans.store'), [
            'fiscal_year' => '2025/2026',
            'description' => 'Test Plan',
            'items' => [
                [
                    'category' => 'ICT',
                    'description' => 'Laptops',
                    'planned_quarter' => 'Q1',
                    'estimated_value' => 100000,
                    'sourcing_method' => 'Open Tender',
                ],
            ],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('annual_procurement_plans', [
            'fiscal_year' => '2025/2026',
            'description' => 'Test Plan',
        ]);
        $plan = AnnualProcurementPlan::where('fiscal_year', '2025/2026')->first();
        $this->assertNotNull($plan);
        $this->assertCount(1, $plan->items);
    }

    public function test_admin_can_submit_and_approve_plan()
    {
        $plan = AnnualProcurementPlan::factory()->create(['status' => 'draft']);
        // Add at least one item using the service to satisfy business logic
        $this->service->addItem($plan, [
            'category' => 'ICT',
            'description' => 'Laptops',
            'planned_quarter' => 'Q1',
            'estimated_quantity' => 1,
            'estimated_unit_price' => 100000,
        ]);
        $this->actingAs($this->user)->post(route('annual-procurement-plans.submit', $plan->id));
        $plan = AnnualProcurementPlan::find($plan->id);
        $this->assertEquals('submitted', $plan->status);
        $this->actingAs($this->user)->post(route('annual-procurement-plans.approve', $plan->id));
        $plan = AnnualProcurementPlan::find($plan->id);
        $this->assertEquals('approved', $plan->status);
    }

    public function test_admin_can_reject_plan()
    {
        $plan = AnnualProcurementPlan::factory()->create(['status' => 'draft']);
        // Add at least one item using the service to satisfy business logic
        $this->service->addItem($plan, [
            'category' => 'ICT',
            'description' => 'Laptops',
            'planned_quarter' => 'Q1',
            'estimated_quantity' => 1,
            'estimated_unit_price' => 100000,
        ]);
        $this->actingAs($this->user)->post(route('annual-procurement-plans.submit', $plan->id));
        $plan = AnnualProcurementPlan::find($plan->id);
        $this->assertEquals('submitted', $plan->status);
        $this->actingAs($this->user)->post(route('annual-procurement-plans.reject', $plan->id));
        $plan = AnnualProcurementPlan::find($plan->id);
        $this->assertEquals('rejected', $plan->status);
    }
}
