<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * RecordInspectionRequest
 * 
 * Validates GRN quality inspection results.
 * Records acceptance/rejection of goods with variance tolerance checking.
 */
class RecordInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inspect', $this->route('grn'));
    }

    public function rules(): array
    {
        return [
            'inspection_status' => [
                'required',
                Rule::in(['passed', 'rejected']),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.grn_item_id' => ['required', 'exists:grn_items,id'],
            'items.*.quality_check_passed' => ['required', 'boolean'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.damage_description' => ['nullable', 'string', 'max:500'],
            'variance_notes' => ['nullable', 'string', 'max:1000'],
            'variance_tolerance_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'inspection_status.required' => 'You must specify final inspection status.',
            'items.required' => 'You must inspect at least one item.',
        ];
    }

    public function validated()
    {
        $validated = parent::validated();
        $validated['inspected_by'] = $this->user()->id;
        $validated['inspection_date'] = now();
        return $validated;
    }
}
