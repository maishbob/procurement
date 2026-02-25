@extends('layouts.app')

@section('title', 'WHT Certificates')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">WHT Certificates</h1>
            <p class="mt-1 text-sm text-gray-500">Withholding Tax certificates issued to suppliers</p>
        </div>
        <form method="POST" action="{{ route('payments.wht-bulk-download') }}">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold hover:bg-primary-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Bulk Download
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
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
                <label class="block text-xs text-gray-500 mb-1">Supplier</label>
                <input type="text" name="supplier" value="{{ request('supplier') }}" placeholder="Supplier name..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
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
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Certificate #</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Supplier</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Payment Ref</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-900">WHT Amount (KES)</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">WHT Rate</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Issue Date</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Download</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($payments ?? [] as $payment)
                @if(($payment->wht_amount ?? 0) > 0)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900">WHT-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $payment->supplier->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-primary-600">
                        <a href="{{ route('payments.show', $payment) }}" class="hover:underline">{{ $payment->reference_number }}</a>
                    </td>
                    <td class="px-6 py-4 text-right font-medium">{{ number_format($payment->wht_amount ?? 0, 2) }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $payment->wht_rate ?? 'N/A' }}%</td>
                    <td class="px-6 py-4 text-gray-500">{{ $payment->processed_at?->format('M d, Y') ?? 'N/A' }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('payments.wht-certificate', $payment) }}" class="text-primary-600 hover:underline text-xs font-semibold">
                            Download PDF
                        </a>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400">No WHT certificates found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
