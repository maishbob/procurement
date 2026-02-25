@extends('layouts.app')

@section('title', 'Approve Payment')

@section('content')
<div class="space-y-6" x-data="{ rejectOpen: false }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Approve Payment</h1>
            <p class="mt-1 text-sm text-gray-500">Review and approve or reject this payment request</p>
        </div>
        <a href="{{ route('payments.index') }}" class="text-sm text-primary-600 hover:underline">&larr; Back to Payments</a>
    </div>

    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Payment Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Payment Details</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Reference</dt><dd class="font-semibold">{{ $payment->reference_number ?? 'N/A' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Supplier</dt><dd class="font-semibold">{{ $payment->supplier->name ?? 'N/A' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Invoice Ref</dt><dd class="font-semibold">{{ $payment->invoice->invoice_number ?? 'N/A' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">PO Reference</dt><dd class="font-semibold">{{ $payment->purchaseOrder->po_number ?? 'N/A' }}</dd></div>
                <div class="flex justify-between border-t pt-3"><dt class="text-gray-500">Gross Amount</dt><dd class="font-semibold">KES {{ number_format($payment->gross_amount ?? 0, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">WHT Deducted</dt><dd class="font-semibold text-orange-600">KES {{ number_format($payment->wht_amount ?? 0, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Net Payable</dt><dd class="font-bold text-lg text-gray-900">KES {{ number_format($payment->net_amount_base ?? 0, 2) }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Three-Way Match Status</h2>
            @php $matched = $payment->invoice->three_way_match_passed ?? false; @endphp
            <div class="flex items-center gap-3 mb-4 p-3 rounded-lg {{ $matched ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                <svg class="w-6 h-6 {{ $matched ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($matched)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    @endif
                </svg>
                <span class="font-semibold {{ $matched ? 'text-green-700' : 'text-red-700' }}">
                    {{ $matched ? 'Three-Way Match Passed' : 'Three-Way Match Failed' }}
                </span>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">PO Matched</dt><dd>{{ ($payment->purchaseOrder ?? false) ? '✓' : '✗' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">GRN Matched</dt><dd>{{ ($payment->invoice->grn_id ?? false) ? '✓' : '✗' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Invoice Matched</dt><dd>{{ ($payment->invoice ?? false) ? '✓' : '✗' }}</dd></div>
            </dl>
        </div>
    </div>

    {{-- Actions --}}
    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Decision</h2>
        <div class="flex items-center gap-4">
            <form method="POST" action="{{ route('payments.approve', $payment) }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-6 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-500">
                    Approve Payment
                </button>
            </form>
            <button type="button" @click="rejectOpen = !rejectOpen" class="inline-flex items-center px-6 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-500">
                Reject
            </button>
        </div>

        <div x-show="rejectOpen" x-cloak class="mt-4">
            <form method="POST" action="{{ route('payments.reject', $payment) }}">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
                <textarea name="rejection_reason" rows="3" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="State the reason for rejection..."></textarea>
                <button type="submit" class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-500">Confirm Rejection</button>
            </form>
        </div>
    </div>
</div>
@endsection
