@extends('layouts.app')

@section('title', 'Budget Allocation')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Budget Allocation</h1>
            <p class="mt-2 text-gray-600">Allocate and manage department budgets by fiscal year</p>
        </div>
        <a href="{{ route('admin.budgets.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New Budget
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <select id="fiscal_year" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Fiscal Years</option>
                @foreach($fiscal_years as $year)
                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                @endforeach
            </select>
            <select id="department" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <select id="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="locked">Locked</option>
                <option value="closed">Closed</option>
            </select>
            <button onclick="applyFilters()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Allocation</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">KES {{ number_format($summary['total'] ?? 0, 0) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Budget Lines</div>
            <div class="text-3xl font-bold text-primary-600 mt-2">{{ $summary['count'] ?? 0 }}</div>
            <p class="text-xs text-gray-500 mt-2">Across {{ $summary['departments'] ?? 0 }} departments</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Average Utilization</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ round($summary['avg_utilization'] ?? 0, 1) }}%</div>
        </div>
    </div>

    <!-- Budget Lines Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Budget Line</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Department</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Fiscal Year</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Allocated</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Executed</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Utilization</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($budgets as $budget)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $budget->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $budget->department->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $budget->fiscal_year->name }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">KES {{ number_format($budget->allocated_amount, 0) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">KES {{ number_format($budget->execution_total, 0) }}</td>
                    <td class="px-6 py-4 text-sm">
                        @php $util = round(($budget->execution_total / $budget->allocated_amount) * 100, 1) @endphp
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full" style="width: {{ min($util, 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-600 w-8 text-right">{{ $util }}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($budget->is_locked)
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Locked</span>
                        @elseif($budget->is_closed)
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Closed</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('admin.budgets.show', $budget) }}" class="text-primary-600 hover:text-primary-900" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </a>
                            @if(!$budget->is_locked && !$budget->is_closed)
                            <a href="{{ route('admin.budgets.edit', $budget) }}" class="text-gray-600 hover:text-gray-900" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">No budgets found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($budgets->hasPages())
    <div class="flex justify-center">
        {{ $budgets->links() }}
    </div>
    @endif
</div>

<script>
    function applyFilters() {
        const year = document.getElementById('fiscal_year').value;
        const dept = document.getElementById('department').value;
        const status = document.getElementById('status').value;
        
        const params = new URLSearchParams();
        if (year) params.append('fiscal_year', year);
        if (dept) params.append('department', dept);
        if (status) params.append('status', status);
        
        window.location.href = '{{ route("admin.budgets.index") }}?' + params.toString();
    }
</script>
@endsection

