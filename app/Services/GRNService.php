<?php

namespace App\Services;

use App\Core\Audit\AuditService;
use App\Core\Workflow\WorkflowEngine;
use App\Modules\GRN\Models\GoodsReceivedNote;
use App\Modules\GRN\Models\GRNItem;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\PurchaseOrders\Models\PurchaseOrder;
use App\Modules\Quality\Services\CapaService;

class GRNService
{
    public function __construct(
        private AuditService $auditService,
        private WorkflowEngine $workflowEngine,
        private CapaService $capaService
    ) {}

    /**
     * Get all GRNs with filters and pagination
     */
    public function getAllGRNs(array $filters = [], int $perPage = 15)
    {
        $query = GoodsReceivedNote::with(['supplier', 'purchaseOrder']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('grn_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a GRN (Goods Received Note) for a purchase order
     */
    public function createGRN(PurchaseOrder $po, array $data): GoodsReceivedNote
    {
        $grn = GoodsReceivedNote::create([
            'purchase_order_id' => $po->id,
            'grn_number' => $this->generateGRNNumber(),
            'supplier_id' => $po->supplier_id,
            'received_date' => $data['received_date'] ?? now()->date(),
            'received_by' => auth()->id(),
            'status' => 'received',
            'delivery_notes' => $data['delivery_notes'] ?? null,
        ]);

        // Create GRN items from PO items
        foreach ($po->items as $poItem) {
            GRNItem::create([
                'grn_id' => $grn->id,
                'catalog_item_id' => $poItem->catalog_item_id,
                'po_item_id' => $poItem->id,
                'quantity_ordered' => $poItem->quantity,
                'quantity_received' => $data['items'][$poItem->id]['quantity_received'] ?? $poItem->quantity,
                'unit_cost' => $poItem->unit_cost,
                'condition' => $data['items'][$poItem->id]['condition'] ?? 'good',
                'inspection_status' => 'pending',
            ]);
        }

        // Status is set to 'created' by default in the GoodsReceivedNote model
        return $grn->fresh();
    }

    /**
     * Update a pending GRN
     */
    public function updateGRN(GoodsReceivedNote $grn, array $data): GoodsReceivedNote
    {
        if ($grn->status !== 'received' && $grn->status !== 'pending') {
             // 'received' is the initial status set in createGRN
             // Assuming 'pending' might be used elsewhere or in future
        }

        // Update items
        if (isset($data['items'])) {
            foreach ($data['items'] as $itemId => $itemData) {
                $item = $grn->items()->where('id', $itemId)->first(); // Use where id to ensure it belongs to GRN
                 if ($item) {
                    $item->update([
                        'quantity_received' => $itemData['quantity_received'] ?? $item->quantity_received,
                        'quantity_accepted' => $itemData['quantity_accepted'] ?? $item->quantity_accepted,
                        'quantity_rejected' => $itemData['quantity_rejected'] ?? $item->quantity_rejected,
                        'condition' => $itemData['condition'] ?? $item->condition,
                    ]);
                }
            }
        }

        if (isset($data['notes'])) {
            $grn->update(['notes' => $data['notes']]);
        }

        return $grn->fresh();
    }

    /**
     * Record quality inspection for GRN items
     */
    public function recordInspection(GoodsReceivedNote $grn, array $inspectionData): GoodsReceivedNote
    {
        $variantolerance = config('procurement.variance_tolerance', 0.05);
        $passCount = 0;
        $failCount = 0;

        foreach ($inspectionData['items'] as $itemId => $inspection) {
            $grnItem = $grn->items()->find($itemId);

            if ($grnItem) {
                $quantityVariance = abs($grnItem->quantity_received - $grnItem->quantity_ordered) / $grnItem->quantity_ordered;

                $inspectionStatus = 'pass';
                if ($quantityVariance > $variantolerance || !$inspection['quality_pass']) {
                    $inspectionStatus = 'fail';
                    $failCount++;
                } else {
                    $passCount++;
                }

                $grnItem->update([
                    'inspection_status' => $inspectionStatus,
                    'inspection_notes' => $inspection['notes'] ?? null,
                    'inspected_by' => auth()->id(),
                    'inspected_at' => now(),
                ]);
            }
        }

        $grn->update([
            'status' => $failCount > 0 ? 'inspection_failed' : 'inspection_complete',
            'inspected_by' => auth()->id(),
            'inspected_at' => now(),
        ]);


        return $grn->fresh();
    }

    /**
     * Post GRN to inventory (move items from receiving to stock)
     */
    public function postToInventory(GoodsReceivedNote $grn): GoodsReceivedNote
    {
        // Only allow posting if inspection passed
        if ($grn->status !== 'inspection_complete') {
            throw new \Exception('GRN must complete inspection before posting to inventory');
        }

        foreach ($grn->items as $grnItem) {
            if ($grnItem->inspection_status === 'pass') {
                // Find or create inventory item
                $inventoryItem = InventoryItem::firstOrCreate(
                    [
                        'catalog_item_id' => $grnItem->catalog_item_id,
                        'store_id' => $grn->purchaseOrder->to_store_id,
                    ],
                    [
                        'quantity' => 0,
                        'unit_cost' => $grnItem->unit_cost,
                        'reorder_level' => $grnItem->catalogItem->reorder_level ?? 10,
                    ]
                );

                // Add received quantity to inventory
                $inventoryItem->increment('quantity', $grnItem->quantity_received);

                // Record transaction — include tracking fields if captured at receipt
                $inventoryItem->transactions()->create([
                    'type'           => 'in',
                    'quantity'       => $grnItem->quantity_received,
                    'reference_type' => 'GoodsReceivedNote',
                    'reference_id'   => $grn->id,
                    'serial_number'  => $grnItem->serial_number,
                    'batch_number'   => $grnItem->batch_number,
                    'expiry_date'    => $grnItem->expiry_date,
                    'storage_location' => $grnItem->storage_location,
                    'notes'          => "Received from {$grn->supplier->name} via GRN #{$grn->grn_number}",
                ]);
            }
        }

        $grn->update(['status' => 'posted']);

        return $grn->fresh();
    }

    /**
     * Record discrepancies/variances between PO, GRN, and actual received
     */
    public function recordDiscrepancy(GoodsReceivedNote $grn, array $discrepancyData): void
    {
        $discrepancy = $grn->discrepancies()->create([
            'item_id' => $discrepancyData['item_id'],
            'discrepancy_type' => $discrepancyData['type'], // quantity, quality, damage
            'description' => $discrepancyData['description'],
            'quantity_variance' => $discrepancyData['quantity_variance'] ?? 0,
            'reported_by' => auth()->id(),
            'status' => 'open',
        ]);

        $this->auditService->log(
            action: 'GRN_DISCREPANCY_RECORDED',
            model: 'GRNDiscrepancy',
            modelId: $discrepancy->id,
            metadata: [
                'grn_id' => $grn->id,
                'type' => $discrepancyData['type'],
            ]
        );
    }

    /**
     * Resolve discrepancy (could be return, credit note, etc)
     */
    public function resolveDiscrepancy(GoodsReceivedNote $grn, int $discrepancyId, string $resolution): void
    {
        $discrepancy = $grn->discrepancies()->find($discrepancyId);

        if (!$discrepancy) {
            throw new \Exception('Discrepancy not found');
        }

        $discrepancy->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        $this->auditService->log(
            action: 'GRN_DISCREPANCY_RESOLVED',
            model: 'GRNDiscrepancy',
            modelId: $discrepancy->id,
            metadata: [
                'resolution' => $resolution,
            ]
        );
    }

    /**
     * Generate unique GRN number
     */
    private function generateGRNNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $latestGRN = GoodsReceivedNote::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->latest('id')
            ->first();

        $sequence = $latestGRN ? (int)substr($latestGRN->grn_number, -4) + 1 : 1;

        return sprintf('GRN-%d-%s-%04d', $year, $month, $sequence);
    }

    /**
     * Record an inspection failure and auto-trigger a CAPA.
     * Called when GRN items fail quality inspection.
     */
    public function recordInspectionFailure(GoodsReceivedNote $grn, array $data = []): void
    {
        $this->capaService->createFromVariance(
            $grn,
            'GoodsReceivedNote',
            array_merge([
                'title'             => "Inspection failure on GRN #{$grn->grn_number}",
                'problem_statement' => $data['problem_statement'] ?? "Quality inspection failed for GRN #{$grn->grn_number}",
                'proposed_action'   => $data['proposed_action'] ?? 'Investigate root cause and initiate corrective action with supplier.',
                'priority'          => 'high',
                'type'              => 'corrective',
                'source'            => 'non_conformance',
            ], $data)
        );
    }

    /**
     * Get GRN with all related data
     */
    public function getGRNDetails(GoodsReceivedNote $grn): array
    {
        return [
            'grn' => $grn,
            'items' => $grn->items()->with('catalogItem')->get(),
            'discrepancies' => $grn->discrepancies()->get(),
            'po' => $grn->purchaseOrder()->with('requisition')->first(),
            'supplier' => $grn->supplier,
            'inspection_status' => $this->getInspectionStatus($grn),
        ];
    }

    /**
     * Get inspection status summary
     */
    private function getInspectionStatus(GoodsReceivedNote $grn): array
    {
        $items = $grn->items()->get();

        return [
            'total_items' => $items->count(),
            'passed_items' => $items->where('inspection_status', 'pass')->count(),
            'failed_items' => $items->where('inspection_status', 'fail')->count(),
            'pending_items' => $items->where('inspection_status', 'pending')->count(),
        ];
    }
}
