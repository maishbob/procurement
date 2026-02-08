@extends('layouts.app')

@section('title', 'Inventory Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inventory Report</h1>
            <p class="mt-2 text-gray-600">Monitor stock levels, asset register, and aging</p>
        </div>
        <button onclick="exportReport('pdf')" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export PDF
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <select id="store" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Stores</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                @endforeach
            </select>
            <select id="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Status</option>
                <option value="out_of_stock">Out of Stock</option>
                <option value="low_stock">Low Stock</option>
                <option value="adequate">Adequate Stock</option>
                <option value="overstocked">Overstocked</option>
            </select>
            <input type="search" id="search" placeholder="Search item..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <button onclick="applyFilters()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Items</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_items'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Value</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">KES {{ number_format($stats['total_value'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Current valuation</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Low Stock Items</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2">{{ $stats['low_stock'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Out of Stock</div>
            <div class="text-3xl font-bold text-red-600 mt-2">{{ $stats['out_of_stock'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Item Code</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Description</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Store</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Current Stock</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Reorder Level</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Unit Cost</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total Value</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->code }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $item->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $item->store->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ $item->quantity }} {{ $item->unit }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ $item->reorder_level }} {{ $item->unit }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">KES {{ number_format($item->unit_cost, 2) }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">KES {{ number_format($item->quantity * $item->unit_cost, 0) }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($item->quantity == 0)
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Out of Stock</span>
                        @elseif($item->quantity < $item->reorder_level)
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Low Stock</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Adequate</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">No items found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($items->hasPages())
    <div class="flex justify-center">
        {{ $items->links() }}
    </div>
    @endif
</div>

<script>
    function applyFilters() {
        const store = document.getElementById('store').value;
        const status = document.getElementById('status').value;
        const search = document.getElementById('search').value;
        
        const params = new URLSearchParams();
        if (store) params.append('store', store);
        if (status) params.append('status', status);
        if (search) params.append('search', search);
        
        window.location.href = '{{ route("reports.inventory") }}?' + params.toString();
    }

    function exportReport(format) {
        alert(`Exporting as ${format.toUpperCase()}...`);
    }
</script>
@endsection

