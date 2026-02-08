<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin') || $this->user()->hasPermission('manage_budget');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:budget_lines,code'],
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'fiscal_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'allocated_amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This budget code is already used.',
            'fiscal_year.regex' => 'Fiscal year must be in format YYYY/YYYY (e.g., 2025/2026).',
            'allocated_amount.min' => 'Allocated amount must be greater than zero.',
        ];
    }

    public function validated()
    {
        $validated = parent::validated();
        $validated['committed_amount'] = 0;
        $validated['spent_amount'] = 0;
        return $validated;
    }
}
