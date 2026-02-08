@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Edit Invoice</h1>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-8">
        <form action="{{ route('invoices.update', $invoice) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Invoice Number (read-only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Invoice Number</label>
                <input type="text" value="INV-{{ $invoice->invoice_number }}" disabled
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-gray-600">
            </div>

            <!-- Invoice Date -->
            <div>
                <label for="invoice_date" class="block text-sm font-medium text-gray-700">Invoice Date</label>
                <input type="date" id="invoice_date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2">
            </div>

            <!-- Due Date -->
            <div>
                <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2">
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('invoices.show', $invoice) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

