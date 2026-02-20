<?php

namespace App\Observers;

use App\Core\Audit\AuditService;
use App\Modules\GRN\Models\GoodsReceivedNote;

class GRNObserver
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Handle the GoodsReceivedNote "created" event.
     */
    public function created(GoodsReceivedNote $grn): void
    {
        $this->auditService->log(
            'GRN_CREATED',
            'GoodsReceivedNote',
            $grn->id,
            null,
            [
                'status' => 'received',
                'purchase_order_id' => $grn->purchase_order_id,
                'received_date' => $grn->received_date,
                'received_by' => auth()?->id(),
            ],
            "GRN #{$grn->grn_number} created for Purchase Order #{$grn->purchaseOrder->po_number}"
        );
    }

    /**
     * Handle the GoodsReceivedNote "inspection_recorded" event.
     */
    public function inspectionRecorded(GoodsReceivedNote $grn): void
    {
        $this->auditService->log(
            'GRN_INSPECTION_RECORDED',
            'GoodsReceivedNote',
            $grn->id,
            ['status' => ['from' => 'pending_inspection', 'to' => 'inspected']],
            null,
            "Quality inspection recorded for GRN #{$grn->grn_number}",
            [
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
            'GRN_POSTED_TO_INVENTORY',
            'GoodsReceivedNote',
            $grn->id,
            ['status' => ['from' => 'inspected', 'to' => 'posted']],
            null,
            "GRN #{$grn->grn_number} posted to inventory",
            [
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
                'GRN_UPDATED',
                'GoodsReceivedNote',
                $grn->id,
                $changes,
                null,
                "GRN #{$grn->grn_number} updated"
            );
        }
    }

    /**
     * Handle the GoodsReceivedNote "deleted" event.
     */
    public function deleted(GoodsReceivedNote $grn): void
    {
        $this->auditService->log(
            'GRN_DELETED',
            'GoodsReceivedNote',
            $grn->id,
            ['deleted_by' => auth()?->id()],
            null,
            "GRN #{$grn->grn_number} permanently deleted"
        );
    }
}
