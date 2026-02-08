@extends('layouts.app')

@section('title', 'Payment #' . $payment->id)

@section('content')
<div class="space-y-6">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Payment-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</h1>
            <p class="mt-2 text-sm text-gray-700">{{ $payment->invoice->supplier->name }} - {{ $payment->payment_date->format('d M Y') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex space-x-3">
            <a href="{{ route('payments.index') }}"
                class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500">
                Back to List
            </a>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Status</p>
            <p class="mt-2">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
                    @if($payment->status === 'created')
                        bg-gray-100 text-gray-800
                    @elseif($payment->status === 'processed')
                        bg-green-100 text-green-800
                    @elseif($payment->status === 'cancelled')
                        bg-red-100 text-red-800
                    @endif
                ">{{ ucfirst($payment->status) }}</span>
            </p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Total Amount</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">KES {{ number_format($payment->amount, 2) }}</p>
        </div>

        <div class="rounded-lg bg-white px-4 py-5 shadow sm:px-6">
            <p class="text-sm font-medium text-gray-500">Payment Date</p>
            <p class="mt-2 text-lg font-semibold text-gray-900">{{ $payment->payment_date->format('d M Y') }}</p>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Invoice Details -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Invoice Details</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Invoice Number</dt>
                            <dd class="mt-1">
                                <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                                    INV-{{ $payment->invoice->invoice_number }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Invoice Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->invoice->invoice_date->format('d M Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->invoice->supplier->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Invoice Amount</dt>
                            <dd class="mt-1 text-sm text-gray-900">KES {{ number_format($payment->invoice->net_amount, 2) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Payment Information</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Payment Mode</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Reference Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->reference_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Gross Amount</dt>
                            <dd class="mt-1 text-sm text-gray-900">KES {{ number_format($payment->invoice->gross_amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">WHT Amount</dt>
                            <dd class="mt-1 text-sm text-gray-900">KES {{ number_format($payment->invoice->wht_amount ?? 0, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Net Amount (Paid)</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">KES {{ number_format($payment->amount, 2) }}</dd>
                        </div>
                        @if($payment->remarks)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Remarks</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->remarks }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Processing Info -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Processing History</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->createdBy->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->created_at->format('d M Y H:i') }}</dd>
                        </div>
                        @if($payment->approvedBy)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Approved By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->approvedBy->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Approved Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->approved_at?->format('d M Y H:i') }}</dd>
                        </div>
                        @endif
                        @if($payment->processedBy)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Processed By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->processedBy->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Processed Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $payment->processed_at?->format('d M Y H:i') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Approvals -->
            <div class="rounded-lg bg-white shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Approvals</h3>
                <div class="space-y-3">
                    @if($payment->approvedBy)
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">{{ $payment->approvedBy->name }}</p>
                            <p class="text-xs text-green-600">Approved {{ $payment->approved_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @else
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Pending Approval</p>
                            <p class="text-xs text-gray-500">Awaiting approval</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

