<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('requisition'));
    }

    public function rules(): array
    {
        $requisition = $this->route('requisition');

        // Only allow editing of draft requisitions
        if ($requisition->status !== 'draft') {
            return [];
        }

        return [
            'department_id' => ['required', 'exists:departments,id'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'currency' => ['required', Rule::in(['KES', 'USD', 'GBP', 'EUR'])],
            'required_by_date' => ['required', 'date', 'after:today'],
            'purpose' => ['required', 'string', 'max:500'],
            'justification' => ['required', 'string', 'max:1000'],
            'budget_line_id' => ['required', 'exists:budget_lines,id'],
            'is_emergency' => ['sometimes', 'boolean'],
            'emergency_justification' => ['required_if:is_emergency,true', 'string', 'max:500'],
            'is_single_source' => ['sometimes', 'boolean'],
            'single_source_justification' => ['required_if:is_single_source,true', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.specifications' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_of_measure' => ['required', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'items.*.is_vatable' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required_by_date.after' => 'Required by date must be in the future.',
            'items.required' => 'You must add at least one item to the requisition.',
        ];
    }
}
