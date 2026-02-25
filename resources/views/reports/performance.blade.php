@extends('layouts.app')

@section('title', 'Performance Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Performance Dashboard</h1>
            <p class="mt-2 text-gray-600">Key performance indicators and targets for procurement operations</p>
        </div>
        <button onclick="exportReport('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Export Excel</button>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ request()->url() }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" placeholder="Date From" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" placeholder="Date To" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
            </div>
        </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Approval Rate</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ number_format($kpis['approval_rate'] ?? 0, 1) }}%</div>
            <p class="text-xs text-gray-500 mt-2">Requisitions approved</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Avg Cycle Time</div>
            <div class="text-3xl font-bold text-primary-600 mt-2">{{ number_format($kpis['avg_cycle_time_days'] ?? 0, 1) }} days</div>
            <p class="text-xs text-gray-500 mt-2">Requisition to PO</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">3-Way Match Rate</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ number_format($kpis['three_way_match_rate'] ?? 0, 1) }}%</div>
            <p class="text-xs text-gray-500 mt-2">PO+GRN+Invoice matched</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">On-Time Delivery</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2">{{ number_format($kpis['on_time_delivery_rate'] ?? 0, 1) }}%</div>
            <p class="text-xs text-gray-500 mt-2">Delivered on schedule</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-semibold text-gray-900">KPI Detail</h2></div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">KPI Name</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Value</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Target</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($kpis['rows'] ?? [] as $kpi)
                @php
                    $met = ($kpi['value'] ?? 0) >= ($kpi['target'] ?? 0);
                    $bc  = $met ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    $lbl = $met ? 'On Track' : 'Below Target';
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $kpi['name'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 text-right font-medium">{{ $kpi['value_formatted'] ?? number_format($kpi['value'] ?? 0, 1) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ $kpi['target_formatted'] ?? number_format($kpi['target'] ?? 0, 1) }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $bc }}">{{ $lbl }}</span>
                    </td>
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