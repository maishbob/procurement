@extends('layouts.app')

@section('title', 'Expenditure Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Expenditure Report</h1>
            <p class="mt-2 text-gray-600">Department-level budget allocation, commitments, and spending</p>
        </div>
        <button onclick="exportReport('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Export Excel</button>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ request()->url() }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" name="fiscal_year" value="{{ $filters['fiscal_year'] ?? '' }}" placeholder="Fiscal Year (e.g. 2025/2026)" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <select name="department_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">All Departments</option>
                    @foreach($report['departments'] ?? [] as $dept)
                        <option value="{{ $dept['id'] }}" @selected(($filters['department_id'] ?? '') == $dept['id'])>{{ $dept['name'] }}</option>
                    @endforeach
                </select>
                <div></div>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
            </div>
        </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Budget</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">KES {{ number_format($report['total_budget'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Total allocated budget</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Spent</div>
            <div class="text-3xl font-bold text-red-600 mt-2">KES {{ number_format($report['total_spent'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Actual expenditure</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            @php $v = ($report['total_budget'] ?? 0) - ($report['total_spent'] ?? 0); $vc = $v >= 0 ? 'text-primary-600' : 'text-red-600'; @endphp
            <div class="text-gray-600 text-sm font-medium">Variance</div>
            <div class="text-3xl font-bold {{ $vc }} mt-2">KES {{ number_format($v, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Budget minus expenditure</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-semibold text-gray-900">Expenditure by Department</h2></div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Department</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Allocated (KES)</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Committed (KES)</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Spent (KES)</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Available (KES)</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Utilization %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($report['rows'] ?? [] as $row)
                @php
                    $alloc = $row['allocated'] ?? 0;
                    $spent = $row['spent'] ?? 0;
                    $avail = $row['available'] ?? ($alloc - $spent);
                    $util  = $alloc > 0 ? round(($spent / $alloc) * 100, 1) : 0;
                    $bar   = $util > 90 ? 'bg-red-500' : ($util > 70 ? 'bg-yellow-500' : 'bg-primary-600');
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['department'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ number_format($alloc, 0) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ number_format($row['committed'] ?? 0, 0) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ number_format($spent, 0) }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ number_format($avail, 0) }}</td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 bg-gray-200 rounded-full h-2"><div class="{{ $bar }} h-2 rounded-full" style="width: {{ min($util, 100) }}%"></div></div>
                            <span class="text-xs font-medium text-gray-600 w-10 text-right">{{ $util }}%</span>
                        </div>
                    </td>
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