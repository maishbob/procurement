@extends('layouts.app')

@section('title', 'Compliance Report')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Compliance Report</h1>
            <p class="mt-2 text-gray-600">PPADA compliance scores and outstanding issues</p>
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
        <div class="bg-white rounded-lg shadow p-6 md:col-span-1">
            @php
                $score = $report['overall_score'] ?? 0;
                $scoreColor = $score >= 80 ? 'text-green-600' : ($score >= 60 ? 'text-yellow-600' : 'text-red-600');
            @endphp
            <div class="text-gray-600 text-sm font-medium">Overall Compliance Score</div>
            <div class="text-5xl font-bold {{ $scoreColor }} mt-4">{{ number_format($score, 1) }}<span class="text-2xl">%</span></div>
            <p class="text-xs text-gray-500 mt-2">Across all compliance areas</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Issues</div>
            <div class="text-3xl font-bold text-red-600 mt-2">{{ number_format($report['total_issues'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Open compliance issues</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Areas Reviewed</div>
            <div class="text-3xl font-bold text-primary-600 mt-2">{{ number_format($report['areas_reviewed'] ?? 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Compliance areas assessed</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200"><h2 class="text-lg font-semibold text-gray-900">Compliance by Area</h2></div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Area</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Score %</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Issues</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($report['rows'] ?? [] as $row)
                @php
                    $s = $row['score'] ?? 0;
                    $bc = $s >= 80 ? 'bg-green-100 text-green-800' : ($s >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                    $lbl = $s >= 80 ? 'Compliant' : ($s >= 60 ? 'Partial' : 'Non-Compliant');
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['area'] ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm text-right">
                        <div class="flex items-center justify-end space-x-2">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="{{ $s >= 80 ? 'bg-green-500' : ($s >= 60 ? 'bg-yellow-500' : 'bg-red-500') }} h-2 rounded-full" style="width: {{ min($s, 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700">{{ number_format($s, 1) }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ number_format($row['issues'] ?? 0) }}</td>
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