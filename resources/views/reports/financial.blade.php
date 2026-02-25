@extends('layouts.app')

@section('title', 'Financial Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Financial Report</h1>
            <p class="mt-2 text-gray-600">Overview of revenue, expenditure, and net financial position</p>
        </div>
        <button onclick="exportReport('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Excel
        </button>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ request()->url() }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" placeholder="Date From" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" placeholder="Date To" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <input type="text" name="fiscal_year" value="{{ $filters['fiscal_year'] ?? '' }}" placeholder="Fiscal Year (e.g. 2025/2026)" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
            </div>
        </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Revenue</div>
            <div class="text-3xl font-bold text-green-600 mt-2">KES {{ number_format($report['total_revenue'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Receipts for the period</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Expenditure</div>
            <div class="text-3xl font-bold text-red-600 mt-2">KES {{ number_format($report['total_expenditure'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Payments for the period</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            @php
                $net = $report['net_position'] ?? 0;
                $netClass = $net >= 0 ? 'text-primary-600' : 'text-red-600';
            @endphp
            <div class="text-gray-600 text-sm font-medium">Net Position</div>
            <div class="text-3xl font-bold {{ $netClass }} mt-2">KES {{ number_format($net, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Revenue minus expenditure</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Monthly Payments</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Month</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Payments Count</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total Amount (KES)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($report['payments_by_month'] ?? [] as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['month'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ number_format($row['count'] ?? 0) }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($row['total'] ?? 0, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">No data found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>function exportReport(f){alert('Exporting as '+f.toUpperCase()+'...');}</script>
@endsection