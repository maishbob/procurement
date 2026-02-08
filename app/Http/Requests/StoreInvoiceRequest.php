<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Invoice::class);
    }
    public function rules(): array
    {
        return ['supplier_id' => ['required', 'exists:suppliers,id'], 'grn_id' => ['required', 'exists:grns,id'], 'purchase_order_id' => ['required', 'exists:purchase_orders,id'], 'invoice_number' => ['required', 'string', 'max:50', 'unique:invoices,invoice_number'], 'invoice_date' => ['required', 'date', 'before_or_equal:today'], 'due_date' => ['required', 'date', 'after:invoice_date'], 'total_amount' => ['required', 'numeric', 'min:0.01'], 'notes' => ['nullable', 'string', 'max:1000'], 'etims_reference' => ['nullable', 'string', 'max:100'], 'items' => ['required', 'array', 'min:1'], 'items.*.description' => ['required', 'string', 'max:255'], 'items.*.quantity' => ['required', 'numeric', 'min:0.01'], 'items.*.unit_price' => ['required', 'numeric', 'min:0.01'], 'items.*.amount' => ['required', 'numeric', 'min:0.01'],];
    }
    public function messages(): array
    {
        return ['invoice_number.unique' => 'This invoice number is already registered.', 'due_date.after' => 'Due date must be after invoice date.',];
    }
    public function validated()
    {
        $validated = parent::validated();
        $validated['created_by'] = $this->user()->id;
        $validated['status'] = 'draft';
        return $validated;
    }
}
