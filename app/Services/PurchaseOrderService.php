<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseOrderService
{
    /**
     * Get all purchase orders with filters
     */
    public function getAllPurchaseOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseOrder::query();

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['supplier_id']) && $filters['supplier_id']) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->where('po_number', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new purchase order
     */
    public function create(array $data): PurchaseOrder
    {
        return PurchaseOrder::create($data);
    }

    /**
     * Get a specific purchase order
     */
    public function getById(int $id): PurchaseOrder
    {
        return PurchaseOrder::findOrFail($id);
    }

    /**
     * Update a purchase order
     */
    public function update(int $id, array $data): PurchaseOrder
    {
        $po = $this->getById($id);
        $po->update($data);
        return $po;
    }

    /**
     * Delete a purchase order
     */
    public function delete(int $id): bool
    {
        $po = $this->getById($id);
        return $po->delete();
    }

    /**
     * Create a new purchase order
     */
    public function createPurchaseOrder(array $data, $user): PurchaseOrder
    {
        $data['created_by'] = $user->id;
        $data['status'] = 'draft';
        return PurchaseOrder::create($data);
    }

    /**
     * Update a purchase order
     */
    public function updatePurchaseOrder(PurchaseOrder $po, array $data): PurchaseOrder
    {
        $po->update($data);
        return $po;
    }

    /**
     * Issue a purchase order
     */
    public function issuePurchaseOrder(PurchaseOrder $po, $user): PurchaseOrder
    {
        $po->update(['status' => 'issued', 'issued_by' => $user->id, 'issued_date' => now()]);
        return $po;
    }

    /**
     * Cancel a purchase order
     */
    public function cancelPurchaseOrder(PurchaseOrder $po, string $reason): PurchaseOrder
    {
        $po->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);
        return $po;
    }
}
