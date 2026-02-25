@extends('layouts.app')

@section('title', 'GRN Discrepancies')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">GRN Discrepancies</h1>
            <p class="mt-1 text-sm text-gray-500">Goods received with quantity or quality discrepancies</p>
        </div>
        <a href="{{ route('grn.index') }}" class="text-sm text-primary-600 hover:underline">&larr; Back to GRN List</a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="From date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="To date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Status</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-500">Filter</button>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">GRN #</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">PO #</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Supplier</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Item</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-900">Expected</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-900">Received</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-900">Variance</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Status</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($discrepancies ?? [] as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-primary-600">
                        <a href="{{ route('grn.show', $item->grn_id ?? 0) }}" class="hover:underline">{{ $item->grn_number ?? 'N/A' }}</a>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $item->po_number ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $item->supplier_name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-900">{{ $item->item_description ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-right text-gray-600">{{ $item->quantity_ordered ?? 0 }}</td>
                    <td class="px-6 py-4 text-right text-gray-600">{{ $item->quantity_received ?? 0 }}</td>
                    <td class="px-6 py-4 text-right font-semibold text-red-600">
                        {{ ($item->quantity_ordered ?? 0) - ($item->quantity_received ?? 0) }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ ($item->discrepancy_status ?? '') === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($item->discrepancy_status ?? 'open') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ isset($item->created_at) ? \Carbon\Carbon::parse($item->created_at)->format('M d, Y') : 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-10 text-center text-gray-400">No discrepancies found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
