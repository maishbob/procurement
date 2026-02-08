<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin') || $this->user()->hasPermission('manage_budget');
    }
    public function rules(): array
    {
        $budgetLine = $this->route('budgetLine');
        return ['code' => ['required', 'string', 'max:50', Rule::unique('budget_lines', 'code')->ignore($budgetLine->id)], 'name' => ['required', 'string', 'max:255'], 'allocated_amount' => ['required', 'numeric', 'min:0.01'], 'description' => ['nullable', 'string', 'max:1000'],];
    }
}
