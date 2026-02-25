@extends('layouts.app')

@section('title', 'Inventory Movements Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inventory Movements Report</h1>
            <p class="mt-2 text-gray-600">Stock transactions by store, type, and date range</p>
        </div>
        <button onclick="exportReport('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Export Excel</button>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ request()->url() }}">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <select name="store_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">All Stores</option>
                    @foreach($report['stores'] ?? [] as $store)
                        <option value="{{ $store['id'] }}" @selected(($filters['store_id'] ?? '') == $store['id'])>{{ $store['name'] }}</option>
                    @endforeach
                </select>
                <select name="transaction_type" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">All Types</option>
                    <option value="IN" @selected(($filters['transaction_type'] ?? '') === 'IN')>IN</option>
                    <option value="OUT" @selected(($filters['transaction_type'] ?? '') === 'OUT')>OUT</option>
                    <option value="ADJUST" @selected(($filters['transaction_type'] ?? '') === 'ADJUST')>ADJUST</option>
                </select>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" placeholder="Date From" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" placeholder="Date To" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
            </div>
        </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Transactions</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($report['total_transactions'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">IN + OUT + ADJUST</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Units In</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ number_format($report['total_in_qty'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Units received into stores</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Units Out</div>
            <div class="text-3xl font-bold text-red-600 mt-2">{{ number_format($report['total_out_qty'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Units issued from stores</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-semibold text-gray-900">Movement Transactions</h2></div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Item</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Store</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Qty</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Reference</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">User</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($report['rows'] ?? [] as $row)
                @php
                    $type = strtoupper($row['type'] ?? 'IN');
                    $tc = match($type) {
                        'IN'     => 'bg-green-100 text-green-800',
                        'OUT'    => 'bg-red-100 text-red-800',
                        'ADJUST' => 'bg-blue-100 text-blue-800',
                        default  => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['date'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['item'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['store'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $tc }}">{{ $type }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($row['qty'] ?? 0) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['reference'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['user'] ?? '-' }}</td>
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