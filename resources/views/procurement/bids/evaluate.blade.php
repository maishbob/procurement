@extends('layouts.app')

@section('title', 'Evaluate Bids')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Evaluate Bids</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $process->reference_number ?? 'N/A' }} &mdash; {{ $process->title ?? '' }}</p>
        </div>
        <a href="{{ route('procurement.rfq.show', $process) }}" class="text-sm text-primary-600 hover:underline">&larr; Back to Process</a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Process summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Budget Allocation</p>
            <p class="mt-1 font-bold text-gray-900">KES {{ number_format($process->budget_allocation ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Bids Received</p>
            <p class="mt-1 font-bold text-gray-900">{{ $process->bids->count() ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Closing Date</p>
            <p class="mt-1 font-bold text-gray-900">{{ $process->closing_date?->format('M d, Y') ?? 'N/A' }}</p>
        </div>
    </div>

    {{-- Bids comparison table --}}
    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 overflow-x-auto">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-700">Bid Comparison</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Supplier</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-900">Bid Amount (KES)</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-900">vs Budget</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-900">Lead Time (days)</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-900">Compliant</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Notes</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($process->bids ?? [] as $bid)
                @php
                    $budget = $process->budget_allocation ?? 0;
                    $savings = $budget > 0 ? (($budget - $bid->bid_amount) / $budget) * 100 : 0;
                @endphp
                <tr class="hover:bg-gray-50 {{ $bid->is_awarded ?? false ? 'bg-green-50' : '' }}">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        {{ $bid->supplier->name ?? 'N/A' }}
                        @if($bid->is_awarded ?? false)
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800 font-semibold">Awarded</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right font-semibold">{{ number_format($bid->bid_amount ?? 0, 2) }}</td>
                    <td class="px-6 py-4 text-right {{ $savings >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $savings >= 0 ? '-' : '+' }}{{ number_format(abs($savings), 1) }}%
                    </td>
                    <td class="px-6 py-4 text-center text-gray-600">{{ $bid->lead_time_days ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-center">
                        @if($bid->is_compliant ?? true)
                            <span class="text-green-600">✓</span>
                        @else
                            <span class="text-red-600">✗</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500 max-w-xs truncate">{{ $bid->notes ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @if(!($bid->is_awarded ?? false))
                        <form method="POST" action="{{ route('procurement.bids.award', [$process, $bid]) }}" onsubmit="return confirm('Award to {{ addslashes($bid->supplier->name ?? 'this supplier') }}?')">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-primary-600 text-white rounded text-xs font-semibold hover:bg-primary-500">
                                Award
                            </button>
                        </form>
                        @else
                            <span class="text-xs text-green-600 font-semibold">Awarded</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400">No bids received yet</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
