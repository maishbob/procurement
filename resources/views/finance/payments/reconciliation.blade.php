@extends('layouts.app')

@section('title', 'Payment Reconciliation')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Payment Reconciliation</h1>
            <p class="mt-1 text-sm text-gray-500">Match payments against invoices and bank statements</p>
        </div>
        <form method="POST" action="{{ route('payments.reconciliation.store') }}">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-500">
                Run Reconciliation
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">All</option>
                    <option value="matched" {{ request('status') === 'matched' ? 'selected' : '' }}>Matched</option>
                    <option value="unmatched" {{ request('status') === 'unmatched' ? 'selected' : '' }}>Unmatched</option>
                    <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-500">Filter</button>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Payment Ref</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Supplier</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Invoice</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-900">Amount (KES)</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Status</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Matched</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($payments ?? [] as $payment)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-primary-600">
                        <a href="{{ route('payments.show', $payment) }}" class="hover:underline">{{ $payment->reference_number }}</a>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $payment->supplier->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-right font-medium">{{ number_format($payment->net_amount_base ?? 0, 2) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $payment->status ?? 'pending')) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($payment->invoice->three_way_match_passed ?? false)
                            <span class="text-green-600 font-semibold">✓ Matched</span>
                        @else
                            <span class="text-red-600 font-semibold">✗ Unmatched</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $payment->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400">No payments found for reconciliation</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
