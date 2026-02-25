@extends('layouts.app')

@section('title', 'Cash Flow Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Cash Flow Report</h1>
            <p class="mt-2 text-gray-600">Monthly inflows, outflows, and net cash position</p>
        </div>
        <button onclick="exportReport('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Export Excel</button>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ request()->url() }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" name="fiscal_year" value="{{ $filters['fiscal_year'] ?? '' }}" placeholder="Fiscal Year (e.g. 2025/2026)" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <div></div>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
            </div>
        </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Inflows</div>
            <div class="text-3xl font-bold text-green-600 mt-2">KES {{ number_format($report['total_inflows'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Cash received</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Outflows</div>
            <div class="text-3xl font-bold text-red-600 mt-2">KES {{ number_format($report['total_outflows'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Cash disbursed</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            @php $nc = $report['net_cash'] ?? 0; $ncc = $nc >= 0 ? 'text-primary-600' : 'text-red-600'; @endphp
            <div class="text-gray-600 text-sm font-medium">Net Cash</div>
            <div class="text-3xl font-bold {{ $ncc }} mt-2">KES {{ number_format($nc, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Inflows minus outflows</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-semibold text-gray-900">Monthly Cash Flow</h2></div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Month</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Inflows (KES)</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Outflows (KES)</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Net Cash (KES)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($report['monthly'] ?? [] as $row)
                @php $rn = ($row['inflows'] ?? 0) - ($row['outflows'] ?? 0); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['month'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-green-600 text-right font-medium">{{ number_format($row['inflows'] ?? 0, 0) }}</td>
                    <td class="px-6 py-4 text-sm text-red-600 text-right font-medium">{{ number_format($row['outflows'] ?? 0, 0) }}</td>
                    <td class="px-6 py-4 text-sm text-right font-bold {{ $rn >= 0 ? 'text-gray-900' : 'text-red-600' }}">{{ number_format($rn, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No data found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>function exportReport(f){alert('Exporting as '+f.toUpperCase()+'...');}</script>
@endsection