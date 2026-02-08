<?php

namespace App\Services;

use App\Core\Audit\AuditService;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Get inventory items with their stock levels
     */
    public function getInventoryItems(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = InventoryItem::with(['stockLevels' => function($q) use ($filters) {
            if (!empty($filters['store_id'])) {
                $q->where('store_id', $filters['store_id']);
            }
        }]);

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'low') {
                $query->whereHas('stockLevels', function($q) {
                    $q->whereRaw('stock_levels.quantity_on_hand <= inventory_items.reorder_point');
                });
            } elseif ($filters['status'] === 'out') {
                $query->whereHas('stockLevels', function($q) {
                    $q->where('quantity_on_hand', 0);
                });
            } elseif ($filters['status'] === 'overstock') {
                $query->whereHas('stockLevels', function($q) {
                    $q->whereRaw('stock_levels.quantity_on_hand > inventory_items.maximum_stock_level');
                });
            }
        }

        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('item_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Adjust stock quantity for an inventory item
     */
    public function adjustStock(InventoryItem $item, string $reason, int $quantityDelta, string $adjustmentType, ?int $approvedBy = null, $user = null): void
    {
        // Implementation would go here
    }

    /**
     * Issue stock to a department
     */
    public function issueStock(InventoryItem $item, int $quantity, ?int $requisitionId = null, int $approvedBy, $user = null): void
    {
        // Implementation would go here
    }

    /**
     * Transfer stock between stores
     */
    public function transferStock(InventoryItem $item, int $fromStoreId, int $toStoreId, int $quantity, int $approvedBy, $user = null): void
    {
        // Implementation would go here
    }

    /**
     * Get reorder items for a store
     */
    public function getReorderItems(int $storeId): array
    {
        return InventoryItem::whereHas('stockLevels', function($q) use ($storeId) {
            $q->where('store_id', $storeId)
              ->whereRaw('stock_levels.quantity_on_hand <= inventory_items.reorder_point');
        })
        ->with(['stockLevels' => function($q) use ($storeId) {
            $q->where('store_id', $storeId);
        }])
        ->get()
        ->toArray();
    }

    /**
     * Calculate item valuation
     */
    public function calculateItemValuation(InventoryItem $item): array
    {
        $totalValue = $item->stockLevels()->sum('value');
        $totalQty = $item->stockLevels()->sum('quantity_on_hand');
        $avgCost = $totalQty > 0 ? $totalValue / $totalQty : 0;

        return [
            'total_quantity' => $totalQty,
            'total_value' => $totalValue,
            'average_cost' => $avgCost,
        ];
    }

    /**
     * Get reorder suggestion for an item
     */
    public function getReorderSuggestion(InventoryItem $item): array
    {
        $totalStock = $item->getTotalStockOnHand();
        $reorderPoint = $item->reorder_point ?? 0;
        $maxStock = $item->maximum_stock_level ?? 0;

        return [
            'current_stock' => $totalStock,
            'reorder_point' => $reorderPoint,
            'suggested_quantity' => max(0, $maxStock - $totalStock),
            'needs_reorder' => $totalStock <= $reorderPoint,
        ];
    }

    /**
     * Get movements report
     */
    public function getMovements(array $filters = []): array
    {
        $query = StockTransaction::query();

        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('transaction_date')->limit(100)->get()->toArray();
    }
}
