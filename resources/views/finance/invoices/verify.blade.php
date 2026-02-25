@extends('layouts.app')

@section('title', 'Verify Invoice')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Three-Way Match Verification</h1>
            <p class="mt-1 text-sm text-gray-500">Invoice: {{ $invoice->invoice_number ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('finance.invoices.index') }}" class="text-sm text-primary-600 hover:underline">&larr; Back to Invoices</a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Three columns: PO / GRN / Invoice --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- PO --}}
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-blue-600 mb-3">Purchase Order</h2>
            @if($invoice->purchaseOrder ?? false)
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">PO Number</dt><dd class="font-semibold">{{ $invoice->purchaseOrder->po_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Supplier</dt><dd>{{ $invoice->purchaseOrder->supplier->name ?? 'N/A' }}</dd></div>
                    <div class="flex justify-between border-t pt-2"><dt class="text-gray-500">PO Total</dt><dd class="font-bold">KES {{ number_format($invoice->purchaseOrder->total_amount ?? 0, 2) }}</dd></div>
                </dl>
                <div class="mt-4 space-y-1 text-sm">
                    @foreach($invoice->purchaseOrder->items ?? [] as $item)
                    <div class="flex justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-700">{{ $item->description }}</span>
                        <span class="text-gray-900 font-medium">{{ $item->quantity }} × KES {{ number_format($item->unit_price ?? 0, 2) }}</span>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400">No PO linked</p>
            @endif
        </div>

        {{-- GRN --}}
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-green-600 mb-3">Goods Received Note</h2>
            @if($invoice->grn ?? false)
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">GRN Number</dt><dd class="font-semibold">{{ $invoice->grn->grn_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Received Date</dt><dd>{{ $invoice->grn->received_date?->format('M d, Y') ?? 'N/A' }}</dd></div>
                    <div class="flex justify-between border-t pt-2"><dt class="text-gray-500">Accepted</dt><dd class="font-bold">{{ $invoice->grn->acceptance_status ?? 'N/A' }}</dd></div>
                </dl>
                <div class="mt-4 space-y-1 text-sm">
                    @foreach($invoice->grn->items ?? [] as $item)
                    <div class="flex justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-700">{{ $item->description }}</span>
                        <span class="text-gray-900 font-medium">Recv: {{ $item->quantity_received }}</span>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400">No GRN linked</p>
            @endif
        </div>

        {{-- Invoice --}}
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-orange-600 mb-3">Supplier Invoice</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Invoice #</dt><dd class="font-semibold">{{ $invoice->invoice_number ?? 'N/A' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Invoice Date</dt><dd>{{ $invoice->invoice_date?->format('M d, Y') ?? 'N/A' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Due Date</dt><dd>{{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}</dd></div>
                <div class="flex justify-between border-t pt-2"><dt class="text-gray-500">Invoice Total</dt><dd class="font-bold">KES {{ number_format($invoice->total_amount ?? 0, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">VAT</dt><dd>KES {{ number_format($invoice->vat_amount ?? 0, 2) }}</dd></div>
            </dl>
        </div>
    </div>

    {{-- Variance Summary --}}
    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Variance Analysis (2% tolerance)</h2>
        @php
            $poTotal = $invoice->purchaseOrder->total_amount ?? 0;
            $invTotal = $invoice->total_amount ?? 0;
            $variance = $poTotal > 0 ? abs(($invTotal - $poTotal) / $poTotal) * 100 : 0;
            $withinTolerance = $variance <= 2;
        @endphp
        <div class="flex items-center gap-4 mb-4">
            <div class="flex-1 text-center p-3 rounded-lg bg-blue-50">
                <p class="text-xs text-gray-500">PO Amount</p>
                <p class="text-lg font-bold text-blue-700">KES {{ number_format($poTotal, 2) }}</p>
            </div>
            <div class="text-gray-400 text-lg">vs</div>
            <div class="flex-1 text-center p-3 rounded-lg bg-orange-50">
                <p class="text-xs text-gray-500">Invoice Amount</p>
                <p class="text-lg font-bold text-orange-700">KES {{ number_format($invTotal, 2) }}</p>
            </div>
            <div class="flex-1 text-center p-3 rounded-lg {{ $withinTolerance ? 'bg-green-50' : 'bg-red-50' }}">
                <p class="text-xs text-gray-500">Variance</p>
                <p class="text-lg font-bold {{ $withinTolerance ? 'text-green-700' : 'text-red-700' }}">{{ number_format($variance, 2) }}%</p>
                <p class="text-xs {{ $withinTolerance ? 'text-green-600' : 'text-red-600' }}">{{ $withinTolerance ? 'Within tolerance' : 'Exceeds 2% tolerance' }}</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <form method="POST" action="{{ route('finance.invoices.verify', $invoice) }}">
                @csrf
                <input type="hidden" name="result" value="pass">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-500">
                    Pass Verification
                </button>
            </form>
            <form method="POST" action="{{ route('finance.invoices.verify', $invoice) }}">
                @csrf
                <input type="hidden" name="result" value="fail">
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-500">
                    Fail Verification
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
