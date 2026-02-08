@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Create Invoice</h1>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-8">
        <form action="{{ route('invoices.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Supplier Selection -->
            <div>
                <label for="supplier_id" class="block text-sm font-medium text-gray-700">Select Supplier</label>
                <select id="supplier_id" name="supplier_id" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">Choose...</option>
                    @foreach($suppliers ?? [] as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Invoice Number -->
            <div>
                <label for="invoice_number" class="block text-sm font-medium text-gray-700">Invoice Number</label>
                <input type="text" id="invoice_number" name="invoice_number" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2">
            </div>

            <!-- Invoice Date -->
            <div>
                <label for="invoice_date" class="block text-sm font-medium text-gray-700">Invoice Date</label>
                <input type="date" id="invoice_date" name="invoice_date" value="{{ date('Y-m-d') }}" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2">
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('invoices.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">Create Invoice</button>
            </div>
        </form>
    </div>
</div>
@endsection

