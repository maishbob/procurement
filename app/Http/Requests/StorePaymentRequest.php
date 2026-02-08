<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Payment::class);
    }
    public function rules(): array
    {
        return ['invoice_ids' => ['required', 'array', 'min:1'], 'invoice_ids.*' => ['exists:invoices,id'], 'payment_method' => ['required', Rule::in(['bank_transfer', 'mobile_money', 'cheque'])], 'payment_reference' => ['nullable', 'string', 'max:100'], 'cheque_number' => ['required_if:payment_method,cheque', 'string', 'max:50'], 'cheque_date' => ['required_if:payment_method,cheque', 'date'], 'bank_details' => ['sometimes', 'array'], 'bank_details.bank_id' => ['sometimes', 'exists:banks,id'], 'bank_details.account_number' => ['sometimes', 'string', 'max:20'], 'notes' => ['nullable', 'string', 'max:1000'], 'wht_calculation' => ['sometimes', 'array'],];
    }
    public function messages(): array
    {
        return ['invoice_ids.required' => 'You must select at least one invoice to pay.', 'cheque_number.required_if' => 'Cheque number is required for cheque payments.', 'cheque_date.required_if' => 'Cheque date is required for cheque payments.',];
    }
    public function validated()
    {
        $validated = parent::validated();
        $validated['created_by'] = $this->user()->id;
        $validated['status'] = 'draft';
        return $validated;
    }
}
