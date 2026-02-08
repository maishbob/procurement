@extends('layouts.app')

@section('title', 'Receive Goods')

@section('content')
<div class="space-y-6">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Receive Goods</h1>
            <p class="mt-2 text-sm text-gray-700">Record goods receipt against purchase order</p>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('grn.store') }}" method="POST" class="space-y-6 p-6 sm:p-8">
            @csrf

            <!-- Purchase Order Selection -->
            <div>
                <label for="purchase_order_id" class="block text-sm font-medium text-gray-700">
                    Select Purchase Order <span class="text-red-500">*</span>
                </label>
                <select id="purchase_order_id" name="purchase_order_id" required @change="updatePOItems()"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('purchase_order_id') border-red-500 @enderror">
                    <option value="">Choose a PO...</option>
                    @foreach($purchaseOrders ?? [] as $po)
                    <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                        PO-{{ $po->po_number }} ({{ $po->supplier->name }}) - KES {{ number_format($po->total_amount, 2) }}
                    </option>
                    @endforeach
                </select>
                @error('purchase_order_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- GRN Date -->
            <div>
                <label for="grn_date" class="block text-sm font-medium text-gray-700">
                    Receipt Date <span class="text-red-500">*</span>
                </label>
                <input type="date" id="grn_date" name="grn_date" value="{{ old('grn_date', date('Y-m-d')) }}" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('grn_date') border-red-500 @enderror">
                @error('grn_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Received Items -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-4">
                    Received Items <span class="text-red-500">*</span>
                </label>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Item Description</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-900">PO Qty</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-900">Received Qty</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Condition</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="items-list">
                            @if($poItems = old('items'))
                                @foreach($poItems as $index => $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item['description'] }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-900">{{ $item['po_quantity'] }}</td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[{{ $index }}][received_quantity]" value="{{ $item['received_quantity'] }}" min="0"
                                            class="w-full text-right px-2 py-1 border border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-3">
                                        <select name="items[{{ $index }}][condition]"
                                            class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                            <option value="good" {{ $item['condition'] == 'good' ? 'selected' : '' }}>Good</option>
                                            <option value="damaged" {{ $item['condition'] == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                            <option value="rejected" {{ $item['condition'] == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Receiving Location -->
            <div>
                <label for="receiving_location" class="block text-sm font-medium text-gray-700">
                    Receiving Location <span class="text-red-500">*</span>
                </label>
                <input type="text" id="receiving_location" name="receiving_location" value="{{ old('receiving_location') }}" required
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm @error('receiving_location') border-red-500 @enderror">
                @error('receiving_location')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Remarks -->
            <div>
                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                <textarea id="remarks" name="remarks" rows="4"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm">{{ old('remarks') }}</textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('grn.index') }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                    Record Receipt
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function updatePOItems() {
    // Implementation would fetch PO items via AJAX
}
</script>
@endpush
@endsection

