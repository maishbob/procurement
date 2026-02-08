@extends('layouts.app')

@section('title', 'Purchase Order #' . $purchaseOrder->po_number)

@section('content')
<div class="space-y-6">
    <!-- Page header with Actions -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">PO-{{ $purchaseOrder->po_number }}</h1>
            <p class="mt-2 text-sm text-gray-700">from {{ $purchaseOrder->supplier->name }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex space-x-3">
            @can('update', $purchaseOrder)
            <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Edit
            </a>
            @endcan
            <a href="{{ route('purchase-orders.index') }}"
                class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                Back to List
            </a>
        </div>
    </div>

    <!-- Status and Key Info -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 lg:grid-cols-4">
        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Status</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
                    @if($purchaseOrder->status === 'draft')
                        bg-gray-100 text-gray-800
                    @elseif($purchaseOrder->status === 'approved')
                        bg-green-100 text-green-800
                    @elseif($purchaseOrder->status === 'cancelled')
                        bg-red-100 text-red-800
                    @elseif($purchaseOrder->status === 'received')
                        bg-primary-100 text-primary-800
                    @endif
                ">
                    {{ ucfirst($purchaseOrder->status) }}
                </span>
            </p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Total Amount</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">KES {{ number_format($purchaseOrder->total_amount, 2) }}</p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">PO Date</p>
            <p class="mt-2 text-lg font-semibold text-gray-900">{{ $purchaseOrder->po_date->format('d M Y') }}</p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Delivery Date</p>
            <p class="mt-2 text-lg font-semibold text-gray-900">{{ $purchaseOrder->delivery_date->format('d M Y') }}</p>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Supplier Details -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Supplier Information</h3>
                </div>
                <div class="px-6 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div><dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->supplier->name }}</dd>
                        </div>
                        <div><dt class="text-sm font-medium text-gray-500">KRA PIN</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->supplier->kra_pin }}</dd>
                        </div>
                        <div><dt class="text-sm font-medium text-gray-500">Contact</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->supplier->primary_contact }}</dd>
                        </div>
                        <div><dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->supplier->email }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Line Items -->
            <div class="rounded-lg bg-white shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Line Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Description</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Qty</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Unit Price</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($purchaseOrder->items as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900">KES {{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">KES {{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Approval Status -->
            <div class="rounded-lg bg-white shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Approvals</h3>
                <div class="space-y-3">
                    @foreach($purchaseOrder->approvals ?? [] as $approval)
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            @if($approval->approved_at)
                            <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            @else
                            <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" />
                            </svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $approval->approver->name }}</p>
                            <p class="text-xs text-gray-500">{{ $approval->approver->roles->first()->name ?? 'Approver' }}</p>
                            @if($approval->approved_at)
                            <p class="text-xs text-green-600 mt-1">Approved {{ $approval->approved_at->diffForHumans() }}</p>
                            @else
                            <p class="text-xs text-gray-500 mt-1">Pending approval</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- GRN Status -->
            <div class="rounded-lg bg-white shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">GRN Status</h3>
                @if($purchaseOrder->goodsReceivedNotes->count() > 0)
                <div class="space-y-2">
                    @foreach($purchaseOrder->goodsReceivedNotes as $grn)
                    <a href="{{ route('grn.show', $grn) }}" class="block p-2 rounded hover:bg-gray-50">
                        <p class="text-sm font-medium text-primary-600">GRN-{{ $grn->id }}</p>
                        <p class="text-xs text-gray-500">{{ $grn->received_date->format('d M Y') }}</p>
                    </a>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-600">No goods received yet</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

