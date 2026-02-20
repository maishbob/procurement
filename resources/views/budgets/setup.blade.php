@extends('layouts.app')

@section('title', 'Budget Setup')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Budget Setup</h1>
            <p class="mt-2 text-gray-600">Create and manage department budgets by fiscal year</p>
        </div>
    </div>

    <!-- Fiscal Year Selection/Creation -->
    <div class="bg-white rounded-lg shadow-sm border-2 border-gray-300 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 1: Select or Create Fiscal Year</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Existing Fiscal Years -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3">Existing Fiscal Years</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($fiscalYears ?? [] as $fy)
                    <div class="flex items-center justify-between p-3 border rounded-lg {{ $fy->is_active ? 'border-primary-500 bg-primary-50' : 'border-gray-300' }}">
                        <div>
                            <p class="font-medium text-gray-900">{{ $fy->name }}</p>
                            <p class="text-xs text-gray-500">{{ $fy->start_date->format('M d, Y') }} - {{ $fy->end_date->format('M d, Y') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($fy->is_active)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Active</span>
                            @else
                                <form action="{{ route('fiscal-years.set-active', $fy->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                        Set Active
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('budgets.department-setup', ['fiscal_year' => $fy->name]) }}" 
                               class="px-3 py-1 bg-primary-600 text-white text-sm rounded hover:bg-primary-700">
                                Set Budgets
                            </a>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 text-sm">No fiscal years created yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Create New Fiscal Year -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-3">Create New Fiscal Year</h3>
                <form action="{{ route('fiscal-years.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Year Name <span class="text-red-500">*</span></label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               required
                               placeholder="e.g., FY 2026"
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:border-primary-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:border-primary-500 focus:outline-none">
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-red-500">*</span></label>
                        <input type="date" 
                               id="end_date" 
                               name="end_date" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:border-primary-500 focus:outline-none">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <label for="is_active" class="ml-2 text-sm text-gray-700">Set as active fiscal year</label>
                    </div>

                    <button type="submit" 
                            class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold">
                        Create Fiscal Year
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
