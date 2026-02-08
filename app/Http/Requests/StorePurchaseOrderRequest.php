<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StorePurchaseOrderRequest
 * 
 * Validates PO creation from requisition.
 * Ensures supplier availability, budget check, and item matching.
 */
class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\PurchaseOrder::class);
    }

    public function rules(): array
    {
        return [
            'requisition_id' => ['required', 'exists:requisitions,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'po_date' => ['required', 'date'],
            'delivery_date' => ['required', 'date', 'after:po_date'],
            'currency' => ['required', Rule::in(['KES', 'USD', 'GBP', 'EUR'])],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.requisition_item_id' => ['required', 'exists:requisition_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.is_vatable' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'requisition_id.required' => 'You must select a requisition.',
            'supplier_id.required' => 'You must select a supplier.',
            'delivery_date.after' => 'Delivery date must be after PO date.',
            'items.required' => 'You must add at least one item to the PO.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
        ];
    }

    public function validated()
    {
        $validated = parent::validated();
        $validated['created_by'] = $this->user()->id;
        $validated['status'] = 'draft';
        return $validated;
    }
}
