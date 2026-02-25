@extends('layouts.app')

@section('title', 'Withholding Tax Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Withholding Tax Report</h1>
            <p class="mt-2 text-gray-600">WHT deductions, certificates issued, and remittance tracking</p>
        </div>
        <button onclick="exportReport('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Export Excel</button>
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
            <div class="text-gray-600 text-sm font-medium">Total WHT Deducted</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">KES {{ number_format($report['total_wht'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Withholding tax withheld</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Certificates Issued</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ number_format($report['certificates_issued'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">WHT certificates generated</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Pending Remittance</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2">KES {{ number_format($report['pending_remittance'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Not yet remitted to KRA</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-semibold text-gray-900">WHT Transactions</h2></div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Supplier</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Invoice</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">WHT Rate</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">WHT Amount (KES)</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Certificate #</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($report['rows'] ?? [] as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['supplier'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['invoice'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ number_format($row['wht_rate'] ?? 0, 1) }}%</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($row['wht_amount'] ?? 0, 0) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['certificate_no'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row['date'] ?? '-' }}</td>
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