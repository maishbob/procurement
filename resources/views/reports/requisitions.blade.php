@extends('layouts.app')

@section('title', 'Requisition Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Requisition Report</h1>
            <p class="mt-2 text-gray-600">Track requisition status, submissions, and approvals</p>
        </div>
        <button onclick="exportReport('pdf')" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export PDF
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="date" id="from_date" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <input type="date" id="to_date" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <select id="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
            <select id="department" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <button onclick="applyFilters()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                Filter
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Total Requisitions</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total'] ?? 0 }}</div>
            <p class="text-xs text-gray-500 mt-2">KES {{ number_format($stats['total_amount'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Pending Approval</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2">{{ $stats['pending'] ?? 0 }}</div>
            <p class="text-xs text-gray-500 mt-2">Awaiting decision</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Approved</div>
            <div class="text-3xl font-bold text-green-600 mt-2">{{ $stats['approved'] ?? 0 }}</div>
            <p class="text-xs text-gray-500 mt-2">Ready for PO</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm font-medium">Rejected</div>
            <div class="text-3xl font-bold text-red-600 mt-2">{{ $stats['rejected'] ?? 0 }}</div>
            <p class="text-xs text-gray-500 mt-2">Returned to draft</p>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">REQ Number</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Department</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Requester</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Amount</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Items</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($requisitions as $req)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-primary-600">
                        <a href="{{ route('requisitions.show', $req) }}" class="hover:underline">
                            {{ $req->reference_number }}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $req->department->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $req->requester->name }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">KES {{ number_format($req->total_amount, 2) }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $req->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $req->status === 'submitted' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $req->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $req->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                        ">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $req->items()->count() }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $req->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">No requisitions found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($requisitions->hasPages())
    <div class="flex justify-center">
        {{ $requisitions->links() }}
    </div>
    @endif
</div>

<script>
    function applyFilters() {
        const from = document.getElementById('from_date').value;
        const to = document.getElementById('to_date').value;
        const status = document.getElementById('status').value;
        const dept = document.getElementById('department').value;
        
        const params = new URLSearchParams();
        if (from) params.append('from_date', from);
        if (to) params.append('to_date', to);
        if (status) params.append('status', status);
        if (dept) params.append('department', dept);
        
        window.location.href = '{{ route("reports.requisitions") }}?' + params.toString();
    }

    function exportReport(format) {
        alert(`Exporting as ${format.toUpperCase()}...`);
        // Implementation would integrate with ReportController export method
    }
</script>
@endsection

