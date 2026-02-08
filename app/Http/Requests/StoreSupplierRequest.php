<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreSupplierRequest
 * 
 * Validates supplier creation with Kenya-specific validation:
 * - KRA PIN format validation (P + 9 digits + letter)
 * - VAT number validation
 * - Tax compliance certificate requirements
 */
class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Supplier::class);
    }

    public function rules(): array
    {
        return [
            // Basic Information
            'business_name' => ['required', 'string', 'max:255', 'unique:suppliers,business_name'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:50', 'unique:suppliers,registration_number'],
            'kra_pin' => [
                'required',
                'string',
                'max:11',
                'unique:suppliers,kra_pin',
                'regex:/^[P][0-9]{9}[A-Z]$/i',
            ],
            'vat_number' => ['nullable', 'string', 'max:20', 'unique:suppliers,vat_number'],

            // Addresses
            'physical_address' => ['required', 'string', 'max:255'],
            'postal_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],

            // Contact Information
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-\(\)]+$/'],
            'email' => ['required', 'email', 'unique:suppliers,email'],
            'website' => ['nullable', 'url'],
            'contact_person' => ['nullable', 'string', 'max:100'],

            // Bank Details
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_branch' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'max:20'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'bank_swift_code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9]{6,11}$/'],

            // Tax Compliance
            'is_tax_compliant' => ['sometimes', 'boolean'],
            'tax_compliance_cert_number' => ['required_if:is_tax_compliant,true', 'string', 'max:50'],
            'tax_compliance_cert_expiry' => ['required_if:is_tax_compliant,true', 'date', 'after:today'],

            // WHT Configuration
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
            'kra_pin.unique' => 'This KRA PIN is already registered.',
            'phone.regex' => 'Please enter a valid phone number.',
            'email.unique' => 'This email is already registered with another supplier.',
            'bank_swift_code.regex' => 'SWIFT code must be 6-11 uppercase alphanumeric characters.',
            'tax_compliance_cert_expiry.after' => 'Tax compliance certificate has expired.',
        ];
    }

    public function validated()
    {
        $validated = parent::validated();

        // Set status to active by default
        if (!isset($validated['status'])) {
            $validated['status'] = 'active';
        }

        return $validated;
    }
}
