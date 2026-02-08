@extends('layouts.app')

@section('title', 'Inventory Item - ' . ($item->name ?? 'View'))

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">{{ $item->name ?? 'Inventory Item' }}</h1>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex space-x-3">
            <a href="{{ route('inventory.index') }}"
                class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                Back to List
            </a>
        </div>
    </div>

    <!-- Stock Status -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Current Stock</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $item->current_stock ?? 0 }} {{ $item->unit ?? 'units' }}</p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Reorder Level</p>
            <p class="mt-2 text-lg font-semibold text-gray-900">{{ $item->reorder_level ?? 0 }}</p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Status</p>
            <p class="mt-2">
                @if(($item->current_stock ?? 0) <= ($item->reorder_level ?? 0))
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-red-100 text-red-800">
                        Low Stock
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-green-100 text-green-800">
                        In Stock
                    </span>
                @endif
            </p>
        </div>
    </div>

    <!-- Item Details -->
    <div class="rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-medium text-gray-900">Item Information</h3>
        </div>
        <div class="px-6 py-5">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Item Code</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $item->code ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Category</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $item->category?->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Unit Price</dt>
                    <dd class="mt-1 text-sm text-gray-900">KES {{ number_format($item->unit_price ?? 0, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Unit of Measure</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $item->unit ?? 'units' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $item->supplier?->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Lead Time (Days)</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $item->lead_time_days ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Stock History -->
    <div class="rounded-lg bg-white shadow overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-lg font-medium text-gray-900">Stock Transactions (Last 30 Days)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Quantity</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @if($item->transactions && $item->transactions->count() > 0)
                        @foreach($item->transactions->take(10) as $trans)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $trans->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($trans->type) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900">{{ $trans->quantity }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $trans->reference ?? '-' }}</td>
                        </tr>
                        @endforeach
                    @else
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No transactions found</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

