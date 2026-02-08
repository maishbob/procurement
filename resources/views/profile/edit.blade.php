@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Edit Profile</h1>
        <p class="mt-1 text-sm text-gray-600">Update your account information</p>
    </div>

    <!-- Edit Profile Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            @if ($errors->any())
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Full Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                    required autocomplete="name"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('name') border-red-500 @enderror">
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                    required autocomplete="email"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('email') border-red-500 @enderror">
                @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                    autocomplete="tel" placeholder="+254 (optional)"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('phone') border-red-500 @enderror">
                @error('phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Read-only Information -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Additional Information</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Employee ID -->
                    @if($user->employee_id)
                    <div>
                        <label class="block text-sm text-gray-600">Employee ID</label>
                        <p class="text-gray-900 font-medium">{{ $user->employee_id }}</p>
                    </div>
                    @endif

                    <!-- Department -->
                    @if($user->department)
                    <div>
                        <label class="block text-sm text-gray-600">Department</label>
                        <p class="text-gray-900 font-medium">{{ $user->department->name }}</p>
                    </div>
                    @endif

                    <!-- Member Since -->
                    <div>
                        <label class="block text-sm text-gray-600">Member Since</label>
                        <p class="text-gray-900 font-medium">{{ $user->created_at->format('M d, Y') }}</p>
                    </div>

                    <!-- Last Updated -->
                    <div>
                        <label class="block text-sm text-gray-600">Last Updated</label>
                        <p class="text-gray-900 font-medium">{{ $user->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 justify-end pt-6 border-t border-gray-200">
                <a href="{{ route('profile.show') }}" class="px-6 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
