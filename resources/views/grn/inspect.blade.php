@extends('layouts.app')

@section('title', 'GRN Inspection')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">GRN Inspection</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $grn->grn_number ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('grn.show', $grn) }}" class="text-sm text-primary-600 hover:underline">&larr; Back to GRN</a>
    </div>

    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Header Info --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Supplier</p>
            <p class="mt-1 font-semibold text-gray-900">{{ $grn->supplier->name ?? 'N/A' }}</p>
        </div>
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Purchase Order</p>
            <p class="mt-1 font-semibold text-gray-900">{{ $grn->purchaseOrder->po_number ?? 'N/A' }}</p>
        </div>
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Delivery Date</p>
            <p class="mt-1 font-semibold text-gray-900">{{ $grn->delivery_date?->format('M d, Y') ?? 'N/A' }}</p>
        </div>
    </div>

    {{-- Inspection Form --}}
    <form method="POST" action="{{ route('grn.accept', $grn) }}">
        @csrf
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">Item Inspection</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-900">Item</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-900">Expected Qty</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-900">Received Qty</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-900">Condition</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-900">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($grn->items ?? [] as $index => $item)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $item->description ?? $item->item->name ?? 'Item ' . ($index + 1) }}</td>
                        <td class="px-6 py-4 text-right text-gray-600">{{ $item->quantity_ordered ?? 0 }}</td>
                        <td class="px-6 py-4 text-right">
                            <input type="number" name="items[{{ $index }}][quantity_received]" value="{{ $item->quantity_received ?? $item->quantity_ordered ?? 0 }}" min="0" class="w-24 rounded border border-gray-300 px-2 py-1 text-sm text-right focus:outline-none focus:ring-1 focus:ring-primary-500">
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                        </td>
                        <td class="px-6 py-4">
                            <select name="items[{{ $index }}][condition]" class="rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                                <option value="good" {{ ($item->condition ?? '') === 'good' ? 'selected' : '' }}>Good</option>
                                <option value="damaged" {{ ($item->condition ?? '') === 'damaged' ? 'selected' : '' }}>Damaged</option>
                                <option value="rejected" {{ ($item->condition ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <input type="text" name="items[{{ $index }}][notes]" value="{{ $item->notes ?? '' }}" placeholder="Optional notes..." class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500">
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">No items on this GRN</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-4 mt-4">
            <button type="submit" name="action" value="accept" class="px-6 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-500">
                Accept Delivery
            </button>
            <button type="submit" name="action" value="partial" class="px-6 py-2 bg-yellow-500 text-white rounded-lg font-semibold hover:bg-yellow-400">
                Partial Accept
            </button>
            <button type="submit" name="action" value="reject" class="px-6 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-500">
                Reject Delivery
            </button>
            <a href="{{ route('grn.show', $grn) }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>
</div>
@endsection
