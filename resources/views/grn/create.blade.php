@extends('layouts.app')

@section('title', 'Receive Goods')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page header -->
    <div class="mb-8 md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Receive Goods</h2>
            <p class="mt-1 text-sm text-gray-500">Record goods receipt against a purchase order</p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <a href="{{ route('grn.index') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Back to List
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl md:col-span-2">
        <form action="{{ route('grn.store') }}" method="POST" class="p-6 md:p-8">
            @csrf

            <!-- SECTION 1: PO SELECTION -->
            <div class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6 mb-8 border-b border-gray-900/10 pb-8">
                <div class="sm:col-span-4">
                    <label for="purchase_order_id" class="block text-sm font-medium leading-6 text-gray-900">Purchase Order <span class="text-red-600">*</span></label>
                    <div class="mt-2">
                        <select id="purchase_order_id" name="purchase_order_id" required @change="updatePOItems()"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:max-w-md sm:text-sm sm:leading-6">
                            <option value="">Select a Purchase Order...</option>
                            @foreach($purchaseOrders ?? [] as $po)
                            <option value="{{ $po->id }}" {{ old('purchase_order_id') == $po->id ? 'selected' : '' }}>
                                PO-{{ $po->po_number }} ({{ $po->supplier->name }}) - KES {{ number_format($po->total_amount, 2) }}
                            </option>
                            @endforeach
                        </select>
                        @error('purchase_order_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label for="grn_date" class="block text-sm font-medium leading-6 text-gray-900">Receipt Date <span class="text-red-600">*</span></label>
                    <div class="mt-2">
                        <input type="date" id="grn_date" name="grn_date" value="{{ old('grn_date', date('Y-m-d')) }}" required
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                        @error('grn_date')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="col-span-full">
                    <label for="receiving_location" class="block text-sm font-medium leading-6 text-gray-900">Receiving Location <span class="text-red-600">*</span></label>
                    <div class="mt-2">
                        <input type="text" id="receiving_location" name="receiving_location" value="{{ old('receiving_location') }}" required placeholder="e.g. Main Warehouse, Loading Bay 2"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                        @error('receiving_location')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="col-span-full">
                    <label for="remarks" class="block text-sm font-medium leading-6 text-gray-900">Remarks</label>
                    <div class="mt-2">
                        <textarea id="remarks" name="remarks" rows="3"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: ITEMS -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold leading-7 text-gray-900">Received Items</h3>
                    <p class="text-sm text-gray-500">Items will load automatically when PO is selected</p>
                </div>
                
                <div class="relative overflow-x-auto ring-1 ring-gray-900/5 sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Item Description</th>
                                <th scope="col" class="px-6 py-3 text-right">Ordered Qty</th>
                                <th scope="col" class="px-6 py-3 text-right w-32">Received</th>
                                <th scope="col" class="px-6 py-3 w-40">Condition</th>
                                <th scope="col" class="px-6 py-3">Tracking</th>
                                <th scope="col" class="px-6 py-3">Storage</th>
                            </tr>
                        </thead>
                        <tbody id="items-list" class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">
                                    Please select a Purchase Order to view items.
                                </td>
                            </tr>
                            @if($poItems = old('items'))
                                @foreach($poItems as $index => $item)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $item['description'] ?? 'Item' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        {{ $item['po_quantity'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="number" name="items[{{ $index }}][quantity_received]" value="{{ $item['received_quantity'] ?? '' }}" min="0" step="any" required
                                            class="block w-24 ml-auto rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 text-right">
                                    </td>
                                    <td class="px-6 py-4">
                                        <select name="items[{{ $index }}][condition]" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                                            <option value="good" {{ ($item['condition'] ?? '') == 'good' ? 'selected' : '' }}>Good</option>
                                            <option value="damaged" {{ ($item['condition'] ?? '') == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                            <option value="expired" {{ ($item['condition'] ?? '') == 'expired' ? 'selected' : '' }}>Expired</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 space-y-2">
                                        <input type="text" name="items[{{ $index }}][serial_number]" value="{{ $item['serial_number'] ?? '' }}" placeholder="Serial No."
                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-xs sm:leading-6">
                                        <input type="text" name="items[{{ $index }}][batch_number]" value="{{ $item['batch_number'] ?? '' }}" placeholder="Batch No."
                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-xs sm:leading-6">
                                        <input type="date" name="items[{{ $index }}][expiry_date]" value="{{ $item['expiry_date'] ?? '' }}"
                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-xs sm:leading-6">
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text" name="items[{{ $index }}][storage_location]" value="{{ $item['storage_location'] ?? '' }}" placeholder="Location"
                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end gap-x-6">
                <a href="{{ route('grn.index') }}" class="text-sm font-semibold leading-6 text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                    Record Receipt
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function updatePOItems() {
    const poId = document.getElementById('purchase_order_id').value;
    const tbody = document.getElementById('items-list');
    
    if (!poId) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Please select a Purchase Order to view items.</td></tr>';
        return;
    }

    // Show loading state
    tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500"><div class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Loading items...</div></td></tr>';

    fetch(`/purchase-orders/${poId}/items`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(items => {
            tbody.innerHTML = '';
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No items found for this PO.</td></tr>';
                return;
            }

            items.forEach((item, index) => {
                const row = document.createElement('tr');
                row.className = 'bg-white border-b hover:bg-gray-50';
                
                row.innerHTML = `
                    <td class="px-6 py-4 font-medium text-gray-900">
                        <div class="flex flex-col">
                            <span>${item.description}</span>
                            <span class="text-xs text-gray-500">SKU: ${item.item_id || 'N/A'}</span>
                        </div>
                        <input type="hidden" name="items[${item.id}][item_id]" value="${item.item_id}">
                        <input type="hidden" name="items[${item.id}][description]" value="${item.description}">
                        <input type="hidden" name="items[${item.id}][po_quantity]" value="${item.po_quantity}">
                    </td>
                    <td class="px-6 py-4 text-right text-gray-600 font-medium">
                        ${item.po_quantity}
                    </td>
                    <td class="px-6 py-4">
                        <input type="number" name="items[${item.id}][quantity_received]" value="${item.po_quantity}" min="0" step="any" required
                            class="block w-24 ml-auto rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 text-right">
                    </td>
                    <td class="px-6 py-4">
                        <select name="items[${item.id}][condition]" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                            <option value="good" selected>Good</option>
                            <option value="damaged">Damaged</option>
                            <option value="expired">Expired</option>
                        </select>
                    </td>
                    <td class="px-6 py-4 space-y-2">
                        <input type="text" name="items[${item.id}][serial_number]" placeholder="Serial No."
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-xs sm:leading-6">
                        <input type="text" name="items[${item.id}][batch_number]" placeholder="Batch No."
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-xs sm:leading-6">
                        <input type="date" name="items[${item.id}][expiry_date]"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-xs sm:leading-6">
                    </td>
                    <td class="px-6 py-4">
                        <input type="text" name="items[${item.id}][storage_location]" placeholder="Location"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6">
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching PO items:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-red-600">Failed to load items. Please try again.</td></tr>';
        });
}

// Trigger update if PO is already selected (e.g. on validation error)
document.addEventListener('DOMContentLoaded', function() {
    const poSelect = document.getElementById('purchase_order_id');
    // Only auto-trigger if content is empty (to avoid overwriting old input if page reloads)
    // However, if we have old data, the server-side loop handles it.
    // The client-side auto-load is only needed if there are NO old items but a PO is selected (edge case).
    if (poSelect.value && document.getElementById('items-list').querySelectorAll('tr').length <= 1) {
        // Check if the only row is the placeholder
        const rows = document.getElementById('items-list').querySelectorAll('tr');
        if(rows.length === 1 && rows[0].innerText.includes('Please select')) {
            updatePOItems();
        }
    }
});
</script>
@endpush
@endsection


