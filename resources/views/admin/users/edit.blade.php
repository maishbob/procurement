@extends('layouts.app')

@section('title', 'Edit User — ' . $user->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('admin.users.show', $user) }}" class="text-primary-600 hover:text-primary-700 flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to User
        </a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Edit User</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $user->email }}</p>
    </div>

    @if($errors->any())
    <div class="mb-6 rounded-md bg-red-50 p-4">
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-6 space-y-6">
            @csrf
            @method('PATCH')

            <!-- Basic Information -->
            <fieldset>
                <legend class="text-lg font-semibold text-gray-900 mb-4">Basic Information</legend>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" id="name" name="name"
                               value="{{ old('name', $user->name) }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('name') border-red-500 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email', $user->email) }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('email') border-red-500 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               value="{{ old('phone', $user->phone) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('phone') border-red-500 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('phone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <!-- Account Settings -->
            <fieldset>
                <legend class="text-lg font-semibold text-gray-900 mb-4">Account Settings</legend>
                <div class="space-y-4">
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                        <select id="department_id" name="department_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('department_id') border-red-500 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(old('department_id', $user->department_id) == $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Roles *</label>
                        <div class="space-y-2 border border-gray-300 rounded-lg p-4 max-h-64 overflow-y-auto">
                            @foreach($roles as $role)
                            <label class="flex items-center">
                                <input type="checkbox"
                                       name="role_ids[]"
                                       value="{{ $role->id }}"
                                       @checked(in_array($role->id, old('role_ids', $userRoles)))
                                       class="w-4 h-4 text-primary-600 rounded focus:ring-2 focus:ring-primary-500">
                                <span class="ml-2 text-gray-700">{{ $role->name }}</span>
                                @if($role->description)
                                    <span class="ml-2 text-xs text-gray-400">— {{ $role->description }}</span>
                                @endif
                            </label>
                            @endforeach
                        </div>
                        @error('role_ids') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="approval_limit" class="block text-sm font-medium text-gray-700 mb-2">Approval Limit (KES)</label>
                        <input type="number" id="approval_limit" name="approval_limit"
                               value="{{ old('approval_limit', $user->approval_limit ?? 0) }}"
                               step="0.01" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <p class="text-xs text-gray-500 mt-1">Maximum amount this user can approve in a single transaction</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $user->is_active))
                               class="w-4 h-4 text-primary-600 rounded focus:ring-2 focus:ring-primary-500">
                        <label for="is_active" class="ml-2 text-gray-700">Account Active</label>
                    </div>
                </div>
            </fieldset>

            <!-- Change Password (optional) -->
            <fieldset>
                <legend class="text-lg font-semibold text-gray-900 mb-4">Change Password <span class="text-sm font-normal text-gray-500">(leave blank to keep current)</span></legend>
                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" id="password" name="password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('password') border-red-500 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
            </fieldset>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <a href="{{ route('admin.users.show', $user) }}"
                   class="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
