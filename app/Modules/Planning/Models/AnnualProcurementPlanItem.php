<?php

namespace App\Modules\Planning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Department;
use App\Models\BudgetLine;
use App\Models\Requisition;
use App\Modules\PurchaseOrders\Models\PurchaseOrder;

class AnnualProcurementPlanItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'annual_procurement_plan_id',
        'item_code',
        'description',
        'category',
        'procurement_method',
        'unit_of_measure',
        'estimated_quantity',
        'estimated_unit_price',
        'estimated_total',
        'budgeted_amount',
        'department_id',
        'budget_line_id',
        'planned_quarter',
        'planned_month',
        'target_approval_date',
        'target_delivery_date',
        'justification',
        'priority',
        'specifications',
        'execution_status',
        'requisition_id',
        'purchase_order_id',
        'actual_cost',
        'variance_amount',
        'variance_percentage',
        'actual_delivery_date',
        'variance_notes',
    ];

    protected $casts = [
        'estimated_quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'budgeted_amount' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'target_approval_date' => 'date',
        'target_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(AnnualProcurementPlan::class, 'annual_procurement_plan_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Status helpers
     */
    public function isPending(): bool
    {
        return $this->execution_status === 'pending';
    }

    public function isInProgress(): bool
    {
        return in_array($this->execution_status, ['in_progress', 'requisition_created', 'po_issued']);
    }

    public function isCompleted(): bool
    {
        return $this->execution_status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->execution_status === 'cancelled';
    }

    /**
     * Calculate variance when actual cost is recorded
     */
    public function calculateVariance(): void
    {
        if ($this->actual_cost && $this->budgeted_amount) {
            $this->variance_amount = $this->actual_cost - $this->budgeted_amount;
            $this->variance_percentage = ($this->variance_amount / $this->budgeted_amount) * 100;
            $this->save();
        }
    }

    /**
     * Check if item is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->target_delivery_date || $this->isCompleted()) {
            return false;
        }
        return now()->gt($this->target_delivery_date);
    }

    /**
     * Get variance status (favorable/unfavorable/on-budget)
     */
    public function getVarianceStatus(): string
    {
        if (!$this->variance_amount) {
            return 'on-budget';
        }
        return $this->variance_amount < 0 ? 'favorable' : 'unfavorable';
    }

    /**
     * Link to requisition
     */
    public function linkToRequisition(int $requisitionId): void
    {
        $this->requisition_id = $requisitionId;
        $this->execution_status = 'requisition_created';
        $this->save();
    }

    /**
     * Link to purchase order
     */
    public function linkToPurchaseOrder(int $purchaseOrderId): void
    {
        $this->purchase_order_id = $purchaseOrderId;
        $this->execution_status = 'po_issued';
        $this->save();
    }
}
