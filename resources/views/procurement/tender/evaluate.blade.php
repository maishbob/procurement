@extends('layouts.app')

@section('title', 'Evaluate Tender')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Evaluate Tender</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $tender->reference_number ?? $process->reference_number ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('procurement.tender.index') }}" class="text-sm text-primary-600 hover:underline">&larr; Back to Tenders</a>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @php $proc = $tender ?? $process ?? null; @endphp

    {{-- Tender summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Budget</p>
            <p class="mt-1 font-bold text-gray-900">KES {{ number_format($proc->budget_allocation ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Submissions</p>
            <p class="mt-1 font-bold text-gray-900">{{ $proc->bids->count() ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Method</p>
            <p class="mt-1 font-bold text-gray-900">{{ ucfirst($proc->procurement_method ?? 'tender') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Closing Date</p>
            <p class="mt-1 font-bold text-gray-900">{{ $proc->closing_date?->format('M d, Y') ?? 'N/A' }}</p>
        </div>
    </div>

    {{-- Evaluation table: Technical + Financial --}}
    <div class="bg-white rounded-lg shadow ring-1 ring-gray-200 overflow-x-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Technical & Financial Evaluation (80/20 split)</h2>
            <span class="text-xs text-gray-400">Technical 80% · Financial 20%</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Bidder</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-900">Bid Amount (KES)</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-900">Technical Score (/80)</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-900">Financial Score (/20)</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-900">Total Score</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Recommendation</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($proc->bids ?? [] as $bid)
                @php
                    $techScore = $bid->technical_score ?? 0;
                    $finScore = $bid->financial_score ?? 0;
                    $total = $techScore + $finScore;
                @endphp
                <tr class="hover:bg-gray-50 {{ $bid->is_awarded ?? false ? 'bg-green-50' : '' }}">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        {{ $bid->supplier->name ?? 'N/A' }}
                        @if($bid->is_awarded ?? false)
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Awarded</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right font-semibold">{{ number_format($bid->bid_amount ?? 0, 2) }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="{{ $techScore >= 60 ? 'text-green-700' : 'text-red-600' }} font-semibold">{{ $techScore }}</span>
                    </td>
                    <td class="px-6 py-4 text-center font-semibold">{{ number_format($finScore, 1) }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $total >= 70 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ number_format($total, 1) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $bid->recommendation ?? ($total >= 70 ? 'Recommended' : 'Not recommended') }}</td>
                    <td class="px-6 py-4">
                        @if(!($bid->is_awarded ?? false))
                        <form method="POST" action="{{ route('procurement.bids.award', [$proc, $bid]) }}" onsubmit="return confirm('Award tender to {{ addslashes($bid->supplier->name ?? 'this bidder') }}?')">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-primary-600 text-white rounded text-xs font-semibold hover:bg-primary-500">Award</button>
                        </form>
                        @else
                            <span class="text-xs text-green-600 font-semibold">Awarded</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400">No tender submissions received</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
