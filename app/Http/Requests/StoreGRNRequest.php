<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGRNRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\GRN::class);
    }
    public function rules(): array
    {
        return ['purchase_order_id' => ['required', 'exists:purchase_orders,id'], 'grn_date' => ['required', 'date', 'before_or_equal:today'], 'receiving_location' => ['required', 'string', 'max:255'], 'received_by' => ['required', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1'], 'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'], 'items.*.quantity_received' => ['required', 'numeric', 'min:0.01'], 'items.*.condition' => ['required', 'in:good,damaged,short'], 'items.*.notes' => ['nullable', 'string', 'max:500'],];
    }
    public function messages(): array
    {
        return ['grn_date.before_or_equal' => 'GRN date cannot be in the future.', 'items.required' => 'You must receive at least one item.',];
    }
    public function validated()
    {
        $validated = parent::validated();
        $validated['created_by'] = $this->user()->id;
        $validated['inspection_status'] = 'pending';
        return $validated;
    }
}
