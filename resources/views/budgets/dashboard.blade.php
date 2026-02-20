@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 py-6">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Budget Dashboard</h1>
            <p class="mt-2 text-sm text-gray-600">Fiscal Year: {{ request('fiscal_year', now()->year) }}</p>
        </div>
        <form method="GET" class="flex items-end gap-3">
            <div>
                <label for="fiscal_year" class="block text-sm font-medium text-gray-700">Fiscal Year</label>
                <select name="fiscal_year" id="fiscal_year"
                        class="mt-1 w-40 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        onchange="this.form.submit()">
                    @for($year = now()->year - 2; $year <= now()->year + 2; $year++)
                        <option value="{{ $year }}" {{ request('fiscal_year', now()->year) == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endfor
                </select>
            </div>
        </form>
    </div>

    <!-- Overview Cards -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg bg-white p-6 shadow">
            <p class="text-sm font-medium text-gray-600">Total Allocated</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($totalAllocated, 2) }}</p>
        </div>
        <div class="rounded-lg bg-green-50 p-6 shadow">
            <p class="text-sm font-medium text-green-700">Approved Budgets</p>
            <p class="mt-2 text-2xl font-bold text-green-800">{{ number_format($approvedAmount, 2) }}</p>
        </div>
        <div class="rounded-lg bg-yellow-50 p-6 shadow">
            <p class="text-sm font-medium text-yellow-700">Pending Review</p>
            <p class="mt-2 text-2xl font-bold text-yellow-800">{{ number_format($pendingAmount, 2) }}</p>
        </div>
        <div class="rounded-lg bg-blue-50 p-6 shadow">
            <p class="text-sm font-medium text-blue-700">Draft Budgets</p>
            <p class="mt-2 text-2xl font-bold text-blue-800">{{ number_format($draftAmount, 2) }}</p>
        </div>
    </div>

    <!-- Budget by Department Category -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Academic Departments</h2>
            </div>
            <div class="p-6">
                @if($academicBudgets->isEmpty())
                    <p class="text-sm text-gray-500">No academic department budgets for this fiscal year.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Allocated</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($academicBudgets as $budget)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ ucfirst($budget->category) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600">{{ $budget->department->name }}</td>
                                    <td class="px-4 py-2 text-right text-sm font-medium text-gray-900">{{ number_format($budget->allocated_amount, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-sm">
                                        @if($budget->status === 'approved')
                                            <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Approved</span>
                                        @elseif($budget->status === 'pending_review')
                                            <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800">Pending</span>
                                        @elseif($budget->status === 'rejected')
                                            <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">Rejected</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800">Draft</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                <tr class="bg-gray-50">
                                    <td class="px-4 py-2 text-sm font-semibold text-gray-900" colspan="2">Total</td>
                                    <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900">{{ number_format($academicTotal, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Operations Departments</h2>
            </div>
            <div class="p-6">
                @if($operationsBudgets->isEmpty())
                    <p class="text-sm text-gray-500">No operations department budgets for this fiscal year.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Allocated</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($operationsBudgets as $budget)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ ucfirst($budget->category) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-600">{{ $budget->department->name }}</td>
                                    <td class="px-4 py-2 text-right text-sm font-medium text-gray-900">{{ number_format($budget->allocated_amount, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-sm">
                                        @if($budget->status === 'approved')
                                            <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Approved</span>
                                        @elseif($budget->status === 'pending_review')
                                            <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800">Pending</span>
                                        @elseif($budget->status === 'rejected')
                                            <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">Rejected</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800">Draft</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                <tr class="bg-gray-50">
                                    <td class="px-4 py-2 text-sm font-semibold text-gray-900" colspan="2">Total</td>
                                    <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900">{{ number_format($operationsTotal, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Budget by Category (Consolidated) -->
    <div class="rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Budget Summary by Category</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                @foreach(['operational', 'capital', 'development', 'emergency'] as $category)
                @php
                    $categoryTotal = $budgetsByCategory->get($category, 0);
                @endphp
                <div class="rounded-lg border border-gray-200 p-4 text-center">
                    <p class="text-sm font-medium text-gray-600">{{ ucfirst($category) }}</p>
                    <p class="mt-2 text-xl font-bold text-gray-900">{{ number_format($categoryTotal, 2) }}</p>
                    @if($totalAllocated > 0)
                    <p class="mt-1 text-xs text-gray-500">{{ number_format(($categoryTotal / $totalAllocated) * 100, 1) }}%</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
