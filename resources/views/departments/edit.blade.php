@extends('layouts.app')

@section('title', 'Edit Department')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
            <a href="{{ route('departments.index') }}" class="hover:text-primary-600">Department Management</a>
            <span>/</span>
            <a href="{{ route('departments.show', $department) }}" class="hover:text-primary-600">{{ $department->code }}</a>
            <span>/</span>
            <span class="text-gray-900">Edit</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">Edit Department</h1>
        <p class="mt-2 text-gray-600">Update department information</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('departments.update', $department) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Basic Information -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                            Department Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="code" 
                               name="code" 
                               value="{{ old('code', $department->code) }}"
                               required
                               placeholder="e.g., ADM, FIN, ICT"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('code') border-red-500 @enderror">
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Department Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $department->name) }}"
                               required
                               placeholder="e.g., Administration"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category" 
                                name="category"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('category') border-red-500 @enderror">
                            <option value="">Select Category</option>
                            <option value="Academic" {{ old('category', $department->category) == 'Academic' ? 'selected' : '' }}>Academic</option>
                            <option value="Operations" {{ old('category', $department->category) == 'Operations' ? 'selected' : '' }}>Operations</option>
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="Enter department description..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('description') border-red-500 @enderror">{{ old('description', $department->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Organizational Structure -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Organizational Structure</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Head of Department -->
                    <div>
                        <label for="head_of_department_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Head of Department
                        </label>
                        <select id="head_of_department_id" 
                                name="head_of_department_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('head_of_department_id') border-red-500 @enderror">
                            <option value="">Select Head of Department</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('head_of_department_id', $department->head_of_department_id) == $user->id ? 'selected' : '' }}>
                                    {{ $user->full_name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('head_of_department_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Parent Department -->
                    <div>
                        <label for="parent_department_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Parent Department
                        </label>
                        <select id="parent_department_id" 
                                name="parent_department_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 @error('parent_department_id') border-red-500 @enderror">
                            <option value="">None (Top-level department)</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('parent_department_id', $department->parent_department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Leave empty if this is a top-level department</p>
                        @error('parent_department_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
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
                           {{ old('is_active', $department->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                    <label for="is_active" class="ml-2 text-sm text-gray-700">
                        Active (Department can be used in the system)
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex gap-2">
                    <a href="{{ route('departments.show', $department) }}" 
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-md transition-colors">
                        Cancel
                    </a>
                    @can('delete', $department)
                        @if($department->users()->count() == 0 && $department->budgetLines()->where('is_active', true)->count() == 0 && $department->subDepartments()->count() == 0)
                        <button type="button"
                                onclick="if(confirm('Are you sure you want to delete this department?')) { document.getElementById('delete-form').submit(); }"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition-colors">
                            Delete
                        </button>
                        @endif
                    @endcan
                </div>
                <button type="submit" 
                        class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md transition-colors">
                    Update Department
                </button>
            </div>
        </form>

        <!-- Delete Form (hidden) -->
        @can('delete', $department)
        <form id="delete-form" action="{{ route('departments.destroy', $department) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        @endcan
    </div>
</div>
@endsection
