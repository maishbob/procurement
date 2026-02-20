@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="{{ route('admin.users.index') }}" class="text-primary-600 hover:text-primary-700 flex items-center mb-2">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Users
            </a>
            <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
            <p class="text-gray-600">{{ $user->email }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                Edit User
            </a>
        </div>
    </div>

    <!-- User Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-3xl font-bold mx-auto mb-4">
                    {{ $user->initials }}
                </div>
                <h2 class="text-lg font-bold text-gray-900">{{ $user->name }}</h2>
                <p class="text-gray-600">{{ $user->email }}</p>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="mb-3">
                        @if($user->is_active)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <span class="w-2 h-2 bg-green-600 rounded-full mr-2"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <span class="w-2 h-2 bg-red-600 rounded-full mr-2"></span>
                                Inactive
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600">Last login: {{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</p>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="md:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Employee ID</p>
                        <p class="text-lg font-medium text-gray-900">{{ $user->employee_id ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Phone</p>
                        <p class="text-lg font-medium text-gray-900">{{ $user->phone ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Department</p>
                        <p class="text-lg font-medium text-gray-900">{{ $user->department?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Approval Limit</p>
                        <p class="text-lg font-medium text-gray-900">KES {{ number_format($user->approval_limit ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Roles & Permissions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Assigned Roles</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($user->roles as $role)
                        <span class="px-4 py-2 bg-primary-100 text-primary-800 rounded-full text-sm font-medium">
                            {{ $role->name }}
                        </span>
                    @empty
                        <p class="text-gray-600">No roles assigned</p>
                    @endforelse
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Permissions</h4>
                    <div class="grid grid-cols-2 gap-2">
                        @forelse($user->getAllPermissions() as $permission)
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $permission->name }}
                            </div>
                        @empty
                            <p class="text-gray-600">No permissions</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Account Activity -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Activity</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created</span>
                        <span class="font-medium text-gray-900">{{ $user->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Last Updated</span>
                        <span class="font-medium text-gray-900">{{ $user->updated_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Last Login</span>
                        <span class="font-medium text-gray-900">{{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

