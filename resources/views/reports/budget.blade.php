@extends('layouts.app')

@section('title', 'Budget Utilization Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Budget Utilization Report</h1>
            <p class="mt-2 text-gray-600">Monitor budget allocation, commitments, and expenditures</p>
        </div>
        <button onclick="exportReport('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Excel
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <select id="fiscal_year" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Fiscal Years</option>
                @foreach($fiscal_years as $year)
                    <option value="{{ $year->id }}" @selected($year->is_current)>{{ $year->name }}</option>
                @endforeach
            </select>
            <select id="department" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <input type="text" id="search" placeholder="Search budget line..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <button onclick="applyFilters()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Allocation</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">KES {{ number_format($summary['allocated'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Across all budgets</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Committed</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2">KES {{ number_format($summary['committed'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">{{ round(($summary['committed'] ?? 0) / ($summary['allocated'] ?? 1) * 100, 1) }}% of allocation</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Executed</div>
            <div class="text-3xl font-bold text-green-600 mt-2">KES {{ number_format($summary['executed'] ?? 0, 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">{{ round(($summary['executed'] ?? 0) / ($summary['allocated'] ?? 1) * 100, 1) }}% of allocation</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Available</div>
            <div class="text-3xl font-bold text-primary-600 mt-2">KES {{ number_format(($summary['allocated'] ?? 0) - ($summary['executed'] ?? 0), 0) }}</div>
            <p class="text-xs text-gray-500 mt-2">Remaining balance</p>
        </div>
    </div>

    <!-- Budget Lines Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Budget Line</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Department</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Allocated</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Committed</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Executed</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Available</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Utilization</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($budgets as $budget)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $budget->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $budget->department->name }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">KES {{ number_format($budget->allocated_amount, 0) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">KES {{ number_format($budget->commitment_total, 0) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">KES {{ number_format($budget->execution_total, 0) }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">KES {{ number_format($budget->allocated_amount - $budget->execution_total, 0) }}</td>
                    <td class="px-6 py-4 text-sm">
                        @php $utilization = round(($budget->execution_total / $budget->allocated_amount) * 100, 1) @endphp
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full" style="width: {{ min($utilization, 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-600 w-10 text-right">{{ $utilization }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">No budgets found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    function applyFilters() {
        const year = document.getElementById('fiscal_year').value;
        const dept = document.getElementById('department').value;
        const search = document.getElementById('search').value;
        
        const params = new URLSearchParams();
        if (year) params.append('fiscal_year', year);
        if (dept) params.append('department', dept);
        if (search) params.append('search', search);
        
        window.location.href = '{{ route("reports.budget") }}?' + params.toString();
    }

    function exportReport(format) {
        alert(`Exporting as ${format.toUpperCase()}...`);
    }
</script>
@endsection

