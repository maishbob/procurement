@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
            <p class="mt-2 text-gray-600">Manage system users, roles, and permissions</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New User
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="search" id="search" placeholder="Search by name or email..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <select id="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            <select id="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button onclick="applyFilters()" class="px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors">
                Apply Filters
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
        <table class="w-full min-w-max">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">Role</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">Department</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">Last Login</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold">
                                {{ $user->initials }}
                            </div>
                            <div class="ml-3">
                                <p>{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">ID: {{ $user->employee_id ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-3 py-1 text-xs font-semibold bg-primary-100 text-primary-800 rounded-full">
                            {{ $user->roles->first()?->name ?? 'No Role' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->department?->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($user->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-2 h-2 bg-green-600 rounded-full mr-1.5"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <span class="w-2 h-2 bg-red-600 rounded-full mr-1.5"></span>
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center px-2 py-1 text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 rounded transition-colors" title="View Details">
                                View
                            </a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center px-2 py-1 text-xs bg-primary-100 text-primary-700 hover:bg-primary-200 rounded transition-colors" title="Edit User">
                                Edit
                            </a>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-xs bg-red-100 text-red-700 hover:bg-red-200 rounded transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM6 20a9 9 0 0118 0v2H6v-2z"></path>
                        </svg>
                        No users found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
    <div class="flex justify-center">
        {{ $users->links() }}
    </div>
    @endif
</div>

<script>
    function applyFilters() {
        const search = document.getElementById('search').value;
        const role = document.getElementById('role').value;
        const status = document.getElementById('status').value;
        
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (role) params.append('role', role);
        if (status) params.append('status', status);
        
        window.location.href = '{{ route("admin.users.index") }}?' + params.toString();
    }
</script>
@endsection

