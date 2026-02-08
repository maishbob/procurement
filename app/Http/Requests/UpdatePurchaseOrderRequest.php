<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('purchaseOrder'));
    }
    public function rules(): array
    {
        $po = $this->route('purchaseOrder');
        if ($po->status !== 'draft') {
            return [];
        }
        return ['supplier_id' => ['required', 'exists:suppliers,id'], 'delivery_date' => ['required', 'date', 'after:' . now()->toDateString()], 'currency' => ['required', Rule::in(['KES', 'USD', 'GBP', 'EUR'])], 'notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1'], 'items.*.quantity' => ['required', 'numeric', 'min:0.01'], 'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],];
    }
}
