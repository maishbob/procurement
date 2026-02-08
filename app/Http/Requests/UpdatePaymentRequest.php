<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('payment'));
    }
    public function rules(): array
    {
        $payment = $this->route('payment');
        if ($payment->status !== 'draft') {
            return [];
        }
        return ['payment_method' => ['required', Rule::in(['bank_transfer', 'mobile_money', 'cheque'])], 'payment_reference' => ['nullable', 'string', 'max:100'], 'cheque_number' => ['required_if:payment_method,cheque', 'string', 'max:50'], 'cheque_date' => ['required_if:payment_method,cheque', 'date'], 'notes' => ['nullable', 'string', 'max:1000'],];
    }
}
