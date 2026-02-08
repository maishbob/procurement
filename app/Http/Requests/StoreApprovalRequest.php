<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreApprovalRequest
 * 
 * Validates approval/rejection of requisitions at any level.
 * Enforces user's approval authority level.
 */
class StoreApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $requisition = $this->route('requisition');
        return $this->user()->can('approve', $requisition);
    }

    public function rules(): array
    {
        return [
            'required_level' => [
                'required',
                Rule::in(['hod', 'principal', 'board', 'ceo']),
            ],
            'action' => [
                'required',
                Rule::in(['approve', 'reject']),
            ],
            'comments' => [
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
            'required_level.required' => 'You must specify the approval level.',
            'action.required' => 'You must specify approve or reject.',
            'rejection_reason.required_if' => 'You must provide a reason for rejection.',
        ];
    }

    public function validated()
    {
        $validated = parent::validated();
        $validated['approved_by'] = $this->user()->id;
        $validated['status'] = $validated['action'] === 'approve' ? 'approved' : 'rejected';
        return $validated;
    }
}
