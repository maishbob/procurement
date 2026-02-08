@extends('layouts.app')

@section('title', 'Create Purchase Order')

@section('content')
<div class="space-y-6">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Create Purchase Order</h1>
            <p class="mt-2 text-sm text-gray-700">Create a new PO from approved requisition</p>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('purchase-orders.store') }}" method="POST" class="space-y-6 p-6 sm:p-8">
            @csrf

            <!-- Requisition Selection -->
            <div>
                <label for="requisition_id" class="block text-sm font-medium text-gray-700">
                    Select Approved Requisition <span class="text-red-500">*</span>
                </label>
                <select id="requisition_id" name="requisition_id" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('requisition_id') border-red-500 @enderror">
                    <option value="">Choose a requisition...</option>
                    @foreach($requisitions ?? [] as $req)
                    <option value="{{ $req->id }}" {{ old('requisition_id') == $req->id ? 'selected' : '' }}>
                        REQ-{{ $req->id }} - {{ $req->description }} (KES {{ number_format($req->total_amount, 2) }})
                    </option>
                    @endforeach
                </select>
                @error('requisition_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Supplier Selection -->
            <div>
                <label for="supplier_id" class="block text-sm font-medium text-gray-700">
                    Select Supplier <span class="text-red-500">*</span>
                </label>
                <select id="supplier_id" name="supplier_id" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('supplier_id') border-red-500 @enderror">
                    <option value="">Choose a supplier...</option>
                    @foreach($suppliers ?? [] as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }} ({{ $supplier->kra_pin }})
                    </option>
                    @endforeach
                </select>
                @error('supplier_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- PO Date -->
            <div>
                <label for="po_date" class="block text-sm font-medium text-gray-700">
                    PO Date <span class="text-red-500">*</span>
                </label>
                <input type="date" id="po_date" name="po_date" value="{{ old('po_date', date('Y-m-d')) }}" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('po_date') border-red-500 @enderror">
                @error('po_date')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Delivery Date -->
            <div>
                <label for="delivery_date" class="block text-sm font-medium text-gray-700">
                    Expected Delivery Date <span class="text-red-500">*</span>
                </label>
                <input type="date" id="delivery_date" name="delivery_date" value="{{ old('delivery_date') }}" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('delivery_date') border-red-500 @enderror">
                @error('delivery_date')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Delivery Location -->
            <div>
                <label for="delivery_location" class="block text-sm font-medium text-gray-700">
                    Delivery Location <span class="text-red-500">*</span>
                </label>
                <input type="text" id="delivery_location" name="delivery_location" value="{{ old('delivery_location') }}" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('delivery_location') border-red-500 @enderror">
                @error('delivery_location')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Additional Notes</label>
                <textarea id="notes" name="notes" rows="4"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm">{{ old('notes') }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('purchase-orders.index') }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                    Create PO
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

