<?php

namespace App\Services;

use App\Models\Requisition;
use Illuminate\Pagination\LengthAwarePaginator;

class RequisitionService
{
    /**
     * Get all requisitions with filters
     */
    public function getAllRequisitions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Requisition::query();

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['department_id']) && $filters['department_id']) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['created_by']) && $filters['created_by']) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->where('requisition_number', 'like', '%' . $filters['search'] . '%')
                ->orWhere('description', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new requisition
     */
    public function create(array $data): Requisition
    {
        return Requisition::create($data);
    }

    /**
     * Get a specific requisition
     */
    public function getById(int $id): Requisition
    {
        return Requisition::findOrFail($id);
    }

    /**
     * Update a requisition
     */
    public function update(int $id, array $data): Requisition
    {
        $requisition = $this->getById($id);
        $requisition->update($data);
        return $requisition;
    }

    /**
     * Delete a requisition
     */
    public function delete(int $id): bool
    {
        $requisition = $this->getById($id);
        return $requisition->delete();
    }

    /**
     * Create a new requisition with items
     */
    public function createRequisition(array $data, $user): Requisition
    {
        // Generate unique requisition number
        $data['requisition_number'] = $this->generateRequisitionNumber();

        // Set user fields
        $data['requested_by'] = $user->id;
        $data['created_by'] = $user->id;

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'draft';
        }

        // Use purpose as title if not provided
        if (!isset($data['title']) && isset($data['purpose'])) {
            $data['title'] = $data['purpose'];
        }

        // Handle items data
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Calculate estimated total from items
        $estimatedTotal = 0;
        foreach ($items as $item) {
            $quantity = floatval($item['quantity'] ?? 0);
            $unitPrice = floatval($item['estimated_unit_price'] ?? 0);
            $itemTotal = $quantity * $unitPrice;

            // Add VAT if applicable
            if (isset($item['is_vatable']) && $item['is_vatable']) {
                $itemTotal *= 1.16; // 16% VAT
            }

            $estimatedTotal += $itemTotal;
        }

        $data['estimated_total'] = $estimatedTotal;

        // Create requisition
        $requisition = Requisition::create($data);

        // Create requisition items
        $lineNumber = 1;
        foreach ($items as $item) {
            $quantity = floatval($item['quantity']);
            $unitPrice = floatval($item['estimated_unit_price']);
            $estimatedTotal = $quantity * $unitPrice;

            $requisition->items()->create([
                'line_number' => $lineNumber++,
                'description' => $item['description'],
                'specifications' => $item['specifications'] ?? null,
                'quantity' => $quantity,
                'unit_of_measure' => $item['unit_of_measure'],
                'estimated_unit_price' => $unitPrice,
                'estimated_total_price' => $estimatedTotal,
                'is_vatable' => $item['is_vatable'] ?? true,
                'status' => 'pending',
            ]);
        }

        return $requisition->fresh('items');
    }

    /**
     * Generate unique requisition number
     */
    protected function generateRequisitionNumber(): string
    {
        $prefix = 'REQ';
        $year = date('Y');
        $month = date('m');

        // Get the last requisition number for this month
        $lastRequisition = Requisition::withTrashed()
            ->where('requisition_number', 'like', "$prefix-$year$month-%")
            ->orderBy('requisition_number', 'desc')
            ->first();

        if ($lastRequisition) {
            // Extract the sequence number and increment
            $lastNumber = intval(substr($lastRequisition->requisition_number, -4));
            $sequence = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // First requisition of the month
            $sequence = '0001';
        }

        return "$prefix-$year$month-$sequence";
    }

    /**
     * Update a requisition
     */
    public function updateRequisition(Requisition $requisition, array $data): Requisition
    {
        // Use purpose as title if title not provided
        if (!isset($data['title']) && isset($data['purpose'])) {
            $data['title'] = $data['purpose'];
        }

        // Handle items if provided
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);

            // Calculate estimated total from items
            $estimatedTotal = 0;
            foreach ($items as $item) {
                $quantity = floatval($item['quantity'] ?? 0);
                $unitPrice = floatval($item['estimated_unit_price'] ?? 0);
                $itemTotal = $quantity * $unitPrice;

                // Add VAT if applicable
                if (isset($item['is_vatable']) && $item['is_vatable']) {
                    $itemTotal *= 1.16; // 16% VAT
                }

                $estimatedTotal += $itemTotal;
            }

            $data['estimated_total'] = $estimatedTotal;

            // Update requisition
            $requisition->update($data);

            // Delete existing items and recreate
            $requisition->items()->delete();

            $lineNumber = 1;
            foreach ($items as $item) {
                $quantity = floatval($item['quantity']);
                $unitPrice = floatval($item['estimated_unit_price']);
                $estimatedTotalPrice = $quantity * $unitPrice;

                $requisition->items()->create([
                    'line_number' => $lineNumber++,
                    'description' => $item['description'],
                    'specifications' => $item['specifications'] ?? null,
                    'quantity' => $quantity,
                    'unit_of_measure' => $item['unit_of_measure'],
                    'estimated_unit_price' => $unitPrice,
                    'estimated_total_price' => $estimatedTotalPrice,
                    'is_vatable' => $item['is_vatable'] ?? true,
                    'status' => 'pending',
                ]);
            }

            return $requisition->fresh('items');
        }

        // Just update requisition without items
        $requisition->update($data);
        return $requisition;
    }

    /**
     * Submit a requisition for approval
     */
    public function submitRequisition(Requisition $requisition): Requisition
    {
        $requisition->update(['status' => 'submitted']);
        return $requisition;
    }
}
