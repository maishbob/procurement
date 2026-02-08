@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Audit Logs</h1>
            <p class="mt-2 text-gray-600">Immutable record of all system changes and user actions</p>
        </div>
        <button onclick="exportLogs()" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="date" id="from_date" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <input type="date" id="to_date" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <select id="entity_type" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Entity Types</option>
                <option value="Requisition">Requisition</option>
                <option value="PurchaseOrder">Purchase Order</option>
                <option value="SupplierInvoice">Invoice</option>
                <option value="Payment">Payment</option>
                <option value="GoodsReceivedNote">GRN</option>
                <option value="InventoryItem">Inventory</option>
            </select>
            <select id="action" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Actions</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="restored">Restored</option>
            </select>
            <button onclick="applyFilters()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Events</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Last 24 Hours</div>
            <div class="text-3xl font-bold text-primary-600 mt-2">{{ $stats['last_24h'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Active Users</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ $stats['active_users'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Archives</div>
            <div class="text-3xl font-bold text-primary-600 mt-2">{{ $stats['archived'] ?? 0 }}</div>
            <p class="text-xs text-gray-500 mt-2">Logs 90+ days old</p>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Timestamp</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">User</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Entity</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Action</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Details</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">IP Address</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleDetails({{ $log->id }})">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                        {{ $log->created_at->format('M d, Y H:i:s') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $log->user?->name ?? 'System' }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 text-primary-800">
                            {{ $log->entity_type }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $log->action === 'created' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $log->action === 'updated' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $log->action === 'deleted' ? 'bg-red-100 text-red-800' : '' }}
                        ">
                            {{ ucfirst($log->action) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <button onclick="showDetails(event, {{ $log->id }})" class="text-primary-600 hover:text-primary-900 text-xs font-medium">
                            View Changes
                        </button>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 font-mono text-xs">{{ $log->ip_address ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($log->is_verified)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 bg-green-600 rounded-full mr-1.5"></span>
                                Verified
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <span class="w-1.5 h-1.5 bg-gray-600 rounded-full mr-1.5"></span>
                                Logged
                            </span>
                        @endif
                    </td>
                </tr>
                <tr id="details-{{ $log->id }}" class="hidden bg-gray-50">
                    <td colspan="7" class="px-6 py-4">
                        <div class="text-sm">
                            <p class="font-semibold text-gray-900 mb-2">Change Details:</p>
                            @if($log->new_values || $log->old_values)
                                <div class="bg-white rounded p-4 font-mono text-xs">
                                    @if($log->old_values)
                                        <p class="text-red-600 mb-2"><strong>Before:</strong> {{ json_encode(json_decode($log->old_values), JSON_PRETTY_PRINT) }}</p>
                                    @endif
                                    @if($log->new_values)
                                        <p class="text-green-600"><strong>After:</strong> {{ json_encode(json_decode($log->new_values), JSON_PRETTY_PRINT) }}</p>
                                    @endif
                                </div>
                            @else
                                <p class="text-gray-500">No changes recorded</p>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">No audit logs found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($logs->hasPages())
    <div class="flex justify-center">
        {{ $logs->links() }}
    </div>
    @endif
</div>

<script>
    function applyFilters() {
        const from = document.getElementById('from_date').value;
        const to = document.getElementById('to_date').value;
        const entity = document.getElementById('entity_type').value;
        const action = document.getElementById('action').value;
        
        const params = new URLSearchParams();
        if (from) params.append('from_date', from);
        if (to) params.append('to_date', to);
        if (entity) params.append('entity_type', entity);
        if (action) params.append('action', action);
        
        window.location.href = '{{ route("admin.audit-logs") }}?' + params.toString();
    }

    function showDetails(e, logId) {
        e.stopPropagation();
        toggleDetails(logId);
    }

    function toggleDetails(logId) {
        const details = document.getElementById(`details-${logId}`);
        details.classList.toggle('hidden');
    }

    function exportLogs() {
        alert('Exporting audit logs...');
        // Implementation would call export endpoint
    }
</script>
@endsection

