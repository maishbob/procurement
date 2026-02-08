@extends('layouts.app')

@section('title', 'Create RFQ')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Create Request for Quotation (RFQ)</h1>
        <p class="mt-2 text-sm text-gray-700">Create a new RFQ to request price quotes from suppliers.</p>
    </div>

    <form action="{{ route('procurement.rfq.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Process Name -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
            <div class="px-4 py-6 sm:p-8">
                <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    
                    <!-- Requisition (if provided) -->
                    @if($requisition)
                    <input type="hidden" name="requisition_id" value="{{ $requisition->id }}">
                    <div class="sm:col-span-6">
                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Linked to Requisition</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>{{ $requisition->requisition_number }}: {{ $requisition->description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="sm:col-span-6">
                        <label for="requisition_id" class="block text-sm font-medium leading-6 text-gray-900">Link to Requisition (Optional)</label>
                        <input type="hidden" name="requisition_id" value="">
                        <p class="mt-2 text-sm text-gray-500">No requisition linked. This RFQ will be created as a standalone process.</p>
                    </div>
                    @endif

                    <div class="sm:col-span-6">
                        <label for="process_name" class="block text-sm font-medium leading-6 text-gray-900">RFQ Title <span class="text-red-500">*</span></label>
                        <input type="text" name="process_name" id="process_name" required value="{{ old('process_name') }}" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6" placeholder="e.g., Office Supplies - Q1 2026">
                        @error('process_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-6">
                        <label for="description" class="block text-sm font-medium leading-6 text-gray-900">Description <span class="text-red-500">*</span></label>
                        <textarea name="description" id="description" rows="4" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-3">
                        <label for="quote_deadline" class="block text-sm font-medium leading-6 text-gray-900">Quote Submission Deadline <span class="text-red-500">*</span></label>
                        <input type="date" name="quote_deadline" id="quote_deadline" required value="{{ old('quote_deadline') }}" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                        @error('quote_deadline')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium leading-6 text-gray-900">Select Suppliers <span class="text-red-500">*</span></label>
                        <div class="mt-2 space-y-2 max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3">
                            @php
                                $suppliers = \App\Models\Supplier::where('status', 'active')->get();
                            @endphp
                            @forelse($suppliers as $supplier)
                            <div class="flex items-center">
                                <input type="checkbox" name="supplier_ids[]" value="{{ $supplier->id }}" id="supplier_{{ $supplier->id }}" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600">
                                <label for="supplier_{{ $supplier->id }}" class="ml-3 text-sm text-gray-700">
                                    {{ $supplier->name }}
                                </label>
                            </div>
                            @empty
                            <p class="text-sm text-gray-500">No active suppliers found. Please add suppliers first.</p>
                            @endforelse
                        </div>
                        @error('supplier_ids')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="flex items-center justify-end gap-x-6 border-t border-gray-900/10 px-4 py-4 sm:px-8">
                <a href="{{ route('procurement.indexRFQ') }}" class="text-sm font-semibold leading-6 text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">Create RFQ</button>
            </div>
        </div>
    </form>
</div>
@endsection
