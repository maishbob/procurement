@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')
<div class="space-y-6">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Edit Purchase Order</h1>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('purchase-orders.update', $purchaseOrder) }}" method="POST" class="space-y-6 p-6 sm:p-8">
            @csrf
            @method('PUT')

            <!-- Supplier -->
            <div>
                <label for="supplier_id" class="block text-sm font-medium text-gray-700">Supplier</label>
                <input type="text" disabled value="{{ $purchaseOrder->supplier->name }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-gray-600 sm:text-sm">
                <p class="mt-1 text-xs text-gray-500">Supplier cannot be changed after creation</p>
            </div>

            <!-- PO Date -->
            <div>
                <label for="po_date" class="block text-sm font-medium text-gray-700">PO Date</label>
                <input type="date" id="po_date" name="po_date" value="{{ $purchaseOrder->po_date->format('Y-m-d') }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('po_date') border-red-500 @enderror">
                @error('po_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Delivery Date -->
            <div>
                <label for="delivery_date" class="block text-sm font-medium text-gray-700">Expected Delivery Date</label>
                <input type="date" id="delivery_date" name="delivery_date" value="{{ $purchaseOrder->delivery_date->format('Y-m-d') }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('delivery_date') border-red-500 @enderror">
                @error('delivery_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Delivery Location -->
            <div>
                <label for="delivery_location" class="block text-sm font-medium text-gray-700">Delivery Location</label>
                <input type="text" id="delivery_location" name="delivery_location" value="{{ $purchaseOrder->delivery_location }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('delivery_location') border-red-500 @enderror">
                @error('delivery_location')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea id="notes" name="notes" rows="4"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm">{{ $purchaseOrder->notes }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('purchase-orders.show', $purchaseOrder) }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

