@extends('layouts.app')

@section('title', 'Upload Document — ' . ($supplier->display_name ?? $supplier->business_name))

@section('content')
<div class="px-4 sm:px-6 lg:px-8 max-w-xl">
    <div class="mb-6">
        <a href="{{ route('suppliers.onboarding.checklist', $supplier) }}" class="text-sm text-primary-600 hover:text-primary-800">&larr; Back to Checklist</a>
        <h1 class="mt-2 text-2xl font-semibold text-gray-900">Upload Document</h1>
        <p class="text-sm text-gray-500">{{ $supplier->display_name ?? $supplier->business_name }}</p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-4">
            <ul class="text-sm text-red-700 list-disc pl-4">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('suppliers.onboarding.upload.store', $supplier) }}" enctype="multipart/form-data">
            @csrf
            <div class="space-y-5">
                {{-- Document type --}}
                <div>
                    <label for="document_type" class="block text-sm font-medium text-gray-700">Document Type <span class="text-red-500">*</span></label>
                    <select name="document_type" id="document_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="">Select document type</option>
                        @php
                            $types = [
                                'kra_pin_certificate'        => 'KRA PIN Certificate',
                                'tax_compliance_certificate' => 'Tax Compliance Certificate',
                                'bank_letter'                => 'Bank Confirmation Letter',
                                'business_registration'      => 'Business Registration Certificate',
                                'etims_registration'         => 'eTIMS Registration',
                                'other'                      => 'Other',
                            ];
                        @endphp
                        @foreach($types as $val => $label)
                            <option value="{{ $val }}" {{ (request('type') === $val || old('document_type') === $val) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- File --}}
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">File <span class="text-red-500">*</span></label>
                    <input type="file" name="file" id="file" required accept=".pdf,.jpg,.jpeg,.png"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100" />
                    <p class="mt-1 text-xs text-gray-400">Accepted: PDF, JPG, PNG. Max 5 MB.</p>
                </div>

                {{-- Expiry date --}}
                <div>
                    <label for="expiry_date" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                    <input type="date" name="expiry_date" id="expiry_date" value="{{ old('expiry_date') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                    <p class="text-xs text-gray-400 mt-1">Leave blank if document has no expiry.</p>
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" id="notes" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('suppliers.onboarding.checklist', $supplier) }}"
                   class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                    Upload Document
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
