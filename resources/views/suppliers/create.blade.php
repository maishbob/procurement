@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">{{ isset($supplier) ? 'Edit Supplier' : 'New Supplier' }}</h1>
        <p class="mt-1 text-sm text-gray-600">
            {{ isset($supplier) ? 'Update supplier information' : 'Register a new supplier in the system' }}
        </p>
    </div>

    <!-- Forms -->
    <form x-data="supplierForm()" @submit.prevent="loading = true; $el.submit();" 
          method="POST" 
          action="{{ isset($supplier) ? route('suppliers.update', $supplier?->id) : route('suppliers.store') }}"
          class="space-y-6">
        @csrf
        @if(isset($supplier))
            @method('PUT')
        @endif

        <!-- Basic Information Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Business Name -->
                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
                    <input type="text" 
                           name="business_name" 
                           id="business_name"
                           value="{{ old('business_name', $supplier?->business_name ?? '') }}"
                           required 
                           maxlength="255"
                           class="w-full rounded-md border shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('business_name') border-red-500 @else border-gray-300 @enderror">
                    @error('business_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Trading Name -->
                <div>
                    <label for="trading_name" class="block text-sm font-medium text-gray-700 mb-1">Trading Name</label>
                    <input type="text" 
                           name="trading_name" 
                           id="trading_name"
                           value="{{ old('trading_name', $supplier?->trading_name ?? '') }}"
                           maxlength="255"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('trading_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Registration Number -->
                <div>
                    <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-1">Registration Number</label>
                    <input type="text" 
                           name="registration_number" 
                           id="registration_number"
                           value="{{ old('registration_number', $supplier?->registration_number ?? '') }}"
                           maxlength="50"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('registration_number')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- KRA PIN -->
                <div>
                    <label for="kra_pin" class="block text-sm font-medium text-gray-700 mb-1">KRA PIN *</label>
                    <div class="relative">
                        <input type="text" 
                               name="kra_pin" 
                               id="kra_pin"
                               x-model="kraPIN"
                               @input="validateKRAPIN()"
                               value="{{ old('kra_pin', $supplier?->kra_pin ?? '') }}"
                               required
                               placeholder="e.g., P012345678K"
                               maxlength="11"
                               class="w-full pr-10 rounded-md border shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('kra_pin') border-red-500 @else border-gray-300 @enderror">
                        <div class="absolute right-3 top-3">
                            <svg x-show="kraPINValid" class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <svg x-show="kraPIN && !kraPINValid" class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Format: P + 9 digits + letter (e.g., P012345678K)</p>
                    @error('kra_pin')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- VAT Number -->
                <div>
                    <label for="vat_number" class="block text-sm font-medium text-gray-700 mb-1">VAT Number</label>
                    <input type="text" 
                           name="vat_number" 
                           id="vat_number"
                           value="{{ old('vat_number', $supplier?->vat_number ?? '') }}"
                           maxlength="50"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('vat_number')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Physical Address -->
                <div class="sm:col-span-2">
                    <label for="physical_address" class="block text-sm font-medium text-gray-700 mb-1">Physical Address</label>
                    <input type="text" 
                           name="physical_address" 
                           id="physical_address"
                           value="{{ old('physical_address', $supplier?->physical_address ?? '') }}"
                           maxlength="500"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('physical_address')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Postal Address -->
                <div class="sm:col-span-2">
                    <label for="postal_address" class="block text-sm font-medium text-gray-700 mb-1">Postal Address</label>
                    <input type="text" 
                           name="postal_address" 
                           id="postal_address"
                           value="{{ old('postal_address', $supplier?->postal_address ?? '') }}"
                           maxlength="500"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('postal_address')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- City -->
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" 
                           name="city" 
                           id="city"
                           value="{{ old('city', $supplier?->city ?? 'Nairobi') }}"
                           maxlength="100"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('city')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Country -->
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <input type="text" 
                           name="country" 
                           id="country"
                           value="{{ old('country', $supplier?->country ?? 'Kenya') }}"
                           maxlength="100"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('country')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Contact Information Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                    <input type="tel" 
                           name="phone" 
                           id="phone"
                           value="{{ old('phone', $supplier?->phone ?? '') }}"
                           required
                           placeholder="+254712345678"
                           class="w-full rounded-md border shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('phone') border-red-500 @else border-gray-300 @enderror">
                    <p class="text-xs text-gray-500 mt-1">Kenya format: +254712345678 or 0712345678</p>
                    @error('phone')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" 
                           name="email" 
                           id="email"
                           value="{{ old('email', $supplier?->email ?? '') }}"
                           required
                           class="w-full rounded-md border shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('email') border-red-500 @else border-gray-300 @enderror">
                    @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Website -->
                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                    <input type="url" 
                           name="website" 
                           id="website"
                           value="{{ old('website', $supplier?->website ?? '') }}"
                           placeholder="https://example.com"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('website')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contact Person -->
                <div>
                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input type="text" 
                           name="contact_person" 
                           id="contact_person"
                           value="{{ old('contact_person', $supplier?->contact_person ?? '') }}"
                           maxlength="100"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('contact_person')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Bank Details Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Bank Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Bank Name -->
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Bank Name *</label>
                    <input type="text" 
                           name="bank_name" 
                           id="bank_name"
                           value="{{ old('bank_name', $supplier?->bank_name ?? '') }}"
                           required
                           maxlength="100"
                           class="w-full rounded-md border shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('bank_name') border-red-500 @else border-gray-300 @enderror">
                    @error('bank_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Branch -->
                <div>
                    <label for="bank_branch" class="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
                    <input type="text" 
                           name="bank_branch" 
                           id="bank_branch"
                           value="{{ old('bank_branch', $supplier?->bank_branch ?? '') }}"
                           required
                           maxlength="100"
                           class="w-full rounded-md border shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('bank_branch') border-red-500 @else border-gray-300 @enderror">
                    @error('bank_branch')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Account Number -->
                <div>
                    <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">Account Number *</label>
                    <input type="text" 
                           name="bank_account_number" 
                           id="bank_account_number"
                           value="{{ old('bank_account_number', $supplier?->bank_account_number ?? '') }}"
                           required
                           maxlength="50"
                           class="w-full rounded-md border shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('bank_account_number') border-red-500 @else border-gray-300 @enderror">
                    @error('bank_account_number')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Account Name -->
                <div>
                    <label for="bank_account_name" class="block text-sm font-medium text-gray-700 mb-1">Account Name *</label>
                    <input type="text" 
                           name="bank_account_name" 
                           id="bank_account_name"
                           value="{{ old('bank_account_name', $supplier?->bank_account_name ?? '') }}"
                           required
                           maxlength="100"
                           class="w-full rounded-md border shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('bank_account_name') border-red-500 @else border-gray-300 @enderror">
                    @error('bank_account_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- SWIFT Code -->
                <div>
                    <label for="bank_swift_code" class="block text-sm font-medium text-gray-700 mb-1">SWIFT Code (for international transfers)</label>
                    <input type="text" 
                           name="bank_swift_code" 
                           id="bank_swift_code"
                           value="{{ old('bank_swift_code', $supplier?->bank_swift_code ?? '') }}"
                           maxlength="20"
                           placeholder="e.g., KENYKEBBK"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('bank_swift_code')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Tax Compliance Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Tax Compliance</h2>
            <div class="space-y-6">
                <!-- Tax Compliant Checkbox -->
                <div class="flex items-center">
                    <input type="checkbox" 
                           name="is_tax_compliant" 
                           id="is_tax_compliant"
                           x-model="isTaxCompliant"
                           value="1"
                           {{ old('is_tax_compliant', $supplier?->is_tax_compliant ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <label for="is_tax_compliant" class="ml-3 block text-sm font-medium text-gray-700">
                        Supplier is tax compliant
                    </label>
                </div>

                <!-- Tax Compliance Fields (conditional) -->
                <div x-show="isTaxCompliant" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="tax_compliance_cert_number" class="block text-sm font-medium text-gray-700 mb-1">Tax Compliance Certificate Number</label>
                            <input type="text" 
                                   name="tax_compliance_cert_number" 
                                   id="tax_compliance_cert_number"
                                   value="{{ old('tax_compliance_cert_number', $supplier?->tax_compliance_cert_number ?? '') }}"
                                   maxlength="50"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>

                        <div>
                            <label for="tax_compliance_cert_expiry" class="block text-sm font-medium text-gray-700 mb-1">Certificate Expiry Date</label>
                            <input type="date" 
                                   name="tax_compliance_cert_expiry" 
                                   id="tax_compliance_cert_expiry"
                                   value="{{ old('tax_compliance_cert_expiry', $supplier?->tax_compliance_cert_expiry?->format('Y-m-d') ?? '') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>
                </div>

                <!-- WHT Checkbox -->
                <div class="flex items-center">
                    <input type="checkbox" 
                           name="subject_to_wht" 
                           id="subject_to_wht"
                           x-model="subjectToWHT"
                           value="1"
                           {{ old('subject_to_wht', $supplier?->subject_to_wht ?? false) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <label for="subject_to_wht" class="ml-3 block text-sm font-medium text-gray-700">
                        Subject to withholding tax (WHT)
                    </label>
                </div>

                <!-- WHT Type (conditional) -->
                <div x-show="subjectToWHT">
                    <label for="wht_type" class="block text-sm font-medium text-gray-700 mb-1">WHT Type</label>
                    <select name="wht_type" id="wht_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Select WHT Type</option>
                        <option value="services" {{ old('wht_type', $supplier?->wht_type ?? '') === 'services' ? 'selected' : '' }}>Services (5%)</option>
                        <option value="supplies" {{ old('wht_type', $supplier?->wht_type ?? '') === 'supplies' ? 'selected' : '' }}>Supplies (3%)</option>
                        <option value="equipment" {{ old('wht_type', $supplier?->wht_type ?? '') === 'equipment' ? 'selected' : '' }}>Equipment (2%)</option>
                        <option value="other" {{ old('wht_type', $supplier?->wht_type ?? '') === 'other' ? 'selected' : '' }}>Other (5%)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="bg-white rounded-lg shadow p-6 flex justify-between items-center">
            <a href="{{ route('suppliers.index') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                Cancel
            </a>
            <button type="submit" :disabled="loading" class="inline-flex items-center px-6 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors disabled:opacity-50">
                <svg x-show="loading" class="animate-spin h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                </svg>
                {{ isset($supplier) ? 'Update Supplier' : 'Create Supplier' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function supplierForm() {
    return {
        loading: false,
        kraPIN: "{{ old('kra_pin', isset($supplier) ? $supplier?->kra_pin : '') }}",
        kraPINValid: false,
        isTaxCompliant: {{ old('is_tax_compliant', isset($supplier) ? $supplier?->is_tax_compliant : false) ? 'true' : 'false' }},
        subjectToWHT: {{ old('subject_to_wht', isset($supplier) ? $supplier?->subject_to_wht : false) ? 'true' : 'false' }},
        
        validateKRAPIN() {
            // KRA PIN format: P + 9 digits + letter
            const regex = /^[P][0-9]{9}[A-Z]$/i;
            this.kraPINValid = regex.test(this.kraPIN);
        }
    };
}
</script>
@endpush
@endsection

