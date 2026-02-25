@extends('layouts.app')

@section('title', 'Procurement Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Procurement Report</h1>
            <p class="mt-2 text-gray-600">Summary of procurement processes, awards, and savings</p>
        </div>
        <button onclick="exportReport('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Export Excel</button>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ request()->url() }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" placeholder="Date From" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" placeholder="Date To" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <select name="process_type" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">All Process Types</option>
                    <option value="RFQ" @selected(($filters['process_type'] ?? '') === 'RFQ')>RFQ</option>
                    <option value="RFP" @selected(($filters['process_type'] ?? '') === 'RFP')>RFP</option>
                    <option value="Tender" @selected(($filters['process_type'] ?? '') === 'Tender')>Tender</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
            </div>
        </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Processes</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($report['total_processes'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">All procurement processes</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Awarded</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ number_format($report['awarded'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Contracts awarded</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Pending</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2">{{ number_format($report['pending'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Awaiting decision</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Average Savings</div>
            <div class="text-3xl font-bold text-primary-600 mt-2">{{ number_format($report['average_savings_pct'] ?? 0, 1) }}%</div>
            <p class="text-xs text-gray-500 mt-2">vs. estimated budget</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-semibold text-gray-900">Procurement Processes</h2></div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Reference</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Supplier</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Amount (KES)</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($report['processes'] ?? [] as $process)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $process['reference'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $process['type'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $process['supplier'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($process['amount'] ?? 0, 0) }}</td>
                    <td class="px-6 py-4 text-sm">
                        @php
                            $status = $process['status'] ?? 'pending';
                            $bc = match(strtolower($status)) {
                                'awarded'   => 'bg-green-100 text-green-800',
                                'pending'   => 'bg-yellow-100 text-yellow-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                default     => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $bc }}">{{ ucfirst($status) }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $process['date'] ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No data found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>function exportReport(f){alert('Exporting as '+f.toUpperCase()+'...');}</script>
@endsection