@extends('layouts.app')

@section('title', 'Edit Budget Line')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
            <a href="{{ route('budgets.index') }}" class="hover:text-primary-600">Budget Management</a>
            <span>/</span>
            <a href="{{ route('budgets.show', $budget) }}" class="hover:text-primary-600">{{ $budget->budget_code }}</a>
            <span>/</span>
            <span class="text-gray-900">Edit</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">Edit Budget Line</h1>
        <p class="mt-2 text-gray-600">Update budget line information</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('budgets.update', $budget) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Basic Information -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Code -->
                    <div>
                        <label for="budget_code" class="block text-sm font-medium text-gray-700 mb-1">
                            Budget Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="budget_code" 
                               name="budget_code" 
                               value="{{ old('budget_code', $budget->budget_code) }}"
                               required
                               placeholder="e.g., BUD-2024-001"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('budget_code') border-red-500 @enderror">
                        @error('budget_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="description" 
                               name="description" 
                               value="{{ old('description', $budget->description) }}"
                               required
                               placeholder="e.g., Operational Supplies"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('description') border-red-500 @enderror">
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Fiscal Year -->
                    <div>
                        <label for="fiscal_year" class="block text-sm font-medium text-gray-700 mb-1">
                            Fiscal Year <span class="text-red-500">*</span>
                        </label>
                        <select id="fiscal_year" 
                                name="fiscal_year"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('fiscal_year') border-red-500 @enderror">
                            <option value="">Select Fiscal Year</option>
                            @foreach($fiscalYears as $fy)
                                <option value="{{ $fy->name }}" {{ old('fiscal_year', $budget->fiscal_year) == $fy->name ? 'selected' : '' }}>
                                    {{ $fy->name }} ({{ $fy->start_date }} to {{ $fy->end_date }})
                                </option>
                            @endforeach
                        </select>
                        @error('fiscal_year')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Department -->
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Department <span class="text-red-500">*</span>
                        </label>
                        <select id="department_id" 
                                name="department_id"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('department_id') border-red-500 @enderror">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $budget->department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                            Budget Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category" 
                                name="category"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('category') border-red-500 @enderror">
                            <option value="">Select Category</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ old('category', $budget->category) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Allocated Amount -->
                    <div>
                        <label for="allocated_amount" class="block text-sm font-medium text-gray-700 mb-1">
                            Allocated Amount <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">KES</span>
                            <input type="number" 
                                   id="allocated_amount" 
                                   name="allocated_amount" 
                                   value="{{ old('allocated_amount', $budget->allocated_amount) }}"
                                   required
                                   min="0"
                                   step="0.01"
                                   placeholder="0.00"
                                   class="w-full pl-14 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('allocated_amount') border-red-500 @enderror">
                        </div>
                        @error('allocated_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Budget Utilization (Read-only) -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Budget Utilization</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 mb-1">Committed Amount</p>
                        <p class="text-xl font-bold text-yellow-600">{{ number_format($budget->committed_amount, 2) }}</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 mb-1">Spent Amount</p>
                        <p class="text-xl font-bold text-red-600">{{ number_format($budget->spent_amount, 2) }}</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 mb-1">Available Amount</p>
                        <p class="text-xl font-bold text-green-600">{{ number_format($budget->available_amount, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Additional Details -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Additional Details</h2>
                
                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        Notes
                    </label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="3"
                              placeholder="Enter any additional notes..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('notes') border-red-500 @enderror">{{ old('notes', $budget->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status -->
            <div class="pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Status</h2>
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           value="1"
                           {{ old('is_active', $budget->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">
                        Active (Budget line can be used for requisitions)
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex gap-2">
                    <a href="{{ route('budgets.show', $budget) }}" 
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-md transition-colors">
                        Cancel
                    </a>
                    @can('delete', $budget)
                        @if($budget->committed_amount == 0 && $budget->spent_amount == 0)
                        <button type="button"
                                onclick="if(confirm('Are you sure you want to delete this budget line?')) { document.getElementById('delete-form').submit(); }"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition-colors">
                            Delete
                        </button>
                        @endif
                    @endcan
                </div>
                <button type="submit" 
                        class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md transition-colors">
                    Update Budget Line
                </button>
            </div>
        </form>

        <!-- Delete Form (hidden) -->
        @can('delete', $budget)
        <form id="delete-form" action="{{ route('budgets.destroy', $budget) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        @endcan
    </div>
</div>
@endsection
