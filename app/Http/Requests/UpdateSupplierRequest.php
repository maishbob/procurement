<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('supplier'));
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');

        return [
            'business_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'business_name')->ignore($supplier->id),
            ],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('suppliers', 'registration_number')->ignore($supplier->id),
            ],
            'kra_pin' => [
                'required',
                'string',
                'max:11',
                Rule::unique('suppliers', 'kra_pin')->ignore($supplier->id),
                'regex:/^[P][0-9]{9}[A-Z]$/i',
            ],
            'vat_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('suppliers', 'vat_number')->ignore($supplier->id),
            ],
            'physical_address' => ['required', 'string', 'max:255'],
            'postal_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-\(\)]+$/'],
            'email' => [
                'required',
                'email',
                Rule::unique('suppliers', 'email')->ignore($supplier->id),
            ],
            'website' => ['nullable', 'url'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_branch' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'max:20'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'bank_swift_code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9]{6,11}$/'],
            'is_tax_compliant' => ['sometimes', 'boolean'],
            'tax_compliance_cert_number' => ['required_if:is_tax_compliant,true', 'string', 'max:50'],
            'tax_compliance_cert_expiry' => ['required_if:is_tax_compliant,true', 'date', 'after:today'],
            'subject_to_wht' => ['sometimes', 'boolean'],
            'wht_type' => [
                'required_if:subject_to_wht,true',
                Rule::in(['services', 'supplies', 'equipment', 'other']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'kra_pin.regex' => 'KRA PIN must be in format P + 9 digits + letter (e.g., P012345678A)',
            'phone.regex' => 'Please enter a valid phone number.',
            'bank_swift_code.regex' => 'SWIFT code must be 6-11 uppercase alphanumeric characters.',
        ];
    }
}
