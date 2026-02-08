@extends('layouts.app')

@section('title', 'Edit Payment')

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Edit Payment</h1>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-8">
        <form action="{{ route('payments.update', $payment) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Invoice (Read-only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Invoice</label>
                <input type="text" value="INV-{{ $payment->invoice->invoice_number }}" disabled
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-gray-600">
            </div>

            <!-- Payment Mode -->
            <div>
                <label for="payment_mode" class="block text-sm font-medium text-gray-700">Payment Mode</label>
                <select id="payment_mode" name="payment_mode"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900">
                    <option value="bank_transfer" {{ $payment->payment_mode == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cheque" {{ $payment->payment_mode == 'cheque' ? 'selected' : '' }}>Cheque</option>
                    <option value="cash" {{ $payment->payment_mode == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="mpesa" {{ $payment->payment_mode == 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                </select>
            </div>

            <!-- Reference Number -->
            <div>
                <label for="reference_number" class="block text-sm font-medium text-gray-700">Reference Number</label>
                <input type="text" id="reference_number" name="reference_number" value="{{ $payment->reference_number }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2">
            </div>

            <!-- Remarks -->
            <div>
                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                <textarea id="remarks" name="remarks" rows="4" class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2">{{ $payment->remarks }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('payments.show', $payment) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

