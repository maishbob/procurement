@extends('layouts.app')

@section('title', 'Scheduled Reports')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Scheduled Reports</h1>
            <p class="mt-2 text-gray-600">Manage automated report generation and delivery</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">{{ session('error') }}</div>
    @endif

    <!-- Scheduled Reports Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">My Scheduled Reports</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Type</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Schedule</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Recipients</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Format</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Created</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($scheduledReports as $sr)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ ucfirst($sr->report_type ?? '-') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($sr->schedule ?? '-') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        @php
                            $emails = is_array($sr->recipient_emails) ? $sr->recipient_emails : json_decode($sr->recipient_emails ?? '[]', true);
                        @endphp
                        {{ implode(', ', array_slice($emails ?? [], 0, 2)) }}{{ count($emails ?? []) > 2 ? ' +' . (count($emails) - 2) . ' more' : '' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ strtoupper($sr->export_format ?? '-') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $sr->created_at?->format('d M Y') ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <form method="POST" action="{{ route('reports.scheduled.destroy', $sr->id) }}" onsubmit="return confirm('Delete this scheduled report?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No scheduled reports found</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($scheduledReports->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">{{ $scheduledReports->links() }}</div>
        @endif
    </div>

    <!-- Create Scheduled Report Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Create Scheduled Report</h2>
        <form method="POST" action="{{ route('reports.scheduled.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select name="report_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Select report type...</option>
                        <option value="requisition">Requisition Report</option>
                        <option value="procurement">Procurement Report</option>
                        <option value="supplier">Supplier Report</option>
                        <option value="inventory">Inventory Report</option>
                        <option value="financial">Financial Report</option>
                        <option value="budget">Budget Report</option>
                    </select>
                    @error('report_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Schedule</label>
                    <select name="schedule" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Select schedule...</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                    @error('schedule')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Emails</label>
                    <input type="text" name="recipient_emails" value="{{ old('recipient_emails') }}" placeholder="alice@school.ac.ke, bob@school.ac.ke" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('recipient_emails')<p class="mt-1 text-xs text-red-600">{{ \ }}</p>@enderror
                    <p class="mt-1 text-xs text-gray-500">Enter one or more email addresses separated by commas.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Export Format</label>
                    <select name="export_format" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Select format...</option>
                        <option value="excel">Excel (.xlsx)</option>
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV</option>
                    </select>
                    @error('export_format')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    Create Scheduled Report
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
