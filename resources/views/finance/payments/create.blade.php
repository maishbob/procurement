@extends('layouts.app')

@section('title', 'Process Payment')

@section('content')
<div class="space-y-6">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Process Payment</h1>
            <p class="mt-2 text-sm text-gray-700">Create new payment for verified invoice</p>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('payments.store') }}" method="POST" class="space-y-6 p-6 sm:p-8">
            @csrf

            <!-- Invoice Selection -->
            <div>
                <label for="invoice_id" class="block text-sm font-medium text-gray-700">
                    Select Invoice <span class="text-red-500">*</span>
                </label>
                <select id="invoice_id" name="invoice_id" required @change="updateInvoiceDetails()"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('invoice_id') border-red-500 @enderror">
                    <option value="">Choose an invoice...</option>
                    @foreach($invoices ?? [] as $inv)
                    <option value="{{ $inv->id }}" {{ old('invoice_id') == $inv->id ? 'selected' : '' }}
                        data-gross="{{ $inv->gross_amount }}" data-wht="{{ $inv->wht_amount }}" data-net="{{ $inv->net_amount }}">
                        INV-{{ $inv->invoice_number }} - {{ $inv->supplier->name }} - KES {{ number_format($inv->net_amount, 2) }}
                    </option>
                    @endforeach
                </select>
                @error('invoice_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Supplier Display -->
            <div id="supplier-info" class="hidden">
                <label class="block text-sm font-medium text-gray-700">Supplier</label>
                <input type="text" id="supplier-name" disabled
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-gray-600 sm:text-sm">
            </div>

            <!-- Amount Fields -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Gross Amount</label>
                    <input type="number" id="gross-amount" disabled step="0.01"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-gray-600 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">WHT Amount</label>
                    <input type="number" id="wht-amount" disabled step="0.01"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-gray-600 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Net Amount</label>
                    <input type="number" id="net-amount" name="amount" required step="0.01"
                        class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('amount') border-red-500 @enderror">
                    @error('amount')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Payment Mode -->
            <div>
                <label for="payment_mode" class="block text-sm font-medium text-gray-700">
                    Payment Mode <span class="text-red-500">*</span>
                </label>
                <select id="payment_mode" name="payment_mode" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('payment_mode') border-red-500 @enderror">
                    <option value="">Select payment mode...</option>
                    <option value="bank_transfer" {{ old('payment_mode') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cheque" {{ old('payment_mode') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                    <option value="cash" {{ old('payment_mode') == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="mpesa" {{ old('payment_mode') == 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                </select>
                @error('payment_mode')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Payment Date -->
            <div>
                <label for="payment_date" class="block text-sm font-medium text-gray-700">
                    Payment Date <span class="text-red-500">*</span>
                </label>
                <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('payment_date') border-red-500 @enderror">
                @error('payment_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Reference Number -->
            <div>
                <label for="reference_number" class="block text-sm font-medium text-gray-700">
                    Reference Number <span class="text-red-500">*</span>
                </label>
                <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="e.g., Check #, Transfer ID, etc." required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('reference_number') border-red-500 @enderror">
                @error('reference_number')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Remarks -->
            <div>
                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                <textarea id="remarks" name="remarks" rows="4"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm">{{ old('remarks') }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('payments.index') }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                    Process Payment
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function updateInvoiceDetails() {
    const select = document.getElementById('invoice_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById('supplier-info').classList.remove('hidden');
        document.getElementById('gross-amount').value = option.dataset.gross;
        document.getElementById('wht-amount').value = option.dataset.wht;
        document.getElementById('net-amount').value = option.dataset.net;
    } else {
        document.getElementById('supplier-info').classList.add('hidden');
    }
}
</script>
@endpush
@endsection

