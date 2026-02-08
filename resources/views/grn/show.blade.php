@extends('layouts.app')

@section('title', 'Goods Received Note #' . $grn->id)

@section('content')
<div class="space-y-6">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">GRN-{{ $grn->id }}</h1>
            <p class="mt-2 text-sm text-gray-700">{{ $grn->purchaseOrder->supplier->name }} - {{ $grn->received_date->format('d M Y') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex space-x-3">
            <a href="{{ route('grn.index') }}"
                class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                Back to List
            </a>
        </div>
    </div>

    <!-- Status and Key Info -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Status</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium bg-green-100 text-green-800">
                    Received
                </span>
            </p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Items Received</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $grn->items->count() }}</p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Receipt Date</p>
            <p class="mt-2 text-lg font-semibold text-gray-900">{{ $grn->received_date->format('d M Y') }}</p>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Supplier Info -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Supplier Information</h3>
                </div>
                <div class="px-6 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div><dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $grn->purchaseOrder->supplier->name }}</dd>
                        </div>
                        <div><dt class="text-sm font-medium text-gray-500">Contact</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $grn->purchaseOrder->supplier->primary_contact }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Received Items -->
            <div class="rounded-lg bg-white shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Received Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Description</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Qty</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Condition</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($grn->items as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ $item->received_quantity }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @if($item->condition === 'good')
                                            bg-green-100 text-green-800
                                        @elseif($item->condition === 'damaged')
                                            bg-yellow-100 text-yellow-800
                                        @else
                                            bg-red-100 text-red-800
                                        @endif
                                    ">
                                        {{ ucfirst($item->condition) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Details</h3>
                </div>
                <div class="px-6 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div><dt class="text-sm font-medium text-gray-500">Receiving Location</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $grn->receiving_location }}</dd>
                        </div>
                        <div><dt class="text-sm font-medium text-gray-500">Received By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $grn->receivedBy->name }}</dd>
                        </div>
                        @if($grn->remarks)
                        <div class="sm:col-span-2"><dt class="text-sm font-medium text-gray-500">Remarks</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $grn->remarks }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Purchase Order -->
            <div class="rounded-lg bg-white shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Purchase Order</h3>
                <a href="{{ route('purchase-orders.show', $grn->purchaseOrder) }}" class="block p-3 rounded border border-gray-200 hover:bg-gray-50">
                    <p class="text-sm font-medium text-primary-600">PO-{{ $grn->purchaseOrder->po_number }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $grn->purchaseOrder->po_date->format('d M Y') }}</p>
                    <p class="text-xs text-gray-900 font-medium mt-1">{{ $grn->purchaseOrder->supplier->name }}</p>
                </a>
            </div>

            <!-- Invoice Status -->
            @if($invoice = $grn->invoiceMatches->first()?->invoice)
            <div class="rounded-lg bg-white shadow p-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Invoice Match</h3>
                <a href="{{ route('invoices.show', $invoice) }}" class="block p-3 rounded border border-gray-200 hover:bg-gray-50">
                    <p class="text-sm font-medium text-primary-600">INV-{{ $invoice->invoice_number }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $invoice->invoice_date->format('d M Y') }}</p>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

