<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreRequisitionRequest
 * 
 * Validates and authorizes requisition creation.
 * Enforces business rules: budget availability, single-source justification, emergency validation.
 */
class StoreRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // User must have permission to create requisitions
        return $this->user()->can('create', \App\Models\Requisition::class);
    }

    public function rules(): array
    {
        return [
            // Basic Information
            'department_id' => ['required', 'exists:departments,id'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'currency' => ['required', Rule::in(['KES', 'USD', 'GBP', 'EUR'])],
            'required_by_date' => ['required', 'date', 'after:' . now()->addDays(1)->format('Y-m-d')],

            // Descriptions
            'purpose' => ['required', 'string', 'max:500'],
            'justification' => ['required', 'string', 'max:1000'],

            // Budget
            'budget_line_id' => ['required', 'exists:budget_lines,id'],

            // Flags
            'is_emergency' => ['sometimes', 'boolean'],
            'emergency_justification' => [
                'required_if:is_emergency,true',
                'string',
                'max:500',
            ],
            'is_single_source' => ['sometimes', 'boolean'],
            'single_source_justification' => [
                'required_if:is_single_source,true',
                'string',
                'max:500',
            ],

            // Items
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
            'required_by_date.after' => 'Required by date must be at least 1 day in the future.',
            'items.required' => 'You must add at least one item to the requisition.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
            'items.*.unit_price.min' => 'Unit price must be greater than zero.',
        ];
    }

    public function validated()
    {
        $validated = parent::validated();

        // Set created by user
        $validated['created_by'] = $this->user()->id;

        // Calculate totals for validation
        $total = 0;
        foreach ($validated['items'] as $item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $total += $itemTotal;
        }

        // Check budget availability (delegated to service, but can check here)
        $budgetLine = \App\Models\BudgetLine::find($validated['budget_line_id']);
        if ($budgetLine && $budgetLine->getAvailableBalance() < $total) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'budget_line_id' => 'Insufficient budget available for this requisition.',
            ]);
        }

        return $validated;
    }
}
