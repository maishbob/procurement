<?php

namespace App\Modules\Planning\Services;

use App\Modules\Planning\Models\AnnualProcurementPlan;
use App\Modules\Planning\Models\AnnualProcurementPlanItem;
use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Carbon\Carbon;

/**
 * Annual Procurement Plan (APP) Service
 * 
 * Manages creation, approval, and tracking of annual procurement plans
 */
class AnnualProcurementPlanService
{
    /**
     * Get all annual procurement plans
     */
    public function getAll()
    {
        return AnnualProcurementPlan::all();
    }
    /**
     * Reject an annual procurement plan
     */
    public function reject(AnnualProcurementPlan $plan, string $comments = null): AnnualProcurementPlan
    {
        \Log::info('[APP] REJECT called for plan', ['id' => $plan->id, 'status_before' => $plan->status]);
        if ($plan->status !== 'submitted') {
            \Log::info('[APP] REJECT cannot reject in status', ['id' => $plan->id, 'status' => $plan->status]);
            throw new Exception("Plan cannot be rejected in {$plan->status} status");
        }
        return DB::transaction(function () use ($plan, $comments) {
            $plan->status = 'rejected';
            $plan->rejected_by = Auth::id();
            $plan->rejected_at = Carbon::now();
            $plan->rejection_comments = $comments;
            $plan->save();

            // $this->auditService->logCustom(
            //     AnnualProcurementPlan::class,
            //     $plan->id,
            //     'rejected',
            //     [
            //         'rejected_by' => Auth::id(),
            //         'rejected_at' => Carbon::now(),
            //         'comments' => $comments,
            //     ]
            // );
            return $plan->fresh();
        });
    }
    protected AuditService $auditService;
    protected WorkflowEngine $workflowEngine;

