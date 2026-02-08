@extends('layouts.app')

@section('title', 'Invoice #' . $invoice->invoice_number)

@section('content')
<div class="space-y-6">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">INV-{{ $invoice->invoice_number }}</h1>
            <p class="mt-2 text-sm text-gray-700">from {{ $invoice->supplier->name }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex space-x-3">
            @can('update', $invoice)
            <a href="{{ route('invoices.edit', $invoice) }}"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Edit
            </a>
            @endcan
            <a href="{{ route('invoices.index') }}"
                class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                Back to List
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Status</p>
            <p class="mt-2 text-lg font-semibold text-gray-900">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
                    @if($invoice->status === 'draft')
                        bg-gray-100 text-gray-800
                    @elseif($invoice->status === 'submitted')
                        bg-primary-100 text-primary-800
                    @elseif($invoice->status === 'verified')
                        bg-green-100 text-green-800
                    @elseif($invoice->status === 'rejected')
                        bg-red-100 text-red-800
                    @endif
                ">{{ ucfirst($invoice->status) }}</span>
            </p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Gross Amount</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">KES {{ number_format($invoice->gross_amount, 2) }}</p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Net Amount</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">KES {{ number_format($invoice->net_amount, 2) }}</p>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Supplier Details -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Supplier Information</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div><dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->supplier->name }}</dd>
                        </div>
                        <div><dt class="text-sm font-medium text-gray-500">KRA PIN</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->supplier->kra_pin }}</dd>
                        </div>
                        <div><dt class="text-sm font-medium text-gray-500">Invoice Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_date->format('d M Y') }}</dd>
                        </div>
                        <div><dt class="text-sm font-medium text-gray-500">Due Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->due_date->format('d M Y') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Line Items -->
            <div class="rounded-lg bg-white shadow overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Invoice Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Description</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Qty</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Unit Price</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900">{{ $item->quantity }}</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900">KES {{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-medium text-gray-900">KES {{ number_format($item->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tax Summary -->
            <div class="rounded-lg bg-white shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Tax Summary</h3>
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600">Subtotal</dt>
                        <dd class="text-sm font-medium text-gray-900">KES {{ number_format($invoice->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600">VAT (16%)</dt>
                        <dd class="text-sm font-medium text-gray-900">KES {{ number_format($invoice->vat_amount ?? 0, 2) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-4">
                        <dt class="text-sm font-medium text-gray-900">Gross Amount</dt>
                        <dd class="text-sm font-semibold text-gray-900">KES {{ number_format($invoice->gross_amount, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600">WHT ({{ $invoice->wht_rate }}%)</dt>
                        <dd class="text-sm font-medium text-gray-900">KES {{ number_format($invoice->wht_amount ?? 0, 2) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-4 bg-primary-50 -mx-6 px-6 py-4">
                        <dt class="text-base font-medium text-gray-900">Net Amount</dt>
                        <dd class="text-base font-semibold text-primary-600">KES {{ number_format($invoice->net_amount, 2) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Three-Way Match Status -->
            <div class="rounded-lg bg-white shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Three-Way Match</h3>
                <div class="space-y-3">
                    @php
                        $poMatches = $invoice->getThreeWayMatchDetails();
                    @endphp
                    <div class="flex items-center">
                        <svg class="h-5 w-5 {{ $poMatches['po_match'] ? 'text-green-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-2 text-sm {{ $poMatches['po_match'] ? 'text-gray-900' : 'text-red-600' }}">PO Match</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 {{ $poMatches['grn_match'] ? 'text-green-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-2 text-sm {{ $poMatches['grn_match'] ? 'text-gray-900' : 'text-red-600' }}">GRN Match</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 {{ $poMatches['price_match'] ? 'text-green-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-2 text-sm {{ $poMatches['price_match'] ? 'text-gray-900' : 'text-red-600' }}">Price Match</span>
                    </div>
                </div>
            </div>

            <!-- Payment Status -->
            @if($payment = $invoice->payment)
            <div class="rounded-lg bg-white shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Status</h3>
                <a href="{{ route('payments.show', $payment) }}" class="block p-3 rounded border border-primary-200 bg-primary-50 hover:bg-primary-100">
                    <p class="text-sm font-medium text-primary-900">Payment Processed</p>
                    <p class="text-xs text-primary-700 mt-1">{{ $payment->payment_date->format('d M Y') }}</p>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

