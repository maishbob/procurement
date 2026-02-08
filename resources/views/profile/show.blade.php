@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
                <p class="mt-1 text-sm text-gray-600">View and manage your account</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                Edit Profile
            </a>
        </div>
    </div>

    <!-- Success Messages -->
    @if (session('success'))
    <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
        <div class="flex">
            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Profile Information Card -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Account Information</h2>
        </div>

        <div class="px-6 py-6 space-y-6">
            <!-- Full Name -->
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-600">Full Name</p>
                    <p class="text-lg font-medium text-gray-900">{{ $user->name }}</p>
                </div>
            </div>

            <!-- Email -->
            <div class="flex justify-between items-start border-t border-gray-200 pt-6">
                <div>
                    <p class="text-sm text-gray-600">Email Address</p>
                    <p class="text-lg font-medium text-gray-900">{{ $user->email }}</p>
                </div>
            </div>

            <!-- Phone -->
            <div class="flex justify-between items-start border-t border-gray-200 pt-6">
                <div>
                    <p class="text-sm text-gray-600">Phone Number</p>
                    <p class="text-lg font-medium text-gray-900">{{ $user->phone ?? 'Not provided' }}</p>
                </div>
            </div>

            <!-- Employee ID -->
            @if($user->employee_id)
            <div class="flex justify-between items-start border-t border-gray-200 pt-6">
                <div>
                    <p class="text-sm text-gray-600">Employee ID</p>
                    <p class="text-lg font-medium text-gray-900">{{ $user->employee_id }}</p>
                </div>
            </div>
            @endif

            <!-- Department -->
            @if($user->department)
            <div class="flex justify-between items-start border-t border-gray-200 pt-6">
                <div>
                    <p class="text-sm text-gray-600">Department</p>
                    <p class="text-lg font-medium text-gray-900">{{ $user->department->name }}</p>
                </div>
            </div>
            @endif

            <!-- Roles -->
            @if($roles->count() > 0)
            <div class="flex justify-between items-start border-t border-gray-200 pt-6">
                <div>
                    <p class="text-sm text-gray-600 mb-2">Roles</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($roles as $role)
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                            {{ $role->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Member Since -->
            <div class="flex justify-between items-start border-t border-gray-200 pt-6">
                <div>
                    <p class="text-sm text-gray-600">Member Since</p>
                    <p class="text-lg font-medium text-gray-900">{{ $user->created_at->format('F d, Y') }}</p>
                </div>
            </div>

            <!-- Last Updated -->
            <div class="flex justify-between items-start border-t border-gray-200 pt-6">
                <div>
                    <p class="text-sm text-gray-600">Last Updated</p>
                    <p class="text-lg font-medium text-gray-900">{{ $user->updated_at->format('F d, Y \\a\\t H:i') }}</p>
                </div>
            </div>
        </div>
    </div>


</div>
@endsection