    public function __construct(
        AuditService $auditService,
        WorkflowEngine $workflowEngine
    ) {
        $this->auditService = $auditService;
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * Create new annual procurement plan
     */
    public function create(array $data, array $items = []): AnnualProcurementPlan
    {
        try {
            return DB::transaction(function () use ($data, $items) {
                // Generate plan number
                $data['plan_number'] = $this->generatePlanNumber($data['fiscal_year']);
                $data['prepared_by'] = Auth::id();
                $data['prepared_at'] = Carbon::now();
                $data['status'] = 'draft';

                $plan = AnnualProcurementPlan::create($data);

                // Add items if provided
                if (!empty($items)) {
                    foreach ($items as $itemData) {
                        $this->addItem($plan, $itemData);
                    }
                    // Recalculate totals
                    $this->recalculateBudget($plan);
                }

                $this->auditService->logCreate(
                    AnnualProcurementPlan::class,
                    $plan->id,
                    $plan->toArray(),
                    [
                        'module' => 'planning',
                        'fiscal_year' => $plan->fiscal_year,
                        'item_count' => count($items),
                    ]
                );

                return $plan->load('items');
            });
        } catch (\Throwable $e) {
            \Log::error('[APP] Failed to create annual procurement plan', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Add item to procurement plan
     */
    public function addItem(AnnualProcurementPlan $plan, array $data): AnnualProcurementPlanItem
    {
        if (!$plan->isDraft() && !$plan->isActive()) {
            throw new Exception("Cannot add items to plan in {$plan->status} status");
        }

        return DB::transaction(function () use ($plan, $data) {
            $data['annual_procurement_plan_id'] = $plan->id;

            // Calculate estimated total
            if (isset($data['estimated_quantity']) && isset($data['estimated_unit_price'])) {
                $data['estimated_total'] = $data['estimated_quantity'] * $data['estimated_unit_price'];
            }

            // Set budgeted amount if not provided
            if (!isset($data['budgeted_amount']) && isset($data['estimated_total'])) {
                $data['budgeted_amount'] = $data['estimated_total'];
            }

            $item = AnnualProcurementPlanItem::create($data);

            // Update plan totals
            $this->recalculateBudget($plan);

            $this->auditService->logCreate(
                AnnualProcurementPlanItem::class,
                $item->id,
                $item->toArray(),
                [
                    'module' => 'planning',
                    'plan_id' => $plan->id,
                ]
            );

            return $item;
        });
    }

    /**
     * Update plan item
     */
    public function updateItem(AnnualProcurementPlanItem $item, array $data): AnnualProcurementPlanItem
    {
        $plan = $item->plan;

        if (!$plan->isDraft() && !$plan->isActive()) {
            throw new Exception("Cannot update items in plan with {$plan->status} status");
        }

        return DB::transaction(function () use ($item, $data, $plan) {
            $oldData = $item->toArray();

            // Recalculate estimated total if quantity or price changed
            if (isset($data['estimated_quantity']) || isset($data['estimated_unit_price'])) {
                $quantity = $data['estimated_quantity'] ?? $item->estimated_quantity;
                $price = $data['estimated_unit_price'] ?? $item->estimated_unit_price;
                $data['estimated_total'] = $quantity * $price;
            }

            $item->update($data);

            // Update plan totals
            $this->recalculateBudget($plan);

            $this->auditService->logUpdate(
                AnnualProcurementPlanItem::class,
                $item->id,
                $oldData,
                $item->fresh()->toArray(),
                [
                    'module' => 'planning',
                    'plan_id' => $plan->id,
                ]
            );

            return $item->fresh();
        });
    }

    /**
     * Submit plan for review
     */
    public function submit(AnnualProcurementPlan $plan): AnnualProcurementPlan
    {
        \Log::info('[APP] SUBMIT called for plan', ['id' => $plan->id, 'status_before' => $plan->status]);
        if (!$plan->canBeSubmitted()) {
            \Log::info('[APP] SUBMIT cannot submit in status', ['id' => $plan->id, 'status' => $plan->status]);
            throw new Exception("Plan cannot be submitted in {$plan->status} status");
        }

        if ($plan->items()->count() === 0) {
            \Log::info('[APP] SUBMIT cannot submit, no items', ['id' => $plan->id]);
            throw new Exception("Cannot submit plan with no items");
        }

        return DB::transaction(function () use ($plan) {
            $plan->status = 'submitted';
            $plan->save();
            \Log::info('[APP] SUBMIT updated status', ['id' => $plan->id, 'status_after' => $plan->status]);

            // $this->auditService->logCustom(
            //     AnnualProcurementPlan::class,
            //     $plan->id,
            //     'rejected',
            //     [
            //         'rejected_by' => Auth::id(),
            //         'rejected_at' => Carbon::now(),
            //         'rejection_comments' => $comments,
            //     ]
            // );
            // );

            return $plan->fresh();
        });
    }

    /**
     * Review plan (HOD/Principal)
     */
    public function review(AnnualProcurementPlan $plan, string $comments = null): AnnualProcurementPlan
    {
        if (!$plan->canBeReviewed()) {
            throw new Exception("Plan cannot be reviewed in {$plan->status} status");
        }

        return DB::transaction(function () use ($plan, $comments) {
            $plan->status = 'reviewed';
            $plan->reviewed_by = Auth::id();
            $plan->reviewed_at = Carbon::now();
            $plan->review_comments = $comments;
            $plan->save();

            $this->auditService->logApproval(
                AnnualProcurementPlan::class,
                $plan->id,
                'reviewed',
                'reviewer',
                $comments,
                ['reviewer_id' => Auth::id()]
            );

            return $plan->fresh();
        });
    }

    /**
     * Approve plan (Principal/Board)
     */
    public function approve(AnnualProcurementPlan $plan, string $comments = null): AnnualProcurementPlan
    {
        \Log::info('[APP] APPROVE called for plan', ['id' => $plan->id, 'status_before' => $plan->status]);
        if (!$plan->canBeApproved()) {
            \Log::info('[APP] APPROVE cannot approve in status', ['id' => $plan->id, 'status' => $plan->status]);
            throw new Exception("Plan cannot be approved in {$plan->status} status");
        }

        return DB::transaction(function () use ($plan, $comments) {
            $plan->status = 'approved';
            $plan->approved_by = Auth::id();
            $plan->approved_at = Carbon::now();
            $plan->approval_comments = $comments;
            $plan->save();

            // $this->auditService->logApproval(
            //     AnnualProcurementPlan::class,
            //     $plan->id,
            //     'approved',
            //     'approver',
            //     $comments,
            //     ['approver_id' => Auth::id()]
            // );

            return $plan->fresh();
        });
    }

    /**
     * Activate plan for the fiscal year
     */
    public function activate(AnnualProcurementPlan $plan): AnnualProcurementPlan
    {
        if (!$plan->isApproved()) {
            throw new Exception("Only approved plans can be activated");
        }

        return DB::transaction(function () use ($plan) {
            // Deactivate any other active plans for the same fiscal year
            AnnualProcurementPlan::where('fiscal_year', $plan->fiscal_year)
                ->where('status', 'active')
                ->where('id', '!=', $plan->id)
                ->update(['status' => 'closed']);

            $plan->status = 'active';
            $plan->save();

            $this->auditService->logCustom(
                AnnualProcurementPlan::class,
                $plan->id,
                'activated',
                [
                    'activated_by' => Auth::id(),
                    'activated_at' => Carbon::now(),
                    'fiscal_year' => $plan->fiscal_year,
                ]
            );

            return $plan->fresh();
        });
    }

    /**
     * Perform quarterly review
     */
    public function quarterlyReview(AnnualProcurementPlan $plan, string $quarter, array $data): AnnualProcurementPlan
    {
        if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
            throw new Exception("Invalid quarter: {$quarter}");
        }

        return DB::transaction(function () use ($plan, $quarter, $data) {
            $reviewField = strtolower($quarter) . '_reviewed_at';
            $plan->{$reviewField} = Carbon::now();
            $plan->save();

            $this->auditService->logCustom(
                AnnualProcurementPlan::class,
                $plan->id,
                'quarterly_review',
                [
                    'quarter' => $quarter,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => Carbon::now(),
                    'execution_summary' => $plan->getExecutionSummary(),
                    'notes' => $data['notes'] ?? null,
                ]
            );

            return $plan->fresh();
        });
    }

    /**
     * Link plan item to requisition
     */
    public function linkToRequisition(AnnualProcurementPlanItem $item, int $requisitionId): void
    {
        DB::transaction(function () use ($item, $requisitionId) {
            $item->linkToRequisition($requisitionId);

            // Update plan committed amount
            $this->recalculateBudget($item->plan);

            $this->auditService->logCustom(
                AnnualProcurementPlanItem::class,
                $item->id,
                'linked_to_requisition',
                [
                    'requisition_id' => $requisitionId,
                    'linked_by' => Auth::id(),
                ]
            );
        });
    }

    /**
     * Link plan item to purchase order
     */
    public function linkToPurchaseOrder(AnnualProcurementPlanItem $item, int $purchaseOrderId, float $actualCost): void
    {
        DB::transaction(function () use ($item, $purchaseOrderId, $actualCost) {
            $item->linkToPurchaseOrder($purchaseOrderId);
            $item->actual_cost = $actualCost;
            $item->calculateVariance();

            // Update plan amounts
            $this->recalculateBudget($item->plan);

            $this->auditService->logCustom(
                AnnualProcurementPlanItem::class,
                $item->id,
                'linked_to_purchase_order',
                [
                    'purchase_order_id' => $purchaseOrderId,
                    'actual_cost' => $actualCost,
                    'variance_amount' => $item->variance_amount,
                    'variance_percentage' => $item->variance_percentage,
                    'linked_by' => Auth::id(),
                ]
            );
        });
    }

    /**
     * Recalculate plan budget totals
     */
    protected function recalculateBudget(AnnualProcurementPlan $plan): void
    {
        $items = $plan->items;

        $plan->total_budget = $items->sum('budgeted_amount');
        $plan->allocated_amount = $items->sum('estimated_total');
        $plan->spent_amount = $items->sum('actual_cost');
        $plan->committed_amount = $items->whereIn('execution_status', [
            'requisition_created',
            'po_issued'
        ])->sum('budgeted_amount');

        $plan->save();
    }

    /**
     * Generate unique plan number
     */
    protected function generatePlanNumber(string $fiscalYear): string
    {
        $prefix = "APP-" . str_replace('/', '-', $fiscalYear);

        $lastPlan = AnnualProcurementPlan::where('plan_number', 'LIKE', "{$prefix}%")
            ->orderBy('plan_number', 'desc')
            ->first();

        if ($lastPlan) {
            $lastNumber = (int) substr($lastPlan->plan_number, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get active plan for fiscal year
     */
    public function getActivePlanForFiscalYear(string $fiscalYear): ?AnnualProcurementPlan
    {
        return AnnualProcurementPlan::where('fiscal_year', $fiscalYear)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get variance report
     */
    public function getVarianceReport(AnnualProcurementPlan $plan): array
    {
        $items = $plan->items()->whereNotNull('actual_cost')->get();

        return [
            'plan_id' => $plan->id,
            'fiscal_year' => $plan->fiscal_year,
            'total_items_completed' => $items->count(),
            'budgeted_total' => $items->sum('budgeted_amount'),
            'actual_total' => $items->sum('actual_cost'),
            'total_variance' => $items->sum('variance_amount'),
            'favorable_variance' => $items->where('variance_amount', '<', 0)->sum('variance_amount'),
            'unfavorable_variance' => $items->where('variance_amount', '>', 0)->sum('variance_amount'),
            'items_over_budget' => $items->where('variance_amount', '>', 0)->count(),
            'items_under_budget' => $items->where('variance_amount', '<', 0)->count(),
            'items_on_budget' => $items->where('variance_amount', 0)->count(),
        ];
    }
}
