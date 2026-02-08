<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGRNRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('grn'));
    }
    public function rules(): array
    {
        $grn = $this->route('grn');
        if ($grn->inspection_status !== 'pending') {
            return [];
        }
        return ['grn_date' => ['required', 'date', 'before_or_equal:today'], 'receiving_location' => ['required', 'string', 'max:255'], 'received_by' => ['required', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1'], 'items.*.quantity_received' => ['required', 'numeric', 'min:0.01'], 'items.*.condition' => ['required', 'in:good,damaged,short'],];
    }
}
