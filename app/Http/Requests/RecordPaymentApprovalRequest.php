<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * RecordApprovalRequest (Payment Approval)
 * 
 * Validates payment approval with segregation of duties.
 * Ensures same person who created cannot approve.
 */
class RecordPaymentApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('payment'));
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',
                Rule::in(['approve', 'reject']),
            ],
            'approval_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'rejection_reason' => [
                'required_if:action,reject',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'You must specify approve or reject.',
            'rejection_reason.required_if' => 'You must provide a reason for rejection.',
        ];
    }

    public function validated()
    {
        $validated = parent::validated();
        $validated['approved_by'] = $this->user()->id;
        $validated['approval_date'] = now();
        return $validated;
    }
}
