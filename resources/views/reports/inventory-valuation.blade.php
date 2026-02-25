@extends('layouts.app')

@section('title', 'Inventory Valuation Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inventory Valuation Report</h1>
            <p class="mt-2 text-gray-600">Stock valuation by store using FIFO or AVCO method</p>
        </div>
        <button onclick="exportReport('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Export Excel</button>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ request()->url() }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <select name="store_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">All Stores</option>
                    @foreach($report['stores'] ?? [] as $store)
                        <option value="{{ $store['id'] }}" @selected(($filters['store_id'] ?? '') == $store['id'])>{{ $store['name'] }}</option>
                    @endforeach
                </select>
                <input type="date" name="valuation_date" value="{{ $filters['valuation_date'] ?? date('Y-m-d') }}" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <div></div>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
            </div>
        </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Inventory Value</div>
            <div class="text-3xl font-bold text-primary-600 mt-2">KES {{ number_format($report['total_value'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">As at {{ $filters['valuation_date'] ?? date('Y-m-d') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Items</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($report['total_items'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Distinct item codes</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Stores</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ number_format(count($report['stores'] ?? [])) }}</div>
            <p class="text-xs text-gray-500 mt-2">Store locations included</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-semibold text-gray-900">Inventory Items</h2></div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Item Code</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Store</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Qty</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Unit Cost (KES)</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total Value (KES)</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Method</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($report['rows'] ?? [] as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900">{{ $row['item_code'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $row['name'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['store'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ number_format($row['qty'] ?? 0) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ number_format($row['unit_cost'] ?? 0, 2) }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($row['total_value'] ?? 0, 0) }}</td>
                    <td class="px-6 py-4 text-sm">
                        @php $method = strtoupper($row['method'] ?? 'AVCO'); @endphp
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $method === 'FIFO' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">{{ $method }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No data found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>function exportReport(f){alert('Exporting as '+f.toUpperCase()+'...');}</script>
@endsection