<?php

namespace App\Observers;

use App\Core\Audit\AuditService;
use App\Models\GoodsReceivedNote;

class GRNObserver
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Handle the GoodsReceivedNote "created" event.
     */
    public function created(GoodsReceivedNote $grn): void
    {
        $this->auditService->log(
            action: 'GRN_CREATED',
            status: 'success',
            model_type: 'GoodsReceivedNote',
            model_id: $grn->id,
            description: "GRN #{$grn->grn_number} created for Purchase Order #{$grn->purchaseOrder->po_number}",
            changes: [
                'status' => 'received',
                'purchase_order_id' => $grn->purchase_order_id,
                'received_date' => $grn->received_date,
                'received_by' => auth()?->id(),
            ]
        );
    }

    /**
     * Handle the GoodsReceivedNote "inspection_recorded" event.
     */
    public function inspectionRecorded(GoodsReceivedNote $grn): void
    {
        $this->auditService->log(
            action: 'GRN_INSPECTION_RECORDED',
            status: 'success',
            model_type: 'GoodsReceivedNote',
            model_id: $grn->id,
            description: "Quality inspection recorded for GRN #{$grn->grn_number}",
            changes: ['status' => ['from' => 'pending_inspection', 'to' => 'inspected']],
            metadata: [
                'inspected_by' => auth()?->id(),
                'inspection_date' => now(),
                'items_passed' => $grn->items()->where('inspection_status', 'pass')->count(),
                'items_failed' => $grn->items()->where('inspection_status', 'fail')->count(),
            ]
        );
    }

    /**
     * Handle the GoodsReceivedNote "posted_to_inventory" event.
     */
    public function postedToInventory(GoodsReceivedNote $grn): void
    {
        $this->auditService->log(
            action: 'GRN_POSTED_TO_INVENTORY',
            status: 'success',
            model_type: 'GoodsReceivedNote',
            model_id: $grn->id,
            description: "GRN #{$grn->grn_number} posted to inventory",
            changes: ['status' => ['from' => 'inspected', 'to' => 'posted']],
            metadata: [
                'posted_by' => auth()?->id(),
                'posted_date' => now(),
                'items_count' => $grn->items()->count(),
            ]
        );
    }

    /**
     * Handle the GoodsReceivedNote "updated" event.
     */
    public function updated(GoodsReceivedNote $grn): void
    {
        $changes = [];
        foreach ($grn->getChanges() as $key => $value) {
            if ($key !== 'updated_at') {
                $changes[$key] = [
                    'from' => $grn->getOriginal($key),
                    'to' => $value,
                ];
            }
        }

        if (!empty($changes)) {
            $this->auditService->log(
                action: 'GRN_UPDATED',
                status: 'success',
                model_type: 'GoodsReceivedNote',
                model_id: $grn->id,
                description: "GRN #{$grn->grn_number} updated",
                changes: $changes
            );
        }
    }

    /**
     * Handle the GoodsReceivedNote "deleted" event.
     */
    public function deleted(GoodsReceivedNote $grn): void
    {
        $this->auditService->log(
            action: 'GRN_DELETED',
            status: 'success',
            model_type: 'GoodsReceivedNote',
            model_id: $grn->id,
            description: "GRN #{$grn->grn_number} permanently deleted",
            changes: ['deleted_by' => auth()?->id()]
        );
    }
}
