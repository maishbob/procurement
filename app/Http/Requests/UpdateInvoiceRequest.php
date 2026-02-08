<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('invoice'));
    }
    public function rules(): array
    {
        $invoice = $this->route('invoice');
        if ($invoice->status !== 'draft') {
            return [];
        }
        return ['supplier_id' => ['required', 'exists:suppliers,id'], 'invoice_number' => ['required', 'string', 'max:50'], 'invoice_date' => ['required', 'date', 'before_or_equal:today'], 'due_date' => ['required', 'date', 'after:invoice_date'], 'total_amount' => ['required', 'numeric', 'min:0.01'], 'notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1'], 'items.*.description' => ['required', 'string', 'max:255'], 'items.*.quantity' => ['required', 'numeric', 'min:0.01'], 'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],];
    }
}
